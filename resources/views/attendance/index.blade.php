<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attendance Log</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(180deg, #f7f4ef 0%, #eef2f6 100%); color: #1f2937; }
        .container { max-width: 1200px; margin: 0 auto; padding: 24px 16px 40px; }
        h1 { font-size: 1.8rem; font-weight: 700; margin-bottom: 12px; color: #1f2937; }
        .subhead { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
        .status-pill { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; background: #fff; box-shadow: 0 10px 30px rgba(31, 41, 55, 0.08); color: #4b5563; font-size: .82rem; }
        .dot { width: 10px; height: 10px; border-radius: 999px; background: #16a34a; box-shadow: 0 0 0 6px rgba(22, 163, 74, 0.12); animation: pulse 1.8s infinite; }
        @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 70% { transform: scale(1.1); opacity: .75; } 100% { transform: scale(1); opacity: 1; } }

        .filters { background: rgba(255,255,255,.92); backdrop-filter: blur(8px); border-radius: 14px; padding: 16px; margin-bottom: 20px; display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; box-shadow: 0 10px 30px rgba(31, 41, 55, 0.08); border: 1px solid rgba(255,255,255,.65); }
        .field { display: flex; flex-direction: column; gap: 4px; min-width: 180px; }
        label { font-size: .75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: .05em; }
        input, select { padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .92rem; background: #fff; color: #111827; }
        input:focus, select:focus { outline: 2px solid #0891b2; outline-offset: 1px; border-color: #0891b2; }
        .btn { padding: 10px 18px; border-radius: 8px; font-size: .9rem; font-weight: 700; cursor: pointer; border: none; }
        .btn-primary { background: linear-gradient(135deg, #0891b2, #0f766e); color: #fff; }
        .btn-secondary { background: #fff; color: #374151; border: 1px solid #d1d5db; text-decoration: none; display: inline-block; }

        .stats { display: grid; gap: 12px; margin-bottom: 20px; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); }
        .stat { background: #fff; border-radius: 14px; padding: 16px 18px; box-shadow: 0 10px 30px rgba(31, 41, 55, 0.08); }
        .stat-value { font-size: 1.9rem; font-weight: 800; color: #0f172a; }
        .stat-label { font-size: .76rem; color: #6b7280; margin-top: 4px; text-transform: uppercase; letter-spacing: .05em; }

        .card { background: rgba(255,255,255,.94); border-radius: 16px; box-shadow: 0 10px 30px rgba(31, 41, 55, 0.08); overflow: hidden; border: 1px solid rgba(255,255,255,.65); }
        table { width: 100%; border-collapse: collapse; }
        thead th { background: #f8fafc; padding: 12px 14px; text-align: left; font-size: .74rem; font-weight: 800; color: #6b7280; text-transform: uppercase; letter-spacing: .06em; border-bottom: 1px solid #e5e7eb; }
        tbody tr:hover { background: #f8fafc; }
        tbody td { padding: 14px; font-size: .9rem; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: none; }

        .badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 999px; font-size: .75rem; font-weight: 700; }
        .badge-in    { background: #dcfce7; color: #166534; }
        .badge-out   { background: #fee2e2; color: #b91c1c; }
        .badge-break { background: #fef3c7; color: #92400e; }
        .badge-ot    { background: #e0f2fe; color: #0c4a6e; }
        .badge-punch { background: #f3e8ff; color: #7c3aed; } /* punch_state=255 AIFACE-MAGNUM */

        .verify { color: #4b5563; }
        .muted { color: #9ca3af; font-size: .8rem; }
        .empty { text-align: center; padding: 48px; color: #9ca3af; font-size: .95rem; }
        .footer-note { text-align: center; color: #9ca3af; font-size: .78rem; margin-top: 16px; }

        @media (max-width: 768px) {
            thead { display: none; }
            table, tbody, tr, td { display: block; width: 100%; }
            tbody tr { border-bottom: 1px solid #e5e7eb; padding: 8px 0; }
            tbody td { border-bottom: none; padding: 6px 14px; }
            tbody td::before { content: attr(data-label); display: block; font-size: .7rem; font-weight: 800; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 2px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="subhead">
        <div>
            <h1>Attendance Log</h1>
            <p class="muted">Live biometric attendance with auto-refresh every 5 seconds.</p>
        </div>
        <div class="status-pill">
            <span class="dot"></span>
            <span id="sync-status">Live sync active</span>
            <span id="last-sync" class="muted">Waiting for next refresh</span>
        </div>
    </div>

    <form method="GET" action="{{ route('attendance.index') }}">
        <div class="filters">
            <div class="field">
                <label>Date</label>
                <input type="date" name="date" value="{{ request('date', today()->toDateString()) }}">
            </div>
            <div class="field">
                <label>Employee Code</label>
                <input type="text" name="employee" value="{{ request('employee') }}" placeholder="All employees">
            </div>
            <div class="field">
                <label>Device</label>
                <select name="device">
                    <option value="">All devices</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}" {{ request('device') == $device->id ? 'selected' : '' }}>
                            {{ $device->serial_number }}{{ $device->name ? ' - '.$device->name : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('attendance.index') }}" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <div class="stats">
        <div class="stat">
            <div class="stat-value" id="stat-total">{{ $summary['total_punches'] }}</div>
            <div class="stat-label">Total Punches</div>
        </div>
        <div class="stat">
            <div class="stat-value" id="stat-checkins">{{ $summary['check_ins'] }}</div>
            <div class="stat-label">Check-Ins On Page</div>
        </div>
        <div class="stat">
            <div class="stat-value" id="stat-checkouts">{{ $summary['check_outs'] }}</div>
            <div class="stat-label">Check-Outs On Page</div>
        </div>
        <div class="stat">
            <div class="stat-value" id="stat-unique">{{ $summary['unique_employees'] }}</div>
            <div class="stat-label">Unique Employees On Page</div>
        </div>
    </div>

    <div class="card">
        @if ($logs->isEmpty())
            <div class="empty" id="attendance-empty">No attendance records found for the selected filters.</div>
        @else
            <table>
                <thead>
                <tr>
                    <th>#</th>
                    <th>Employee Code</th>
                    <th>Punch Time</th>
                    <th>Type</th>
                    <th>Verify Method</th>
                    <th>Device</th>
                </tr>
                </thead>
                <tbody id="attendance-body">
                @foreach ($logs as $log)
                    @php
                        $stateClasses = [0=>'badge-in',1=>'badge-out',2=>'badge-break',3=>'badge-break',4=>'badge-ot',5=>'badge-ot',255=>'badge-punch'];
                    @endphp
                    <tr>
                        <td data-label="#" class="muted">{{ $log->id }}</td>
                        <td data-label="Employee Code"><strong>{{ $log->employee_code }}</strong></td>
                        <td data-label="Punch Time">{{ \Carbon\Carbon::parse($log->punch_time)->setTimezone(config('biometric.timezone', 'Asia/Kolkata'))->format('d M Y H:i:s') }}</td>
                        <td data-label="Type">
                            <span class="badge {{ $stateClasses[$log->punch_state] ?? 'badge-in' }}">{{ $log->punch_state_label }}</span>
                        </td>
                        <td data-label="Verify Method" class="verify">{{ $log->verify_type_label }}</td>
                        <td data-label="Device" class="muted">{{ $log->device?->serial_number ?? '-' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            @if ($logs->hasPages())
                <div class="pagination">
                    {{ $logs->links() }}
                </div>
            @endif
        @endif
    </div>

    <p class="footer-note" id="attendance-summary">
        Showing {{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} records
    </p>
</div>

<script>
    const feedUrl = @json($feedUrl);
    const attendanceBody = document.getElementById('attendance-body');
    const attendanceEmpty = document.getElementById('attendance-empty');
    const summaryEl = document.getElementById('attendance-summary');
    const lastSyncEl = document.getElementById('last-sync');
    const syncStatusEl = document.getElementById('sync-status');
    const stateClasses = {
        0: 'badge-in',
        1: 'badge-out',
        2: 'badge-break',
        3: 'badge-break',
        4: 'badge-ot',
        5: 'badge-ot',
        255: 'badge-punch'   // AIFACE-MAGNUM: direction unassigned
    };

    async function refreshAttendance() {
        try {
            const response = await fetch(feedUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const payload = await response.json();

            document.getElementById('stat-total').textContent = payload.summary.total_punches;
            document.getElementById('stat-checkins').textContent = payload.summary.check_ins;
            document.getElementById('stat-checkouts').textContent = payload.summary.check_outs;
            document.getElementById('stat-unique').textContent = payload.summary.unique_employees;

            if (attendanceBody) {
                if (payload.rows.length === 0) {
                    attendanceBody.innerHTML = '';
                    if (attendanceEmpty) {
                        attendanceEmpty.style.display = 'block';
                    }
                } else {
                    if (attendanceEmpty) {
                        attendanceEmpty.style.display = 'none';
                    }

                    attendanceBody.innerHTML = payload.rows.map((row) => `
                        <tr>
                            <td data-label="#" class="muted">${row.id}</td>
                            <td data-label="Employee Code"><strong>${row.employee_code}</strong></td>
                            <td data-label="Punch Time">${row.punch_time}</td>
                            <td data-label="Type"><span class="badge ${stateClasses[row.punch_state] || 'badge-in'}">${row.punch_state_label}</span></td>
                            <td data-label="Verify Method" class="verify">${row.verify_type_label}</td>
                            <td data-label="Device" class="muted">${row.device_serial_number}</td>
                        </tr>
                    `).join('');
                }
            }

            summaryEl.textContent = `Showing ${payload.pagination.first_item ?? 0}-${payload.pagination.last_item ?? 0} of ${payload.pagination.total} records`;
            syncStatusEl.textContent = 'Live sync active';
            lastSyncEl.textContent = `Updated ${new Date(payload.server_time).toLocaleTimeString()}`;
        } catch (error) {
            syncStatusEl.textContent = 'Live sync retrying';
            lastSyncEl.textContent = error.message;
        }
    }

    refreshAttendance();
    setInterval(refreshAttendance, 5000);
</script>
</body>
</html>
