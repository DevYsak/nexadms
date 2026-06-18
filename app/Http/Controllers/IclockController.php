<?php

namespace App\Http\Controllers;

use App\Models\BiometricAttendance;
use App\Models\BiometricDevice;
use App\Models\BiometricRawLog;
use App\Services\AttendanceRecalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ZKTeco ADMS Push Protocol — iclock endpoint handler.
 *
 * Flow:
 *   1. Device GET  /iclock/cdata?SN=X&options=all   → server sends config + stamp
 *   2. Device POST /iclock/cdata?SN=X&table=ATTLOG  → device pushes attendance logs
 *   3. Server replies OK: N                          → device saves stamp N
 *   4. Device GET  /iclock/getrequest?SN=X          → polls for pending commands
 *   5. Device POST /iclock/devicecmd?SN=X           → replies to commands
 */
class IclockController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Step 1 — Device handshake / registration
    // GET /iclock/cdata?SN=SERIAL&options=all&pushver=2.2.14&language=69
    // ─────────────────────────────────────────────────────────────────────────
    public function cdata(Request $request): Response
    {
        $sn = (string) $request->query('SN', '');

        if ($sn === '') {
            return response('ERROR', 400);
        }

        // POST = device is pushing a table (ATTLOG, OPERLOG, etc.)
        if ($request->isMethod('POST')) {
            return $this->handlePush($request, $sn);
        }

        // GET = device is registering / checking in
        return $this->handleHandshake($request, $sn);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step 4 — Device polls for pending commands
    // GET /iclock/getrequest?SN=SERIAL
    // ─────────────────────────────────────────────────────────────────────────
    public function getrequest(Request $request): Response
    {
        $sn = (string) $request->query('SN', '');

        $device = BiometricDevice::where('serial_number', $sn)->first();
        if ($device) {
            $device->update(['last_activity_at' => now(), 'last_online_at' => now()]);
        }

        // No pending commands → tell device to check back later
        return response("OK\r\n", 200, ['Content-Type' => 'text/plain']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Step 5 — Device replies to a command we sent
    // POST /iclock/devicecmd?SN=SERIAL
    // ─────────────────────────────────────────────────────────────────────────
    public function devicecmd(Request $request): Response
    {
        return response("OK\r\n", 200, ['Content-Type' => 'text/plain']);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function handleHandshake(Request $request, string $sn): Response
    {
        $pushver  = $request->query('pushver', '');
        $firmware = $request->query('ZKFPVersion', $request->query('firmware', ''));

        // Upsert device record
        $device = BiometricDevice::updateOrCreate(
            ['serial_number' => $sn],
            [
                'pushver'          => $pushver,
                'firmware_version' => $firmware ?: null,
                'last_activity_at' => now(),
                'last_online_at'   => now(),
            ]
        );

        $stamp = $device->last_stamp ?? 0;

        // Response format expected by ZKTeco ADMS firmware
        $tz    = config('app.timezone', 'Asia/Kolkata');
        $time  = now()->timezone($tz)->format('Y-m-d H:i:s');

        $body = implode("\r\n", [
            "GET OPTION FROM: {$sn}",
            "ATTLOGStamp={$stamp}",
            "OPERLOGStamp=9999",
            "ATTPHOTOStamp=None",
            "ErrorDelay=30",
            "Delay=10",
            "TransTimes=00:00;14:05",
            "TransInterval=1",
            "TransFlag=TransData AttLog OpLog EnrollUser ChgUser EnrollFP ChgFP UserPic",
            "TimeZone=8",
            "Realtime=1",
            "Encrypt=None",
            "ServerVer=2.2.14 2015-04-14",
            "PushProtVer=2.4.1",
            "PushOptionsFlag=0",
            "SeverPortHTTPS=443",
        ]) . "\r\n";

        Log::channel('stack')->info("[ADMS] Handshake from {$sn} (stamp={$stamp})");

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }

    private function handlePush(Request $request, string $sn): Response
    {
        $table = strtoupper((string) $request->query('table', ''));
        $stamp = (int) $request->query('Stamp', 0);
        $body  = $request->getContent();

        $device = BiometricDevice::where('serial_number', $sn)->first();
        if (! $device) {
            $device = BiometricDevice::create([
                'serial_number'    => $sn,
                'last_activity_at' => now(),
                'last_online_at'   => now(),
                'last_stamp'       => 0,
            ]);
        }

        $device->update(['last_activity_at' => now(), 'last_online_at' => now()]);

        // Store raw log regardless of table — ATTLOG is the attendance table
        $rawLog = BiometricRawLog::create([
            'serial_number'  => $sn,
            'log_type'       => $table,
            'body'           => $body,
            'records_parsed' => 0,
            'received_at'    => now(),
        ]);

        $newStamp = $stamp;

        if ($table === 'ATTLOG' && trim($body) !== '') {
            $newStamp = $this->parseAndStoreAttlog($body, $device, $rawLog);
        }

        // Update stamp so device won't resend these records
        $device->update(['last_stamp' => max($device->last_stamp ?? 0, $newStamp)]);

        Log::channel('stack')->info("[ADMS] {$table} from {$sn}: stamp {$stamp}→{$newStamp}");

        // Reply format: OK: <newStamp>
        return response("OK: {$newStamp}\r\n", 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * Parse ATTLOG body and upsert attendance records.
     *
     * eSSL AiFace Magnum / ZKTeco ATTLOG line format (tab OR space separated):
     *
     *   PIN   DateTime               Verify  InOut  WorkCode  Reserved
     *   5     2026-06-03 20:36:22    255     1      0         0
     *
     *   parts[0]  PIN          — employee code
     *   parts[1]  Date         — YYYY-MM-DD
     *   parts[2]  Time         — HH:MM:SS  (split because of space in DateTime)
     *   parts[3]  Verify       — biometric method: 1=FP, 15=Card, 255=Face
     *   parts[4]  InOut        — direction: 0=in, 1=out, 4=OT-in, 5=OT-out
     *   parts[5]  WorkCode
     *   parts[6]  Reserved
     *
     * Returns the new stamp (total records received including this batch).
     */
    private function parseAndStoreAttlog(string $body, BiometricDevice $device, BiometricRawLog $rawLog): int
    {
        $lines   = preg_split('/\r?\n/', trim($body));
        $count   = 0;
        $dates   = [];

        DB::transaction(function () use ($lines, $device, &$count, &$dates) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;

                // Handles tab-separated and space-separated firmware variants
                $parts = preg_split('/[\t ]+/', $line);
                if (count($parts) < 3) continue;

                $pin     = trim($parts[0]);
                $rawDate = trim($parts[1]);
                $rawTime = isset($parts[2]) && strlen($parts[2]) > 3 ? trim($parts[2]) : '';

                // parts[3] = Verify type (biometric method)
                $verifyType = (int) ($parts[3] ?? 0);

                // parts[4] = InOut direction from device
                // 0 = check-in, 1 = check-out, 4 = OT check-in, 5 = OT check-out
                $inOut          = (int) ($parts[4] ?? -1);
                $punchDirection = match ($inOut) {
                    0, 4    => 'in',
                    1, 5    => 'out',
                    default => 'unknown',   // field absent (older firmware)
                };

                $dateTimeStr = $rawTime ? "{$rawDate} {$rawTime}" : $rawDate;

                try {
                    // Device sends time in its local timezone (IST = Asia/Kolkata).
                    // Parse explicitly so storage is timezone-aware.
                    $punchTime = Carbon::createFromFormat('Y-m-d H:i:s', $dateTimeStr, 'Asia/Kolkata');
                } catch (\Throwable) {
                    continue;
                }

                // firstOrCreate: deduplication is by (employee_code, punch_time, device_id).
                // Direction and verify_type are set only on INSERT — never overwritten on
                // subsequent pushes so an admin correction to event_type is preserved.
                BiometricAttendance::firstOrCreate(
                    [
                        'employee_code' => $pin,
                        'punch_time'    => $punchTime->toDateTimeString(),
                        'device_id'     => $device->id,
                    ],
                    [
                        'verify_type'     => $verifyType,
                        'punch_direction' => $punchDirection,
                        'event_type'      => 'unknown',
                    ]
                );

                $dates[$punchTime->toDateString()] = true;
                $count++;
            }
        });

        // Update raw log record count
        $rawLog->update(['records_parsed' => $count]);

        // Trigger session recalculation for affected dates (async-friendly: just delete
        // existing sessions so gridApi auto-recalculates on next page load)
        if ($count > 0) {
            $svc  = app(AttendanceRecalculationService::class);
            $codes = BiometricAttendance::whereIn(
                DB::raw('DATE(punch_time)'), array_keys($dates)
            )->distinct('employee_code')->pluck('employee_code');

            foreach (array_keys($dates) as $date) {
                $dayCodes = BiometricAttendance::whereDate('punch_time', $date)
                    ->distinct('employee_code')
                    ->pluck('employee_code');
                foreach ($dayCodes as $code) {
                    $svc->recalculate($code, $date);
                }
            }
        }

        return ($device->last_stamp ?? 0) + $count;
    }
}
