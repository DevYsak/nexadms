<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>BioSync — Attendance</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; }
  html, body { height: 100%; margin: 0; }
  body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #F8FAFC; color: #0F172A; }

  /* Scrollbars */
  ::-webkit-scrollbar { width: 5px; height: 5px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 99px; }
  ::-webkit-scrollbar-thumb:hover { background: #CBD5E1; }

  /* Sidebar nav active */
  .nav-item { display:flex; align-items:center; gap:10px; padding:7px 12px; border-radius:8px; font-size:13.5px; font-weight:500; color:#64748B; cursor:pointer; transition:all .15s; border:none; background:none; width:100%; text-align:left; }
  .nav-item:hover { background:#F1F5F9; color:#0F172A; }
  .nav-item.active { background:#EFF6FF; color:#2563EB; font-weight:600; }
  .nav-item svg { flex-shrink:0; opacity:.7; }
  .nav-item.active svg { opacity:1; }

  /* KPI Cards */
  .kpi-card { background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:20px; display:flex; flex-direction:column; gap:12px; transition:box-shadow .15s; }
  .kpi-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.06); }

  /* Table */
  .data-table { width:100%; border-collapse:collapse; font-size:13.5px; }
  .data-table thead th { padding:10px 16px; text-align:left; font-size:11px; font-weight:600; color:#64748B; text-transform:uppercase; letter-spacing:.04em; background:#F8FAFC; border-bottom:1px solid #E5E7EB; white-space:nowrap; position:sticky; top:0; z-index:10; }
  .data-table tbody tr { border-bottom:1px solid #F1F5F9; transition:background .1s; cursor:pointer; }
  .data-table tbody tr:last-child { border-bottom:none; }
  .data-table tbody tr:hover { background:#F8FAFC; }
  .data-table tbody tr.selected { background:#EFF6FF; }
  .data-table td { padding:12px 16px; vertical-align:middle; }

  /* Status badges */
  .badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:99px; font-size:11.5px; font-weight:600; white-space:nowrap; }
  .badge-green  { background:#ECFDF5; color:#059669; }
  .badge-blue   { background:#EFF6FF; color:#2563EB; }
  .badge-amber  { background:#FFFBEB; color:#D97706; }
  .badge-red    { background:#FEF2F2; color:#DC2626; }
  .badge-orange { background:#FFF7ED; color:#EA580C; }
  .badge-gray   { background:#F1F5F9; color:#64748B; }

  /* Pill dot */
  .dot { width:6px; height:6px; border-radius:50%; display:inline-block; flex-shrink:0; }
  .dot-green  { background:#10B981; }
  .dot-blue   { background:#2563EB; }
  .dot-amber  { background:#F59E0B; }
  .dot-red    { background:#EF4444; }
  .dot-gray   { background:#94A3B8; }

  /* Pulse */
  @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.35} }
  .pulse { animation:pulse 2s infinite; }

  /* Fade in rows */
  @keyframes fadeUp { from{opacity:0;transform:translateY(3px)} to{opacity:1;transform:none} }
  .fade-up { animation:fadeUp .2s ease both; }

  /* Filter toolbar inputs */
  .tb-input { height:36px; border:1px solid #E5E7EB; border-radius:8px; padding:0 12px; font-size:13px; color:#0F172A; background:#fff; outline:none; font-family:inherit; }
  .tb-input:focus { border-color:#2563EB; box-shadow:0 0 0 3px rgba(37,99,235,.1); }
  .tb-btn-primary { height:36px; padding:0 16px; background:#2563EB; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; font-family:inherit; transition:background .15s; }
  .tb-btn-primary:hover { background:#1D4ED8; }
  .tb-btn-ghost { height:36px; padding:0 14px; background:#fff; color:#64748B; border:1px solid #E5E7EB; border-radius:8px; font-size:13px; font-weight:500; cursor:pointer; font-family:inherit; transition:all .15s; }
  .tb-btn-ghost:hover { background:#F8FAFC; color:#0F172A; }
  .tb-btn-export { height:36px; padding:0 14px; background:#fff; color:#059669; border:1px solid #D1FAE5; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; font-family:inherit; display:inline-flex; align-items:center; gap:6px; transition:all .15s; }
  .tb-btn-export:hover { background:#ECFDF5; }

  /* Avatar */
  .avatar { width:32px; height:32px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#fff; flex-shrink:0; }

  /* Timeline event dot connector */
  .tl-connector { width:1px; flex:1; background:#E5E7EB; min-height:12px; margin:3px 0; }

  /* Section label */
  .section-label { font-size:10.5px; font-weight:700; color:#94A3B8; text-transform:uppercase; letter-spacing:.07em; margin-bottom:8px; }

  /* Session card */
  .session-card { background:#F8FAFC; border:1px solid #E5E7EB; border-radius:10px; padding:12px 14px; }

  /* Pagination */
  .pg-btn { min-width:32px; height:32px; padding:0 8px; border:1px solid #E5E7EB; border-radius:7px; background:#fff; font-size:12.5px; color:#64748B; cursor:pointer; font-family:inherit; transition:all .15s; }
  .pg-btn:hover:not(:disabled) { background:#F8FAFC; color:#0F172A; }
  .pg-btn.active { background:#2563EB; border-color:#2563EB; color:#fff; font-weight:600; }
  .pg-btn:disabled { opacity:.4; cursor:not-allowed; }

  /* Spin */
  @keyframes spin { to{transform:rotate(360deg)} }
  .spin { animation:spin .6s linear infinite; }

  /* Card shadow */
  .card { background:#fff; border:1px solid #E5E7EB; border-radius:12px; }
</style>
</head>

<body>
<div style="display:flex;height:100vh;overflow:hidden;">

  {{-- ═══════════════════════════ SIDEBAR ═══════════════════════════ --}}
  <aside style="width:220px;flex-shrink:0;background:#fff;border-right:1px solid #E5E7EB;display:flex;flex-direction:column;overflow-y:auto;">

    {{-- Logo --}}
    <div style="padding:18px 16px 14px;border-bottom:1px solid #F1F5F9;">
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#2563EB,#7C3AED);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="16" height="16" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/></svg>
        </div>
        <div>
          <div style="font-size:14px;font-weight:700;color:#0F172A;line-height:1.2;">BioSync</div>
          <div style="font-size:11px;color:#94A3B8;margin-top:1px;">Attendance System</div>
        </div>
      </div>
    </div>

    {{-- Navigation --}}
    <nav style="flex:1;padding:10px 10px;">
      @php
        $nav = [
          ['id'=>'nav-dash', 'label'=>'Dashboard',    'icon'=>'home'],
          ['id'=>'nav-log',  'label'=>'Attendance',   'icon'=>'list',  'active'=>true],
          ['id'=>'nav-live', 'label'=>'Live Monitor', 'icon'=>'activity'],
          ['id'=>'nav-emp',  'label'=>'Employees',    'icon'=>'users'],
          ['id'=>'nav-dev',  'label'=>'Devices',      'icon'=>'cpu'],
          ['id'=>'nav-rep',  'label'=>'Reports',      'icon'=>'bar-chart'],
          ['id'=>'nav-sync', 'label'=>'Sync Status',  'icon'=>'refresh-cw'],
          ['id'=>'nav-set',  'label'=>'Settings',     'icon'=>'settings'],
        ];
      @endphp
      @foreach($nav as $item)
      <button onclick="setNav('{{ $item['id'] }}')" id="{{ $item['id'] }}"
        class="nav-item {{ ($item['active'] ?? false) ? 'active' : '' }}">
        @if($item['icon']==='home')
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        @elseif($item['icon']==='list')
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        @elseif($item['icon']==='activity')
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        @elseif($item['icon']==='users')
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
        @elseif($item['icon']==='cpu')
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 1v3M15 1v3M9 20v3M15 20v3M1 9h3M1 15h3M20 9h3M20 15h3"/></svg>
        @elseif($item['icon']==='bar-chart')
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        @elseif($item['icon']==='refresh-cw')
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
        @else
          <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 0 0 4.93 19.07"/><path d="M19.07 19.07A10 10 0 0 0 4.93 4.93"/></svg>
        @endif
        {{ $item['label'] }}
      </button>
      @endforeach
    </nav>

    {{-- Sync Widget --}}
    <div style="padding:12px 10px 14px;border-top:1px solid #F1F5F9;">
      <div style="background:#F8FAFC;border:1px solid #E5E7EB;border-radius:10px;padding:12px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
          <span style="font-size:10.5px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.06em;">Device Sync</span>
          <span class="dot dot-green pulse"></span>
        </div>
        <div id="sidebar-sync-status" style="font-size:12px;color:#64748B;">Loading…</div>
        <div style="margin-top:8px;font-size:11px;color:#94A3B8;text-align:center;">
          Next sync in <span id="countdown" style="font-weight:600;color:#2563EB;font-variant-numeric:tabular-nums;">0:05</span>
        </div>
      </div>
    </div>
  </aside>

  {{-- ═══════════════════════════ MAIN ═══════════════════════════ --}}
  <div style="flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;">

    {{-- Top Header --}}
    <header style="background:#fff;border-bottom:1px solid #E5E7EB;padding:0 24px;height:56px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;gap:16px;">
      <div style="display:flex;align-items:center;gap:8px;">
        <h1 style="font-size:15px;font-weight:700;color:#0F172A;margin:0;">Attendance</h1>
        <span style="font-size:12px;color:#94A3B8;font-weight:400;">/ Daily Log</span>
      </div>
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="display:flex;align-items:center;gap:6px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:99px;padding:4px 12px;">
          <span class="dot dot-green pulse"></span>
          <span style="font-size:12px;font-weight:600;color:#16A34A;">Live</span>
          <span style="font-size:12px;color:#64748B;" id="last-sync-time">just now</span>
        </div>
        <input type="date" id="date-filter" value="{{ $date }}" class="tb-input" style="width:148px;">
      </div>
    </header>

    {{-- Scrollable body --}}
    <div style="flex:1;overflow-y:auto;padding:20px 24px;display:flex;flex-direction:column;gap:16px;">

      {{-- KPI Row --}}
      <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px;">
        @php
          $kpis = [
            ['id'=>'stat-total-emp','label'=>'Total Employees','val'=>$stats['total_employees'],'color'=>'#2563EB','bg'=>'#EFF6FF','icon'=>'users'],
            ['id'=>'stat-present',  'label'=>'Present Today',  'val'=>$stats['present'],        'color'=>'#059669','bg'=>'#ECFDF5','icon'=>'check'],
            ['id'=>'stat-absent',   'label'=>'Absent Today',   'val'=>$stats['absent'],         'color'=>'#DC2626','bg'=>'#FEF2F2','icon'=>'x'],
            ['id'=>'stat-late',     'label'=>'Late Arrivals',  'val'=>$stats['late'],           'color'=>'#D97706','bg'=>'#FFFBEB','icon'=>'clock'],
            ['id'=>'stat-punches',  'label'=>'Total Scans',    'val'=>$stats['total_punches'],  'color'=>'#7C3AED','bg'=>'#F5F3FF','icon'=>'finger'],
            ['id'=>'stat-devices',  'label'=>'Devices Online', 'val'=>$stats['devices_online'], 'color'=>'#0891B2','bg'=>'#ECFEFF','icon'=>'wifi'],
          ];
        @endphp
        @foreach($kpis as $k)
        <div class="kpi-card">
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:12px;font-weight:500;color:#64748B;">{{ $k['label'] }}</span>
            <div style="width:32px;height:32px;border-radius:8px;background:{{ $k['bg'] }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <svg width="15" height="15" fill="none" stroke="{{ $k['color'] }}" stroke-width="2" viewBox="0 0 24 24">
                @if($k['icon']==='users')   <path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                @elseif($k['icon']==='check') <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                @elseif($k['icon']==='x')   <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                @elseif($k['icon']==='clock') <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                @elseif($k['icon']==='finger') <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
                @else <path stroke-linecap="round" stroke-linejoin="round" d="M5 12.55a11 11 0 0114.08 0"/><path stroke-linecap="round" stroke-linejoin="round" d="M1.42 9a16 16 0 0121.16 0"/><path stroke-linecap="round" stroke-linejoin="round" d="M8.53 16.11a6 6 0 016.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>
                @endif
              </svg>
            </div>
          </div>
          <div>
            <div style="font-size:28px;font-weight:800;color:#0F172A;line-height:1;" id="{{ $k['id'] }}">{{ $k['val'] }}</div>
          </div>
        </div>
        @endforeach
      </div>

      {{-- Toolbar --}}
      <div class="card" style="padding:12px 16px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <div style="position:relative;flex:1;min-width:200px;">
          <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;" width="14" height="14" fill="none" stroke="#94A3B8" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" id="search-input" placeholder="Search employee name or code…" class="tb-input" style="padding-left:32px;width:100%;">
        </div>
        <select id="dept-filter" class="tb-input" style="min-width:150px;">
          <option value="">All Departments</option>
          @foreach($departments as $dept)
            <option value="{{ $dept }}" {{ $department === $dept ? 'selected' : '' }}>{{ $dept }}</option>
          @endforeach
        </select>
        <select id="device-filter" class="tb-input" style="min-width:140px;">
          <option value="">All Devices</option>
          @foreach($devices as $device)
            <option value="{{ $device->id }}" {{ $deviceId == $device->id ? 'selected' : '' }}>{{ $device->serial_number }}</option>
          @endforeach
        </select>
        <button onclick="applyFilters()" class="tb-btn-primary">Apply</button>
        <button onclick="resetFilters()" class="tb-btn-ghost">Reset</button>
        <div style="margin-left:auto;">
          <button onclick="exportExcel()" class="tb-btn-export">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Export
          </button>
        </div>
      </div>

      {{-- Table + Detail Panel --}}
      <div style="display:flex;gap:14px;align-items:flex-start;min-height:0;">

        {{-- Attendance Table --}}
        <div class="card" style="flex:1;overflow:hidden;display:flex;flex-direction:column;min-width:0;">
          <div style="padding:14px 16px;border-bottom:1px solid #F1F5F9;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:10px;">
              <span style="font-size:13.5px;font-weight:700;color:#0F172A;">Attendance Summary</span>
              <span id="total-badge" style="background:#F1F5F9;color:#64748B;font-size:11px;font-weight:600;padding:2px 8px;border-radius:99px;">0</span>
            </div>
            <div id="grid-loading" style="display:none;">
              <svg class="spin" width="16" height="16" fill="none" stroke="#2563EB" stroke-width="2.5" viewBox="0 0 24 24"><path opacity=".25" stroke-linecap="round" d="M12 2a10 10 0 100 20A10 10 0 0012 2z"/><path stroke-linecap="round" d="M12 2a10 10 0 0110 10"/></svg>
            </div>
          </div>
          <div style="overflow-x:auto;overflow-y:auto;max-height:calc(100vh - 340px);">
            <table class="data-table">
              <thead>
                <tr>
                  <th style="width:40px;">#</th>
                  <th>Employee</th>
                  <th>Code</th>
                  <th>First Check-In</th>
                  <th>Last Check-Out</th>
                  <th>Working Hours</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="attendance-tbody">
                <tr><td colspan="7" style="text-align:center;padding:40px 16px;color:#94A3B8;font-size:13px;">Loading attendance data…</td></tr>
              </tbody>
            </table>
          </div>
          <div style="padding:10px 16px;border-top:1px solid #F1F5F9;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <span style="font-size:12.5px;color:#64748B;" id="pagination-info">Showing 0 records</span>
            <div style="display:flex;align-items:center;gap:4px;" id="pagination-controls"></div>
          </div>
        </div>

        {{-- Employee Detail Panel --}}
        <div class="card" id="detail-panel" style="width:360px;flex-shrink:0;display:flex;flex-direction:column;max-height:calc(100vh - 200px);overflow:hidden;">
          <div style="padding:14px 16px;border-bottom:1px solid #F1F5F9;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <span style="font-size:13px;font-weight:700;color:#0F172A;">Employee Detail</span>
            <select id="timeline-emp-select" onchange="loadTimeline(this.value)" class="tb-input" style="font-size:12px;height:30px;max-width:180px;">
              <option value="">Select employee</option>
              @foreach(\App\Models\Employee::active()->orderBy('name')->get() as $emp)
                <option value="{{ $emp->employee_code }}">{{ $emp->name }} ({{ $emp->employee_code }})</option>
              @endforeach
            </select>
          </div>
          <div id="timeline-content" style="flex:1;overflow-y:auto;padding:16px;">
            <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:180px;color:#94A3B8;gap:10px;">
              <svg width="36" height="36" fill="none" stroke="#E2E8F0" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 20c0-4 3.582-7 8-7s8 3 8 7"/></svg>
              <span style="font-size:13px;">Select an employee</span>
            </div>
          </div>
        </div>

      </div>
    </div>{{-- end scrollable --}}
  </div>{{-- end main --}}
</div>

<script>
// ═══════════════════ STATE ═══════════════════
let currentPage   = 1;
let currentDate   = document.getElementById('date-filter').value;
let currentDept   = '';
let currentDevice = '';
let currentSearch = '';
let countdownSecs = 5;

// ═══════════════════ HELPERS ═══════════════════
const statusBadge = s => {
  const m = {
    present:     '<span class="badge badge-green"><span class="dot dot-green"></span>Checked Out</span>',
    checked_out: '<span class="badge badge-green"><span class="dot dot-green"></span>Checked Out</span>',
    in_office:   '<span class="badge badge-blue"><span class="dot dot-blue"></span>In Office</span>',
    late:        '<span class="badge badge-amber"><span class="dot dot-amber"></span>Late</span>',
    absent:      '<span class="badge badge-red"><span class="dot dot-red"></span>Absent</span>',
    missing_out: '<span class="badge badge-orange">⚠ Missing Out</span>',
    missing_in:  '<span class="badge badge-orange">⚠ Missing In</span>',
    incomplete:  '<span class="badge badge-gray">Incomplete</span>',
  };
  return m[s] || '<span class="badge badge-gray">—</span>';
};

const avatar = (initials, color) =>
  `<div class="avatar" style="background:${color};font-size:11px;">${initials}</div>`;

const fmtTime = t => t
  ? `<span style="color:#0F172A;font-weight:600;">${t}</span>`
  : `<span style="color:#CBD5E1;">—</span>`;

// ═══════════════════ GRID ═══════════════════
async function loadGrid(page = 1) {
  currentPage = page;
  document.getElementById('grid-loading').style.display = 'block';

  const params = new URLSearchParams({
    date: currentDate, department: currentDept, device: currentDevice,
    search: currentSearch, page, per_page: 15,
  });

  try {
    const res  = await fetch(`/api/attendance/grid?${params}`, { headers: { Accept: 'application/json' } });
    const data = await res.json();
    renderGrid(data);
    document.getElementById('last-sync-time').textContent = new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
  } catch(e) {
    document.getElementById('attendance-tbody').innerHTML =
      `<tr><td colspan="7" style="text-align:center;padding:40px;color:#EF4444;font-size:13px;">Failed to load. Retrying…</td></tr>`;
  } finally {
    document.getElementById('grid-loading').style.display = 'none';
  }
}

function renderGrid(data) {
  const tbody = document.getElementById('attendance-tbody');
  document.getElementById('total-badge').textContent = data.total;

  if (!data.rows.length) {
    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:48px 16px;color:#94A3B8;font-size:13px;">No attendance records for this date.</td></tr>`;
    document.getElementById('pagination-info').textContent = 'No records';
    document.getElementById('pagination-controls').innerHTML = '';
    return;
  }

  const offset = (data.page - 1) * data.per_page;
  tbody.innerHTML = data.rows.map((row, i) => `
    <tr class="fade-up" onclick="selectRow(this,'${row.code}')" style="animation-delay:${i*20}ms">
      <td style="color:#CBD5E1;font-size:12px;font-weight:500;">${offset + i + 1}</td>
      <td>
        <div style="display:flex;align-items:center;gap:10px;">
          ${avatar(row.initials, row.avatar_color)}
          <div>
            <div style="font-size:13.5px;font-weight:600;color:#0F172A;line-height:1.3;">${row.name}</div>
            <div style="font-size:11.5px;color:#94A3B8;">${row.department || '—'}</div>
          </div>
        </div>
      </td>
      <td><span style="font-size:12px;font-weight:500;color:#64748B;font-variant-numeric:tabular-nums;">${row.code}</span></td>
      <td style="font-size:13px;">
        ${row.first_in
          ? `<span style="color:#059669;font-weight:600;">↑ ${row.first_in}</span>`
          : `<span style="color:#CBD5E1;">—</span>`}
      </td>
      <td style="font-size:13px;">
        ${row.last_out
          ? `<span style="color:#DC2626;font-weight:600;">↓ ${row.last_out}</span>`
          : `<span style="color:#CBD5E1;">—</span>`}
      </td>
      <td>
        ${row.working_hours
          ? `<span style="font-size:13px;font-weight:700;color:#0F172A;">${row.working_hours}</span>`
          : `<span style="color:#CBD5E1;font-size:13px;">—</span>`}
      </td>
      <td>${statusBadge(row.status)}</td>
    </tr>
  `).join('');

  const from = offset + 1, to = offset + data.rows.length;
  document.getElementById('pagination-info').textContent = `${from}–${to} of ${data.total} employees`;
  renderPagination(data.page, data.last_page);
}

function selectRow(tr, code) {
  document.querySelectorAll('.data-table tbody tr').forEach(r => r.classList.remove('selected'));
  tr.classList.add('selected');
  document.getElementById('timeline-emp-select').value = code;
  loadTimeline(code);
}

function renderPagination(page, lastPage) {
  const el = document.getElementById('pagination-controls');
  if (lastPage <= 1) { el.innerHTML = ''; return; }
  let h = `<button class="pg-btn" onclick="loadGrid(${page-1})" ${page===1?'disabled':''}>‹</button>`;
  for (let p = 1; p <= lastPage; p++) {
    if (p===1 || p===lastPage || (p>=page-1 && p<=page+1)) {
      h += `<button class="pg-btn ${p===page?'active':''}" onclick="loadGrid(${p})">${p}</button>`;
    } else if (p===page-2||p===page+2) h += `<span style="color:#CBD5E1;padding:0 4px;font-size:12px;">…</span>`;
  }
  h += `<button class="pg-btn" onclick="loadGrid(${page+1})" ${page===lastPage?'disabled':''}>›</button>`;
  el.innerHTML = h;
}

// ═══════════════════ TIMELINE ═══════════════════
async function loadTimeline(code) {
  if (!code) return;
  const el = document.getElementById('timeline-content');
  el.innerHTML = `<div style="text-align:center;padding:48px 0;color:#94A3B8;font-size:13px;">Loading…</div>`;

  try {
    const res  = await fetch(`/api/attendance/timeline/${code}?date=${currentDate}`);
    const data = await res.json();
    renderTimeline(data, el);
  } catch(e) {
    el.innerHTML = `<div style="text-align:center;padding:32px;color:#EF4444;font-size:13px;">Failed to load.</div>`;
  }
}

function renderTimeline(data, el) {
  const emp      = data.employee;
  const sessions = data.sessions ?? [];
  const events   = data.events   ?? [];
  const sum      = data.summary;

  if (!events.length) {
    el.innerHTML = `
      <div style="display:flex;align-items:center;gap:10px;padding-bottom:14px;border-bottom:1px solid #F1F5F9;margin-bottom:14px;">
        <div class="avatar" style="width:40px;height:40px;font-size:13px;background:${emp?.color??'#6366f1'}">${emp?.initials??'?'}</div>
        <div>
          <div style="font-size:14px;font-weight:700;color:#0F172A;">${emp?.name??'Employee'}</div>
          <div style="font-size:12px;color:#94A3B8;">${emp?.department??'—'}</div>
        </div>
      </div>
      <div style="text-align:center;padding:32px 0;color:#94A3B8;font-size:13px;">No attendance recorded.</div>`;
    return;
  }

  // Status config
  const statusCfg = {
    checked_out: { cls:'badge-green',  label:'Checked Out',     dot:'dot-green'  },
    in_office:   { cls:'badge-blue',   label:'In Office',       dot:'dot-blue'   },
    absent:      { cls:'badge-red',    label:'Absent',          dot:'dot-red'    },
    missing_out: { cls:'badge-orange', label:'Missing Out',     dot:''           },
    incomplete:  { cls:'badge-gray',   label:'Incomplete',      dot:'dot-gray'   },
  };
  const sc = statusCfg[sum?.status] ?? { cls:'badge-gray', label: sum?.status??'—', dot:'dot-gray' };

  // Session cards HTML
  const sessionCards = sessions.length
    ? sessions.map(s => `
      <div class="session-card" style="margin-bottom:8px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
          <span style="font-size:11px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.05em;">Session ${s.index}</span>
          ${s.admin_note ? `<span style="font-size:11px;color:#7C3AED;font-weight:600;">Corrected</span>` : ''}
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
          <div style="display:flex;align-items:center;gap:5px;">
            <span class="dot dot-green"></span>
            <span style="font-size:13px;font-weight:700;color:#0F172A;">${s.check_in??'—'}</span>
          </div>
          <svg width="14" height="14" fill="none" stroke="#CBD5E1" stroke-width="2" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
          <div style="display:flex;align-items:center;gap:5px;">
            <span class="dot ${s.check_out?'dot-red':'dot-gray'}"></span>
            <span style="font-size:13px;font-weight:700;color:${s.check_out?'#0F172A':'#94A3B8'};">${s.check_out??'In Office'}</span>
          </div>
        </div>
        ${s.duration ? `<div style="font-size:12px;color:#64748B;font-weight:500;margin-top:5px;">⏱ ${s.duration}</div>` : ''}
      </div>`)
    .join('')
    : `<div style="font-size:12.5px;color:#94A3B8;text-align:center;padding:8px 0;">No sessions paired yet</div>`;

  // Event rows
  const evStyles = {
    check_in:  { color:'#059669', dot:'dot-green', label:'CHECK-IN'  },
    check_out: { color:'#DC2626', dot:'dot-red',   label:'CHECK-OUT' },
    duplicate: { color:'#D97706', dot:'dot-amber', label:'DUPLICATE' },
    unknown:   { color:'#64748B', dot:'dot-gray',  label:'SCAN'      },
  };

  const buildEv = ev => {
    const s = evStyles[ev.type] ?? evStyles.unknown;
    return `
    <div style="display:flex;align-items:flex-start;gap:10px;padding:6px 0;">
      <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;padding-top:4px;">
        <span class="dot ${s.dot}"></span>
        <span class="tl-connector"></span>
      </div>
      <div style="flex:1;padding-bottom:2px;">
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <span style="font-size:11px;font-weight:700;color:${s.color};letter-spacing:.04em;">${s.label}</span>
          ${ev.verify_label ? `<span style="font-size:11px;color:#CBD5E1;">${ev.verify_label}</span>` : ''}
        </div>
        <div style="font-size:13.5px;font-weight:600;color:#0F172A;margin-top:1px;">${ev.time}</div>
      </div>
    </div>`;
  };

  const visEvs = events.filter(e => !e.is_duplicate);
  const dupEvs = events.filter(e => e.is_duplicate);
  const dupBtn = dupEvs.length ? `
    <div style="margin-top:4px;">
      <button onclick="toggleDups(this)" style="font-size:11.5px;color:#94A3B8;background:none;border:none;cursor:pointer;padding:4px 0;font-family:inherit;">
        + ${dupEvs.length} duplicate scan${dupEvs.length>1?'s':''} hidden
      </button>
      <div class="dup-events" style="display:none;border-left:2px solid #FEF3C7;padding-left:8px;margin-top:4px;">
        ${dupEvs.map(buildEv).join('')}
      </div>
    </div>` : '';

  el.innerHTML = `
    {{-- Profile header --}}
    <div style="display:flex;align-items:center;gap:12px;padding-bottom:14px;border-bottom:1px solid #F1F5F9;margin-bottom:14px;">
      <div class="avatar" style="width:42px;height:42px;font-size:14px;flex-shrink:0;background:${emp.color}">${emp.initials}</div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:14px;font-weight:700;color:#0F172A;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${emp.name}</div>
        <div style="font-size:12px;color:#64748B;">${emp.department}</div>
      </div>
      <span class="badge ${sc.cls}" style="flex-shrink:0;font-size:11px;">
        ${sc.dot ? `<span class="dot ${sc.dot}"></span>` : ''}${sc.label}
      </span>
    </div>

    {{-- Summary strip --}}
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:16px;">
      <div style="background:#F8FAFC;border:1px solid #E5E7EB;border-radius:9px;padding:10px;text-align:center;">
        <div style="font-size:10.5px;color:#94A3B8;font-weight:500;margin-bottom:4px;">First In</div>
        <div style="font-size:13px;font-weight:700;color:#059669;">${sum?.first_in??'—'}</div>
      </div>
      <div style="background:#F8FAFC;border:1px solid #E5E7EB;border-radius:9px;padding:10px;text-align:center;">
        <div style="font-size:10.5px;color:#94A3B8;font-weight:500;margin-bottom:4px;">Last Out</div>
        <div style="font-size:13px;font-weight:700;color:#DC2626;">${sum?.last_out??'—'}</div>
      </div>
      <div style="background:#F8FAFC;border:1px solid #E5E7EB;border-radius:9px;padding:10px;text-align:center;">
        <div style="font-size:10.5px;color:#94A3B8;font-weight:500;margin-bottom:4px;">Hours</div>
        <div style="font-size:13px;font-weight:700;color:#0F172A;">${sum?.working_hours??'—'}</div>
      </div>
    </div>

    {{-- Sessions --}}
    <div style="margin-bottom:16px;">
      <div class="section-label">Sessions</div>
      ${sessionCards}
    </div>

    {{-- Raw events --}}
    <div>
      <div class="section-label">Raw Events</div>
      <div style="padding-left:2px;">
        ${visEvs.map(buildEv).join('')}
        ${dupBtn}
      </div>
    </div>`;
}

function toggleDups(btn) {
  const panel = btn.nextElementSibling;
  const hidden = panel.style.display === 'none';
  panel.style.display = hidden ? 'block' : 'none';
  const n = panel.querySelectorAll('.tl-connector').length;
  btn.textContent = hidden
    ? `− ${n} duplicate scan${n>1?'s':''} visible`
    : `+ ${n} duplicate scan${n>1?'s':''} hidden`;
}

// ═══════════════════ STATS ═══════════════════
async function loadStats() {
  try {
    const res  = await fetch(`/api/attendance/stats?date=${currentDate}`);
    const data = await res.json();
    document.getElementById('stat-total-emp').textContent = data.total_employees;
    document.getElementById('stat-present').textContent   = data.present;
    document.getElementById('stat-absent').textContent    = data.absent;
    document.getElementById('stat-late').textContent      = data.late;
    document.getElementById('stat-punches').textContent   = data.total_punches;
    document.getElementById('stat-devices').textContent   = data.devices_online;
  } catch(e) {}
}

// ═══════════════════ SYNC STATUS ═══════════════════
async function loadSyncStatus() {
  try {
    const res  = await fetch('/api/sync/status');
    const data = await res.json();
    const el   = document.getElementById('sidebar-sync-status');
    if (data.devices?.length) {
      el.innerHTML = data.devices.map(d => `
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
          <span style="font-size:12px;color:#0F172A;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:110px;">${d.name||d.serial_number}</span>
          <span style="display:flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:${d.is_online?'#059669':'#94A3B8'};">
            <span class="dot ${d.is_online?'dot-green pulse':'dot-gray'}"></span>
            ${d.is_online?'Online':'Offline'}
          </span>
        </div>
        <div style="font-size:11px;color:#94A3B8;margin-bottom:6px;">${d.last_activity}</div>`).join('');
    } else {
      el.innerHTML = '<div style="font-size:12px;color:#94A3B8;">No devices registered</div>';
    }
  } catch(e) {}
}

// ═══════════════════ FILTERS ═══════════════════
function applyFilters() {
  currentDate   = document.getElementById('date-filter').value;
  currentDept   = document.getElementById('dept-filter').value;
  currentDevice = document.getElementById('device-filter').value;
  currentSearch = document.getElementById('search-input').value;
  loadGrid(1); loadStats();
}

function resetFilters() {
  const today = '{{ today()->toDateString() }}';
  document.getElementById('date-filter').value   = today;
  document.getElementById('dept-filter').value   = '';
  document.getElementById('device-filter').value = '';
  document.getElementById('search-input').value  = '';
  currentDate = today; currentDept = ''; currentDevice = ''; currentSearch = '';
  loadGrid(1); loadStats();
}

document.getElementById('date-filter').addEventListener('change', () => {
  currentDate = document.getElementById('date-filter').value;
  loadGrid(1); loadStats();
});
document.getElementById('search-input').addEventListener('input', debounce(() => {
  currentSearch = document.getElementById('search-input').value;
  loadGrid(1);
}, 380));

function debounce(fn, ms) {
  let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
}

function exportExcel() {
  alert('Export: /api/attendance/export?date=' + currentDate);
}

// ═══════════════════ SIDEBAR NAV ═══════════════════
function setNav(id) {
  document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
  document.getElementById(id)?.classList.add('active');
}

// ═══════════════════ COUNTDOWN ═══════════════════
function startCountdown() {
  countdownSecs = 5;
  const el = document.getElementById('countdown');
  const iv = setInterval(() => {
    countdownSecs--;
    el.textContent = '0:0' + countdownSecs;
    if (countdownSecs <= 0) {
      clearInterval(iv);
      refreshAll();
      startCountdown();
    }
  }, 1000);
}

function refreshAll() {
  loadGrid(currentPage);
  loadStats();
  loadSyncStatus();
}

// ═══════════════════ INIT ═══════════════════
loadGrid(1);
loadStats();
loadSyncStatus();
startCountdown();
</script>
</body>
</html>
