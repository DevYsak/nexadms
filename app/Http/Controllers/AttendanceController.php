<?php

namespace App\Http\Controllers;

use App\Models\BiometricAttendance;
use App\Models\BiometricDevice;
use App\Models\BiometricRawLog;
use App\Services\AdmsParserService;
use App\Services\AttendanceStoreService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AdmsParserService $parser,
        private readonly AttendanceStoreService $store,
    ) {
    }

    public function index(Request $request)
    {
        $logs = $this->attendanceQuery($request)->paginate(50)->withQueryString();
        $devices = BiometricDevice::orderBy('name')->get();
        $summary = $this->buildSummary(clone $logs->getCollection());
        $feedUrl = route('attendance.feed', $this->filterParams($request));

        return view('attendance.index', compact('logs', 'devices', 'summary', 'feedUrl'));
    }

    public function feed(Request $request): JsonResponse
    {
        $logs = $this->attendanceQuery($request)->paginate(50)->withQueryString();

        return response()->json([
            'summary' => $this->buildSummary(clone $logs->getCollection(), $logs->total()),
            'rows' => $logs->getCollection()->map(fn (BiometricAttendance $log) => $this->transformAttendance($log))->values(),
            'pagination' => [
                'total' => $logs->total(),
                'first_item' => $logs->firstItem(),
                'last_item' => $logs->lastItem(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
            ],
            'server_time' => now()->timezone(config('biometric.timezone', 'Asia/Kolkata'))->toIso8601String(),
        ]);
    }

    public function testAttendance(Request $request): JsonResponse
    {
        $serialNumber = (string) $request->input('serial_number', 'TEST-DEVICE-001');
        $device = BiometricDevice::findOrRegister($serialNumber, [
            'ip_address' => $request->ip(),
            'name' => 'Test-'.$serialNumber,
        ]);

        $employeeCode = (string) $request->input('employee_code', '1001');
        $punchState = (int) $request->input('punch_state', 0);
        $verifyType = (int) $request->input('verify_type', 1);
        $punchTime = (string) $request->input(
            'punch_time',
            now()->timezone(config('biometric.timezone', 'Asia/Kolkata'))->format('Y-m-d H:i:s')
        );

        $payload = (string) $request->input(
            'payload',
            implode("\t", [$employeeCode, $punchTime, $punchState, $verifyType, 0, 0])
        );

        $parsed = $this->parser->parseAttLog($payload);
        $counts = $this->store->storeBatch($device, $parsed['records']);

        return response()->json([
            'ok' => true,
            'device' => [
                'id' => $device->id,
                'serial_number' => $device->serial_number,
            ],
            'payload' => $payload,
            'parsed_records' => $parsed['records'],
            'store_counts' => $counts,
        ]);
    }

    public function testAttendanceBulk(Request $request): JsonResponse
    {
        $serialNumber = (string) $request->input('serial_number', 'TEST-DEVICE-001');
        $device = BiometricDevice::findOrRegister($serialNumber, [
            'ip_address' => $request->ip(),
            'name' => 'Test-'.$serialNumber,
        ]);

        $employeeCode = (string) $request->input('employee_code', '1001');
        $count = max(1, min(100, (int) $request->input('count', 10)));
        $interval = max(1, min(60, (int) $request->input('interval_seconds', 3)));
        $startTime = Carbon::parse(
            (string) $request->input('start_time', now()->timezone(config('biometric.timezone', 'Asia/Kolkata'))->format('Y-m-d H:i:s')),
            config('biometric.timezone', 'Asia/Kolkata')
        );

        $lines = [];
        for ($i = 0; $i < $count; $i++) {
            $punchState = $i % 2; // alternate IN/OUT
            $verifyType = [1, 2, 10][$i % 3]; // rotate fingerprint/card/face
            $time = $startTime->copy()->addSeconds($i * $interval)->format('Y-m-d H:i:s');
            $lines[] = implode("\t", [$employeeCode, $time, $punchState, $verifyType, 0, 0]);
        }

        $payload = implode("\n", $lines);
        $parsed = $this->parser->parseAttLog($payload);
        $counts = $this->store->storeBatch($device, $parsed['records']);

        return response()->json([
            'ok' => true,
            'device' => [
                'id' => $device->id,
                'serial_number' => $device->serial_number,
            ],
            'count_requested' => $count,
            'payload_preview' => mb_substr($payload, 0, 500),
            'parsed_count' => count($parsed['records']),
            'store_counts' => $counts,
        ]);
    }

    public function debug(Request $request)
    {
        $sn = (string) $request->query('sn', '');

        $rawQuery = BiometricRawLog::query()->where('log_type', 'ATTLOG');
        if ($sn !== '') {
            $rawQuery->where('serial_number', $sn);
        }
        $latestRaw = $rawQuery->latest('id')->first();

        // Last 5 raw ATTLOG logs for this device (or all devices)
        $recentRawLogs = (clone $rawQuery)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (BiometricRawLog $r) => [
                'id' => $r->id,
                'serial_number' => $r->serial_number,
                'method' => $r->method,
                'query_string' => $r->query_string,
                'records_parsed' => $r->records_parsed,
                'body_length' => strlen((string) $r->body),
                'body_preview' => mb_substr((string) $r->body, 0, 300),
                'received_at' => optional($r->received_at)->toDateTimeString(),
            ])
            ->values();

        $latestRows = BiometricAttendance::query()
            ->with('device')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (BiometricAttendance $row) => $this->transformAttendance($row))
            ->values();

        $latestLogInfo = $this->latestBiometricLogSignals();

        // Per-device summary
        $devices = BiometricDevice::all()->map(fn (BiometricDevice $d) => [
            'id' => $d->id,
            'serial_number' => $d->serial_number,
            'name' => $d->name,
            'ip_address' => $d->ip_address,
            'last_activity_at' => optional($d->last_activity_at)->toDateTimeString(),
            'is_active' => (bool) $d->is_active,
            'attendance_count' => BiometricAttendance::where('device_id', $d->id)->count(),
            'today_count' => BiometricAttendance::where('device_id', $d->id)
                ->whereDate('punch_time', today())->count(),
            'raw_log_count' => BiometricRawLog::where('serial_number', $d->serial_number)->count(),
            'attlog_zero_records' => BiometricRawLog::where('serial_number', $d->serial_number)
                ->where('log_type', 'ATTLOG')
                ->where('records_parsed', 0)
                ->count(),
        ]);

        // DB verification stats
        $dbStats = [
            'total_attendance' => BiometricAttendance::count(),
            'today_attendance' => BiometricAttendance::whereDate('punch_time', today())->count(),
            'total_raw_logs' => BiometricRawLog::count(),
            'attlog_total' => BiometricRawLog::where('log_type', 'ATTLOG')->count(),
            'attlog_with_records' => BiometricRawLog::where('log_type', 'ATTLOG')
                ->where('records_parsed', '>', 0)->count(),
            'attlog_zero_records' => BiometricRawLog::where('log_type', 'ATTLOG')
                ->where('records_parsed', 0)->count(),
            'attlog_non_empty_zero' => BiometricRawLog::where('log_type', 'ATTLOG')
                ->where('records_parsed', 0)
                ->where('body', '!=', '')
                ->whereNotNull('body')
                ->count(),
        ];

        $payload = [
            'filter_sn' => $sn ?: null,
            'latest_raw_payload' => $latestRaw ? [
                'id' => $latestRaw->id,
                'serial_number' => $latestRaw->serial_number,
                'method' => $latestRaw->method,
                'query_string' => $latestRaw->query_string,
                'records_parsed' => $latestRaw->records_parsed,
                'body_length' => strlen((string) $latestRaw->body),
                'received_at' => optional($latestRaw->received_at)->toDateTimeString(),
                'body' => $latestRaw->body,
            ] : null,
            'recent_raw_attlogs' => $recentRawLogs,
            'latest_parsed_attendance' => $latestRows,
            'devices' => $devices,
            'db_stats' => $dbStats,
            'latest_skipped_duplicate' => $latestLogInfo['skipped_duplicate'],
            'latest_db_insert' => $latestLogInfo['inserted'],
            'latest_skipped_invalid' => $latestLogInfo['skipped_invalid'],
            'latest_parser_error' => $latestLogInfo['parser_error'],
        ];

        if ($request->expectsJson() || $request->query('format') === 'json') {
            return response()->json($payload);
        }

        return view('attendance.debug', $payload);
    }

    public function rawLogs(Request $request): JsonResponse
    {
        $sn = (string) $request->query('sn', '');
        $logType = strtoupper((string) $request->query('type', 'ATTLOG'));
        $limit = max(1, min(50, (int) $request->query('limit', 20)));

        $query = BiometricRawLog::query()->where('log_type', $logType);
        if ($sn !== '') {
            $query->where('serial_number', $sn);
        }

        $rows = $query->latest('id')->limit($limit)->get()->map(fn (BiometricRawLog $r) => [
            'id' => $r->id,
            'serial_number' => $r->serial_number,
            'method' => $r->method,
            'query_string' => $r->query_string,
            'records_parsed' => $r->records_parsed,
            'body_length' => strlen((string) $r->body),
            'body' => $r->body,
            'headers' => $r->headers,
            'response_code' => $r->response_code,
            'received_at' => optional($r->received_at)->toDateTimeString(),
        ]);

        return response()->json([
            'filter' => ['sn' => $sn ?: null, 'type' => $logType, 'limit' => $limit],
            'count' => $rows->count(),
            'rows' => $rows,
        ]);
    }

    private function attendanceQuery(Request $request)
    {
        $query = BiometricAttendance::with('device')->orderByDesc('punch_time')->orderByDesc('id');

        if ($request->filled('date')) {
            $query->whereDate('punch_time', $request->date);
        } else {
            $query->whereDate('punch_time', today());
        }

        if ($request->filled('employee')) {
            $query->where('employee_code', $request->employee);
        }

        if ($request->filled('device')) {
            $query->where('device_id', $request->device);
        }

        return $query;
    }

    private function buildSummary($collection, ?int $total = null): array
    {
        $total ??= $collection->count();

        return [
            'total_punches' => $total,
            'check_ins' => $collection->where('punch_state', 0)->count(),
            'check_outs' => $collection->where('punch_state', 1)->count(),
            'unique_employees' => $collection->unique('employee_code')->count(),
        ];
    }

    private function transformAttendance(BiometricAttendance $log): array
    {
        return [
            'id' => $log->id,
            'employee_code' => $log->employee_code,
            // Do NOT call ->timezone() here.
            // punch_time is stored as the device's local wall-clock time (IST).
            // With APP_TIMEZONE=Asia/Kolkata the Carbon is already in IST; ->format() is correct as-is.
            'punch_time' => Carbon::parse($log->punch_time)->format('d M Y H:i:s'),
            'punch_state' => (int) $log->punch_state,
            'punch_state_label' => $log->punch_state_label,
            'verify_type' => (int) $log->verify_type,
            'verify_type_label' => $log->verify_type_label,
            'device_serial_number' => $log->device?->serial_number ?? '-',
        ];
    }

    private function filterParams(Request $request): array
    {
        return array_filter([
            'date' => $request->input('date'),
            'employee' => $request->input('employee'),
            'device' => $request->input('device'),
            'page' => $request->input('page'),
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function latestBiometricLogSignals(): array
    {
        $logFile = $this->resolveLatestBiometricLogFile();
        if ($logFile === null || ! File::exists($logFile)) {
            return [
                'skipped_duplicate' => null,
                'inserted' => null,
                'skipped_invalid' => null,
                'parser_error' => null,
            ];
        }

        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $lines = array_reverse($lines);

        $findLatest = function (string $needle) use ($lines): ?string {
            foreach ($lines as $line) {
                if (str_contains($line, $needle)) {
                    return $line;
                }
            }
            return null;
        };

        return [
            'skipped_duplicate' => $findLatest('Skipped duplicate'),
            'inserted' => $findLatest('Inserted attendance'),
            'skipped_invalid' => $findLatest('Skipped invalid row'),
            'parser_error' => $findLatest('Parser skipped record'),
        ];
    }

    private function resolveLatestBiometricLogFile(): ?string
    {
        $paths = glob(storage_path('logs/biometric*.log')) ?: [];
        if ($paths === []) {
            return null;
        }

        usort($paths, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));
        return $paths[0] ?? null;
    }
}
