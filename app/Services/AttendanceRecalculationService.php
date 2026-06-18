<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\BiometricAttendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceRecalculationService
{
    // ── Constants ──────────────────────────────────────────────────────────────

    /**
     * When ALL punches carry a device direction (punch_direction ≠ 'unknown'),
     * consecutive same-direction punches within this window are duplicates.
     *
     * 10 minutes covers typical re-scan retries (failed face reads, door re-scans)
     * without swallowing legitimate fast re-entries.
     */
    private const DIR_DEDUP_SECONDS = 600;

    /**
     * Fallback dedup window used when punch_direction = 'unknown' for all punches
     * (old records / firmware without InOut field).
     */
    private const LEGACY_DEDUP_SECONDS = 90;

    /**
     * Absolute window (in seconds) from the FIRST punch of a cluster.
     * Any punch within this window of the cluster-start is part of the same "event".
     *
     * 5400 = 90 minutes — covers extended checkout retry sessions
     * (e.g. employee tries face-scan from 08:36 PM to 09:39 PM = 63 minutes → same cluster).
     */
    private const CLUSTER_WINDOW_SECS = 5400;

    /**
     * When an open session exists on a past date, look for a checkout punch up to
     * this many hours into the next calendar day before giving up.
     */
    private const OVERNIGHT_WINDOW_HOURS = 14;

    // ── Public API ────────────────────────────────────────────────────────────

    public function recalculate(string $employeeCode, string $date): void
    {
        AttendanceSession::where('employee_code', $employeeCode)
            ->where('session_date', $date)
            ->delete();

        // Exclude punches already used as overnight checkouts by the previous day
        $overnightCheckoutTimes = $this->overnightCheckoutTimesOnDate($employeeCode, $date);

        $punches = BiometricAttendance::where('employee_code', $employeeCode)
            ->whereDate('punch_time', $date)
            ->orderBy('punch_time')
            ->get()
            ->reject(fn ($p) => $overnightCheckoutTimes->contains(
                Carbon::parse($p->punch_time)->toDateTimeString()
            ));

        if ($punches->isEmpty()) {
            return;
        }

        // Choose algorithm:
        //  - If device sent BOTH 'in' and 'out' directions → direction-aware pairing.
        //  - If device sent only one direction (e.g. eSSL Attendance mode, all InOut=0)
        //    OR no direction at all → legacy alternating with a larger dedup window (5 min).
        $hasIn  = $punches->contains(fn ($p) => $p->punch_direction === 'in');
        $hasOut = $punches->contains(fn ($p) => $p->punch_direction === 'out');
        $hasBothDirections = $hasIn && $hasOut;

        if ($hasBothDirections) {
            $this->buildSessionsWithDirection($employeeCode, $date, $punches);
        } else {
            // Single-direction mode (all InOut=0 / all InOut=1 / all unknown).
            // Use alternating pairing with a 5-minute cluster-dedup window so that
            // repeated retry scans (e.g. 08:36, 08:39, 08:41 are all checkouts)
            // collapse into one effective punch before pairing.
            $this->buildSessionsLegacy($employeeCode, $date, $punches, 300);
        }
    }

    public function recalculateToday(): void
    {
        $date  = today()->toDateString();
        $codes = BiometricAttendance::whereDate('punch_time', $date)
            ->distinct('employee_code')
            ->pluck('employee_code');

        foreach ($codes as $code) {
            $this->recalculate($code, $date);
        }
    }

    /**
     * Recover open sessions from past dates by pairing them with the first
     * available punch on the following calendar day (overnight checkout).
     *
     * @return array{recovered:int, still_open:int}
     */
    public function recoverOpenSessions(): array
    {
        $openSessions = AttendanceSession::where('status', 'in_office')
            ->whereNull('check_out_at')
            ->where('session_date', '<', today()->toDateString())
            ->orderBy('session_date')
            ->get();

        $recovered = 0;
        $stillOpen = 0;

        foreach ($openSessions as $session) {
            $windowStart = Carbon::parse($session->session_date)->addDay()->startOfDay();
            $windowEnd   = Carbon::parse($session->session_date)->addDay()
                ->setHour(self::OVERNIGHT_WINDOW_HOURS)->setMinute(0)->setSecond(0);

            $nextPunch = BiometricAttendance::where('employee_code', $session->employee_code)
                ->whereBetween('punch_time', [$windowStart, $windowEnd])
                ->where('event_type', '!=', 'skipped')
                ->orderBy('punch_time')
                ->first();

            if (! $nextPunch || $this->isAlreadyOvernightCheckout($session->employee_code, $nextPunch->punch_time)) {
                $stillOpen++;
                continue;
            }

            $mins = (int) Carbon::parse($session->check_in_at)->diffInMinutes($nextPunch->punch_time);

            $session->update([
                'check_out_at'     => $nextPunch->punch_time,
                'duration_minutes' => $mins,
                'status'           => 'present',
                'is_overnight'     => true,
            ]);

            $nextPunch->update(['event_type' => 'check_out']);
            $recovered++;
        }

        return ['recovered' => $recovered, 'still_open' => $stillOpen];
    }

    // ── Direction-aware algorithm (primary path) ──────────────────────────────

    /**
     * Used when the device sent InOut direction for at least one punch.
     *
     * Algorithm:
     *   1. For each punch, normalise direction (fall back to 'in' / 'out' from
     *      punch_direction; use 'in' when unknown and nothing preceding it yet).
     *   2. Dedup consecutive same-direction punches within DIR_DEDUP_SECONDS.
     *      Keeps the FIRST of each direction run.
     *   3. Pair the remaining sequence: in → out → in → out …
     *      An unmatched trailing 'in' creates an open (in_office) session.
     *   4. For past dates with an open session: look ahead to the next day for
     *      an overnight checkout.
     */
    private function buildSessionsWithDirection(string $code, string $date, Collection $punches): void
    {
        // ── Step 1: Assign final direction to each punch ──────────────────────
        // For punches with punch_direction='unknown', inherit the OPPOSITE of the
        // previous effective punch (so a stray unknown punch alternates direction
        // rather than silently creating a same-direction duplicate).
        $directed = [];
        $lastDir  = null;

        foreach ($punches as $punch) {
            $dir = $punch->punch_direction;

            if (! in_array($dir, ['in', 'out'])) {
                // No device direction — guess: first punch is 'in', thereafter alternate
                $dir = $lastDir === null ? 'in' : ($lastDir === 'in' ? 'out' : 'in');
            }

            $directed[] = ['punch' => $punch, 'dir' => $dir];
            $lastDir    = $dir;
        }

        // ── Step 2: Direction-aware deduplication ─────────────────────────────
        // Consecutive same-direction punches within DIR_DEDUP_SECONDS → keep first,
        // mark rest as skipped.
        $deduped  = [];
        $skipped  = [];
        $prevDir  = null;
        $prevTime = null;

        foreach ($directed as ['punch' => $punch, 'dir' => $dir]) {
            $t = Carbon::parse($punch->punch_time);

            $isSameDir        = $prevDir !== null && $dir === $prevDir;
            $isWithinWindow   = $prevTime !== null && $t->diffInSeconds($prevTime) <= self::DIR_DEDUP_SECONDS;

            if ($isSameDir && $isWithinWindow) {
                $skipped[] = $punch->id;
            } else {
                $deduped[] = ['punch' => $punch, 'dir' => $dir];
                $prevDir   = $dir;
                $prevTime  = $t;
            }
        }

        if (! empty($skipped)) {
            BiometricAttendance::whereIn('id', $skipped)->update(['event_type' => 'skipped']);
        }

        // ── Step 3: Pair in → out ──────────────────────────────────────────────
        $index   = 0;
        $checkIn = null;

        foreach ($deduped as ['punch' => $punch, 'dir' => $dir]) {
            if ($dir === 'in') {
                if ($checkIn !== null) {
                    // Two consecutive check-ins (shouldn't happen after dedup, but safe)
                    // Close the previous open one without a checkout
                    AttendanceSession::create([
                        'employee_code'    => $code,
                        'session_date'     => $date,
                        'session_index'    => $index++,
                        'check_in_at'      => $checkIn->punch_time,
                        'check_out_at'     => null,
                        'duration_minutes' => null,
                        'status'           => 'in_office',
                        'is_overnight'     => false,
                    ]);
                }
                $checkIn = $punch;
                BiometricAttendance::where('id', $punch->id)->update(['event_type' => 'check_in']);
            } else {
                // 'out'
                if ($checkIn === null) {
                    // Checkout without matching check-in — skip it
                    BiometricAttendance::where('id', $punch->id)->update(['event_type' => 'skipped']);
                    continue;
                }

                $mins = (int) Carbon::parse($checkIn->punch_time)->diffInMinutes($punch->punch_time);

                BiometricAttendance::where('id', $punch->id)->update(['event_type' => 'check_out']);

                AttendanceSession::create([
                    'employee_code'    => $code,
                    'session_date'     => $date,
                    'session_index'    => $index++,
                    'check_in_at'      => $checkIn->punch_time,
                    'check_out_at'     => $punch->punch_time,
                    'duration_minutes' => $mins,
                    'status'           => 'present',
                    'is_overnight'     => false,
                ]);

                $checkIn = null;
            }
        }

        // Unpaired trailing check-in
        if ($checkIn !== null) {
            $this->createOpenOrOvernightSession($code, $date, $checkIn->punch_time, $index);
        }
    }

    // ── Legacy alternating algorithm (fallback for old records) ──────────────

    private function buildSessionsLegacy(string $code, string $date, Collection $punches, int $dedupSecs = self::LEGACY_DEDUP_SECONDS): void
    {
        // ── Cluster-based deduplication ───────────────────────────────────────
        // Groups consecutive punches whose timestamps fall within CLUSTER_WINDOW_SECS
        // of the FIRST punch in that cluster into a single "event".
        //
        // Cluster 1, 3, 5 … = check-in events  → keep the FIRST punch (exact arrival)
        // Cluster 2, 4, 6 … = check-out events → keep the LAST  punch (final departure)
        //
        // This correctly handles retry-scans:
        //   03:19 PM → cluster 1 → first=03:19 → CHECK-IN
        //   08:36–09:39 PM (all within 90 min of 08:36) → cluster 2 → last=09:39 → CHECK-OUT
        $clusters = $this->clusterPunches($punches, self::CLUSTER_WINDOW_SECS);

        $deduped = [];
        $skipped = [];

        foreach ($clusters as $idx => $cluster) {
            $isCheckIn = ($idx % 2 === 0);           // 0-indexed: even = in, odd = out
            $kept      = $isCheckIn ? $cluster[0] : end($cluster);

            foreach ($cluster as $punch) {
                if ($punch->id === $kept->id) {
                    $deduped[] = $punch;
                } else {
                    $skipped[] = $punch->id;
                }
            }
        }

        if (! empty($skipped)) {
            BiometricAttendance::whereIn('id', $skipped)->update(['event_type' => 'skipped']);
        }

        $index   = 0;
        $checkIn = null;

        foreach ($deduped as $punch) {
            if ($checkIn === null) {
                $checkIn = $punch;
                BiometricAttendance::where('id', $punch->id)->update(['event_type' => 'check_in']);
            } else {
                $mins = (int) Carbon::parse($checkIn->punch_time)->diffInMinutes($punch->punch_time);
                BiometricAttendance::where('id', $punch->id)->update(['event_type' => 'check_out']);

                AttendanceSession::create([
                    'employee_code'    => $code,
                    'session_date'     => $date,
                    'session_index'    => $index++,
                    'check_in_at'      => $checkIn->punch_time,
                    'check_out_at'     => $punch->punch_time,
                    'duration_minutes' => $mins,
                    'status'           => 'present',
                    'is_overnight'     => false,
                ]);

                $checkIn = null;
            }
        }

        if ($checkIn !== null) {
            $this->createOpenOrOvernightSession($code, $date, $checkIn->punch_time, $index);
        }
    }

    // ── Shared helpers ────────────────────────────────────────────────────────

    /**
     * For past dates: look ahead to the next calendar day for an overnight checkout.
     * For today: create an open in_office session (employee still inside).
     */
    private function createOpenOrOvernightSession(
        string $code, string $date, $checkInTime, int $index
    ): void {
        if (Carbon::parse($date)->lt(today())) {
            $checkInCarbon = Carbon::parse($checkInTime);

            // Only look for overnight checkout if the open check-in was in the evening
            // (after 17:00). Daytime open sessions are treated as missed checkouts.
            $isEveningCheckIn = $checkInCarbon->hour >= 17;

            $overnight = $isEveningCheckIn ? $this->findOvernightCheckout($code, $date) : null;

            // Sanity-check: refuse overnight pairings longer than 16 hours
            if ($overnight !== null) {
                $durationHours = $checkInCarbon->diffInHours(Carbon::parse($overnight->punch_time));
                if ($durationHours > 16) {
                    $overnight = null;
                }
            }

            if ($overnight !== null) {
                $mins = (int) Carbon::parse($checkInTime)->diffInMinutes($overnight->punch_time);
                $overnight->update(['event_type' => 'check_out']);

                AttendanceSession::create([
                    'employee_code'    => $code,
                    'session_date'     => $date,
                    'session_index'    => $index,
                    'check_in_at'      => $checkInTime,
                    'check_out_at'     => $overnight->punch_time,
                    'duration_minutes' => $mins,
                    'status'           => 'present',
                    'is_overnight'     => true,
                ]);

                return;
            }
        }

        AttendanceSession::create([
            'employee_code'    => $code,
            'session_date'     => $date,
            'session_index'    => $index,
            'check_in_at'      => $checkInTime,
            'check_out_at'     => null,
            'duration_minutes' => null,
            'status'           => 'in_office',
            'is_overnight'     => false,
        ]);
    }

    private function findOvernightCheckout(string $code, string $date): ?BiometricAttendance
    {
        $windowStart = Carbon::parse($date)->addDay()->startOfDay();
        $windowEnd   = Carbon::parse($date)->addDay()
            ->setHour(self::OVERNIGHT_WINDOW_HOURS)->setMinute(0)->setSecond(0);

        // Only treat a next-day punch as an overnight checkout if the open session's
        // check_in was LATE in the day (after 6 PM). Earlier check-ins almost certainly
        // mean a missed checkout, not a genuine overnight shift.
        // Also cap the resulting session duration at 16 hours to reject absurd pairings.
        $punch = BiometricAttendance::where('employee_code', $code)
            ->whereBetween('punch_time', [$windowStart, $windowEnd])
            ->whereIn('event_type', ['unknown', 'check_in', 'check_out'])
            ->orderBy('punch_time')
            ->first();

        return (! $punch || $this->isAlreadyOvernightCheckout($code, $punch->punch_time))
            ? null
            : $punch;
    }

    private function overnightCheckoutTimesOnDate(string $code, string $date): Collection
    {
        return AttendanceSession::where('employee_code', $code)
            ->where('is_overnight', true)
            ->where('session_date', Carbon::parse($date)->subDay()->toDateString())
            ->whereNotNull('check_out_at')
            ->pluck('check_out_at')
            ->map(fn ($dt) => Carbon::parse($dt)->toDateTimeString());
    }

    /**
     * Group punches into clusters.
     * A new cluster starts when the gap from the CURRENT cluster's first punch exceeds $windowSecs.
     *
     * @return array<int, array<BiometricAttendance>>
     */
    private function clusterPunches(Collection $punches, int $windowSecs): array
    {
        $clusters     = [];
        $current      = [];
        $clusterStart = null;

        foreach ($punches as $punch) {
            $t = Carbon::parse($punch->punch_time);

            if ($clusterStart === null || $t->diffInSeconds($clusterStart) > $windowSecs) {
                if (! empty($current)) {
                    $clusters[] = $current;
                }
                $clusterStart = $t;
                $current      = [$punch];
            } else {
                $current[] = $punch;
            }
        }

        if (! empty($current)) {
            $clusters[] = $current;
        }

        return $clusters;
    }

    private function isAlreadyOvernightCheckout(string $code, $punchTime): bool
    {
        $dt = Carbon::parse($punchTime)->toDateTimeString();

        return AttendanceSession::where('employee_code', $code)
            ->where('is_overnight', true)
            ->whereNotNull('check_out_at')
            ->get()
            ->contains(fn ($s) => Carbon::parse($s->check_out_at)->toDateTimeString() === $dt);
    }
}
