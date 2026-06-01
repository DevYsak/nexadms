<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attendance Debug</title>
    <style>
        *{box-sizing:border-box}
        body{font-family:Consolas,"Courier New",monospace;background:#0b1220;color:#e2e8f0;margin:0;padding:16px;font-size:13px}
        h1{font-size:18px;margin:0 0 4px;color:#f8fafc}
        .sub{color:#64748b;margin:0 0 16px;font-size:12px}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:10px;margin-bottom:12px}
        .card{background:#111b31;border:1px solid #24324f;border-radius:8px;padding:12px}
        .card.warn{border-color:#b45309}
        .card.ok{border-color:#065f46}
        .title{font-size:11px;color:#93c5fd;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em}
        pre{white-space:pre-wrap;word-break:break-all;margin:0;color:#d1d5db;font-size:12px;max-height:300px;overflow-y:auto}
        .stat{display:flex;justify-content:space-between;padding:3px 0;border-bottom:1px solid #1e2d47}
        .stat:last-child{border-bottom:0}
        .stat .k{color:#94a3b8}
        .stat .v{color:#f1f5f9;font-weight:bold}
        .stat .v.bad{color:#fca5a5}
        .stat .v.good{color:#6ee7b7}
        a{color:#67e8f9;text-decoration:none}
        a:hover{text-decoration:underline}
        .badge{display:inline-block;padding:1px 6px;border-radius:10px;font-size:11px;margin-left:4px}
        .badge.red{background:#7f1d1d;color:#fca5a5}
        .badge.green{background:#064e3b;color:#6ee7b7}
        .log-line{border-bottom:1px solid #1e2d47;padding:4px 0;word-break:break-all}
        .log-line:last-child{border-bottom:0}
        .section-title{font-size:14px;color:#bfdbfe;margin:16px 0 8px;border-bottom:1px solid #1e2d47;padding-bottom:4px}
        .raw-body{background:#0f172a;padding:8px;border-radius:4px;margin-top:6px;max-height:200px;overflow-y:auto;font-size:11px;color:#a5f3fc;word-break:break-all}
        .device-row{display:flex;flex-wrap:wrap;gap:8px;padding:6px 0;border-bottom:1px solid #1e2d47}
        .device-row:last-child{border-bottom:0}
        .chip{background:#1e293b;border:1px solid #334155;border-radius:4px;padding:2px 8px;font-size:11px}
        .chip.bad{border-color:#7f1d1d;color:#fca5a5}
        .chip.good{border-color:#065f46;color:#6ee7b7}
    </style>
</head>
<body>

<h1>Attendance Debug</h1>
<p class="sub">
    JSON:
    <a href="{{ route('attendance.debug', ['format' => 'json']) }}">all devices</a>
    @if($filter_sn)
        &nbsp;|&nbsp; Filtered by SN: <strong>{{ $filter_sn }}</strong>
        <a href="{{ route('attendance.debug') }}">[clear]</a>
    @else
        &nbsp;|&nbsp; Raw logs: <a href="{{ route('attendance.rawlogs') }}">all</a>
    @endif
    &nbsp;|&nbsp; <a href="{{ route('attendance.index') }}">← Dashboard</a>
</p>

{{-- DB Stats --}}
<div class="section-title">Database Stats</div>
<div class="grid">
    <div class="card {{ ($db_stats['attlog_non_empty_zero'] ?? 0) > 0 ? 'warn' : 'ok' }}">
        <div class="title">Attendance Records</div>
        <div class="stat"><span class="k">Total all time</span><span class="v">{{ $db_stats['total_attendance'] }}</span></div>
        <div class="stat"><span class="k">Today</span><span class="v">{{ $db_stats['today_attendance'] }}</span></div>
        <div class="stat"><span class="k">ATTLOG raw logs total</span><span class="v">{{ $db_stats['attlog_total'] }}</span></div>
        <div class="stat"><span class="k">ATTLOGs with records parsed</span><span class="v good">{{ $db_stats['attlog_with_records'] }}</span></div>
        <div class="stat"><span class="k">ATTLOGs with 0 records (any)</span><span class="v">{{ $db_stats['attlog_zero_records'] }}</span></div>
        <div class="stat">
            <span class="k">ATTLOGs non-empty but 0 parsed</span>
            <span class="v {{ ($db_stats['attlog_non_empty_zero'] ?? 0) > 0 ? 'bad' : 'good' }}">
                {{ $db_stats['attlog_non_empty_zero'] }}
                @if(($db_stats['attlog_non_empty_zero'] ?? 0) > 0)
                    <span class="badge red">PARSER ISSUE</span>
                @endif
            </span>
        </div>
    </div>
</div>

{{-- Devices --}}
<div class="section-title">Registered Devices</div>
@if(count($devices) === 0)
    <div class="card"><pre>No devices registered yet.</pre></div>
@else
    <div class="card">
        @foreach($devices as $dev)
        <div class="device-row">
            <span class="chip {{ $dev['is_active'] ? 'good' : 'bad' }}">{{ $dev['is_active'] ? 'ACTIVE' : 'INACTIVE' }}</span>
            <strong>{{ $dev['serial_number'] }}</strong>
            @if($dev['name'])<small style="color:#64748b">{{ $dev['name'] }}</small>@endif
            <span class="chip">IP: {{ $dev['ip_address'] ?? '?' }}</span>
            <span class="chip">Last: {{ $dev['last_activity_at'] ?? 'never' }}</span>
            <span class="chip {{ $dev['today_count'] > 0 ? 'good' : '' }}">Today: {{ $dev['today_count'] }}</span>
            <span class="chip">Total: {{ $dev['attendance_count'] }}</span>
            <span class="chip {{ $dev['attlog_zero_records'] > 0 ? 'bad' : '' }}">0-record ATTLOGs: {{ $dev['attlog_zero_records'] }}</span>
            <a href="{{ route('attendance.debug', ['sn' => $dev['serial_number']]) }}">[filter]</a>
            <a href="{{ route('attendance.rawlogs', ['sn' => $dev['serial_number']]) }}">[raw logs]</a>
        </div>
        @endforeach
    </div>
@endif

{{-- Recent raw ATTLOG payloads --}}
<div class="section-title">Recent ATTLOG Raw Payloads (last 5{{ $filter_sn ? ' for '.$filter_sn : '' }})</div>
<div class="card">
    @forelse($recent_raw_attlogs as $rl)
    <div class="log-line">
        <span style="color:#64748b">#{{ $rl['id'] }}</span>
        <span class="chip">{{ $rl['method'] }}</span>
        <span class="chip">SN: {{ $rl['serial_number'] }}</span>
        <span class="chip {{ $rl['records_parsed'] > 0 ? 'good' : ($rl['body_length'] > 0 ? 'bad' : '') }}">
            parsed: {{ $rl['records_parsed'] }}
            @if($rl['body_length'] > 0 && $rl['records_parsed'] === 0)
                <span class="badge red">!</span>
            @endif
        </span>
        <span class="chip">bytes: {{ $rl['body_length'] }}</span>
        <span style="color:#64748b">{{ $rl['received_at'] }}</span>
        @if($rl['query_string'])<br><span style="color:#475569">?{{ $rl['query_string'] }}</span>@endif
        @if($rl['body_length'] > 0)
        <div class="raw-body">{{ $rl['body_preview'] }}{{ $rl['body_length'] > 300 ? '…' : '' }}</div>
        @else
        <div style="color:#475569;font-size:11px;margin-top:4px">(empty body)</div>
        @endif
    </div>
    @empty
        <pre>No ATTLOG raw logs found{{ $filter_sn ? ' for '.$filter_sn : '' }}.</pre>
    @endforelse
</div>

{{-- Latest parsed attendance --}}
<div class="section-title">Latest 10 Attendance Records (all devices)</div>
<div class="card">
    <pre>{{ json_encode($latest_parsed_attendance, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
</div>

{{-- Log signals --}}
<div class="section-title">Log File Signals</div>
<div class="grid">
    <div class="card">
        <div class="title">Latest DB Insert</div>
        <pre>{{ $latest_db_insert ?? 'N/A' }}</pre>
    </div>
    <div class="card">
        <div class="title">Latest Skipped Duplicate</div>
        <pre>{{ $latest_skipped_duplicate ?? 'N/A' }}</pre>
    </div>
    <div class="card">
        <div class="title">Latest Parser Error</div>
        <pre>{{ $latest_parser_error ?? 'N/A' }}</pre>
    </div>
    <div class="card">
        <div class="title">Latest Skipped Invalid Row</div>
        <pre>{{ $latest_skipped_invalid ?? 'N/A' }}</pre>
    </div>
</div>

{{-- Full body of most recent ATTLOG --}}
<div class="section-title">Full Body — Most Recent ATTLOG{{ $filter_sn ? ' for '.$filter_sn : '' }}</div>
<div class="card {{ $latest_raw_payload && $latest_raw_payload['records_parsed'] == 0 && $latest_raw_payload['body_length'] > 0 ? 'warn' : '' }}">
    @if($latest_raw_payload)
    <div class="stat"><span class="k">ID</span><span class="v">{{ $latest_raw_payload['id'] }}</span></div>
    <div class="stat"><span class="k">Serial Number</span><span class="v">{{ $latest_raw_payload['serial_number'] }}</span></div>
    <div class="stat"><span class="k">Method</span><span class="v">{{ $latest_raw_payload['method'] }}</span></div>
    <div class="stat"><span class="k">Query</span><span class="v">{{ $latest_raw_payload['query_string'] }}</span></div>
    <div class="stat"><span class="k">Records Parsed</span>
        <span class="v {{ $latest_raw_payload['records_parsed'] > 0 ? 'good' : ($latest_raw_payload['body_length'] > 0 ? 'bad' : '') }}">
            {{ $latest_raw_payload['records_parsed'] }}
        </span>
    </div>
    <div class="stat"><span class="k">Body Length</span><span class="v">{{ $latest_raw_payload['body_length'] }} bytes</span></div>
    <div class="stat"><span class="k">Received</span><span class="v">{{ $latest_raw_payload['received_at'] }}</span></div>
    @if($latest_raw_payload['body'])
    <div class="raw-body" style="max-height:400px">{{ $latest_raw_payload['body'] }}</div>
    @else
    <div style="color:#475569;margin-top:8px">(empty body)</div>
    @endif
    @else
    <pre>No ATTLOG raw logs found.</pre>
    @endif
</div>

</body>
</html>
