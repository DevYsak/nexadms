<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>BioSync — Attendance Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
  * { font-family: 'Inter', system-ui, sans-serif; }
  .scrollbar-thin::-webkit-scrollbar { width: 4px; height: 4px; }
  .scrollbar-thin::-webkit-scrollbar-track { background: #1e293b; }
  .scrollbar-thin::-webkit-scrollbar-thumb { background: #334155; border-radius: 2px; }
  .pulse-dot { animation: pulse-anim 2s infinite; }
  @keyframes pulse-anim { 0%,100%{opacity:1} 50%{opacity:.4} }
  .badge { @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold; }
  .fade-in { animation: fadeIn .3s ease; }
  @keyframes fadeIn { from{opacity:0;transform:translateY(4px)} to{opacity:1;transform:none} }
  .timeline-line::before { content:''; position:absolute; left:20px; top:0; bottom:0; width:2px; background:#e2e8f0; z-index:0; }
  .dark .timeline-line::before { background:#334155; }
  tr.hover-row:hover { background:#f8fafc; cursor:pointer; }
  .dark tr.hover-row:hover { background:#1e293b; }
</style>
</head>

<body class="bg-slate-50 text-slate-800" id="body">
<div class="flex h-screen overflow-hidden">

  {{-- ──────────────────────────── SIDEBAR ──────────────────────────────── --}}
  <aside class="w-60 bg-slate-900 text-slate-300 flex flex-col flex-shrink-0 overflow-y-auto scrollbar-thin">
    {{-- Logo --}}
    <div class="px-5 py-5 flex items-center gap-3 border-b border-slate-700/50">
      <div class="size-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center flex-shrink-0">
        <svg class="size-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/></svg>
      </div>
      <div>
        <div class="text-white font-bold text-sm leading-tight">BioSync</div>
        <div class="text-xs text-slate-500">Attendance System</div>
      </div>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 px-3 py-4 space-y-0.5">
      @php
        $navItems = [
          ['icon'=>'home','label'=>'Dashboard','id'=>'nav-dash'],
          ['icon'=>'list','label'=>'Attendance Log','id'=>'nav-log','active'=>true],
          ['icon'=>'monitor','label'=>'Live Monitor','id'=>'nav-live'],
          ['icon'=>'users','label'=>'Employees','id'=>'nav-emp'],
          ['icon'=>'device','label'=>'Devices','id'=>'nav-dev'],
          ['icon'=>'chart','label'=>'Reports','id'=>'nav-rep'],
          ['icon'=>'sync','label'=>'Sync Status','id'=>'nav-sync'],
          ['icon'=>'cog','label'=>'Settings','id'=>'nav-set'],
        ];
      @endphp
      @foreach($navItems as $item)
        <button onclick="setPage('{{ $item['id'] }}')" id="{{ $item['id'] }}"
          class="sidebar-nav w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
            {{ ($item['active'] ?? false) ? 'bg-blue-600 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
          @if($item['icon'] === 'home')
            <svg class="size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
          @elseif($item['icon'] === 'list')
            <svg class="size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
          @elseif($item['icon'] === 'users')
            <svg class="size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          @elseif($item['icon'] === 'chart')
            <svg class="size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          @else
            <svg class="size-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
          @endif
          {{ $item['label'] }}
        </button>
      @endforeach
    </nav>

    {{-- Device Sync Status --}}
    <div class="px-3 pb-4">
      <div class="bg-slate-800 rounded-xl p-3 text-xs space-y-2">
        <div class="flex items-center justify-between text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
          <span>Device Sync Status</span>
          <span class="size-2 rounded-full bg-emerald-500 pulse-dot"></span>
        </div>
        <div id="sidebar-sync-status" class="space-y-1 text-slate-400">
          <div class="text-slate-300 font-semibold">Loading...</div>
        </div>
        <div id="next-sync-countdown" class="text-blue-400 font-mono text-center text-[11px] pt-1">
          Next Auto Sync in <span id="countdown">00:00:05</span>
        </div>
      </div>
    </div>
  </aside>

  {{-- ──────────────────────────── MAIN AREA ─────────────────────────────── --}}
  <div class="flex-1 flex flex-col overflow-hidden">

    {{-- Header --}}
    <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between flex-shrink-0">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Attendance Log</h1>
        <p class="text-sm text-slate-500 mt-0.5">Live biometric attendance with auto-refresh every 5 seconds.</p>
      </div>
      <div class="flex items-center gap-4">
        <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 rounded-full px-3 py-1.5">
          <span class="size-2 rounded-full bg-emerald-500 pulse-dot"></span>
          <span class="text-xs font-semibold text-emerald-700">Live sync active</span>
          <span class="text-xs text-emerald-600" id="last-sync-time">Updated just now</span>
        </div>
        <input type="date" id="date-filter" value="{{ $date }}"
          class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
    </header>

    {{-- Scrollable content --}}
    <div class="flex-1 overflow-y-auto scrollbar-thin p-6 space-y-6">

      {{-- Stats Cards --}}
      <div class="grid grid-cols-2 xl:grid-cols-6 gap-4">
        @php
        $statCards = [
          ['id'=>'stat-total-emp', 'label'=>'Total Employees','val'=>$stats['total_employees'],'icon'=>'users','bg'=>'bg-blue-50','icon_bg'=>'bg-blue-500','text'=>'text-blue-600'],
          ['id'=>'stat-present',   'label'=>'Present Today',  'val'=>$stats['present'],         'icon'=>'check','bg'=>'bg-emerald-50','icon_bg'=>'bg-emerald-500','text'=>'text-emerald-600'],
          ['id'=>'stat-absent',    'label'=>'Absent Today',   'val'=>$stats['absent'],          'icon'=>'x','bg'=>'bg-red-50','icon_bg'=>'bg-red-500','text'=>'text-red-600'],
          ['id'=>'stat-late',      'label'=>'Late Arrivals',  'val'=>$stats['late'],            'icon'=>'clock','bg'=>'bg-amber-50','icon_bg'=>'bg-amber-500','text'=>'text-amber-600'],
          ['id'=>'stat-punches',   'label'=>'Total Punches',  'val'=>$stats['total_punches'],   'icon'=>'finger','bg'=>'bg-violet-50','icon_bg'=>'bg-violet-500','text'=>'text-violet-600'],
          ['id'=>'stat-devices',   'label'=>'Devices Online', 'val'=>$stats['devices_online'],  'icon'=>'device','bg'=>'bg-cyan-50','icon_bg'=>'bg-cyan-500','text'=>'text-cyan-600'],
        ];
        @endphp
        @foreach($statCards as $card)
        <div class="{{ $card['bg'] }} rounded-2xl p-4 flex items-center gap-3">
          <div class="size-11 {{ $card['icon_bg'] }} rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="size-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              @if($card['icon']==='users')   <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
              @elseif($card['icon']==='check') <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              @elseif($card['icon']==='x')   <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              @elseif($card['icon']==='clock') <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              @elseif($card['icon']==='finger') <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
              @else <path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
              @endif
            </svg>
          </div>
          <div>
            <div class="text-2xl font-black {{ $card['text'] }}" id="{{ $card['id'] }}">{{ $card['val'] }}</div>
            <div class="text-xs text-slate-500 font-medium leading-tight mt-0.5">{{ $card['label'] }}</div>
          </div>
        </div>
        @endforeach
      </div>

      {{-- Latest Punches Widget --}}
      <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <h2 class="font-bold text-slate-800 text-sm">Latest Punches</h2>
            <span class="size-2 rounded-full bg-emerald-500 pulse-dot"></span>
          </div>
          <span class="text-xs text-slate-400" id="recent-updated">Live</span>
        </div>
        <div id="recent-punches-list" class="divide-y divide-slate-50">
          <div class="px-5 py-4 text-center text-slate-400 text-sm">Loading…</div>
        </div>
      </div>

      {{-- Filter Bar --}}
      <div class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-48">
          <input type="text" id="search-input" placeholder="Search by name or code…"
            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <select id="dept-filter" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">All Departments</option>
          @foreach($departments as $dept)
            <option value="{{ $dept }}" {{ $department === $dept ? 'selected' : '' }}>{{ $dept }}</option>
          @endforeach
        </select>
        <select id="device-filter" class="border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">All Devices</option>
          @foreach($devices as $device)
            <option value="{{ $device->id }}" {{ $deviceId == $device->id ? 'selected' : '' }}>{{ $device->serial_number }}</option>
          @endforeach
        </select>
        <button onclick="applyFilters()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-2 rounded-lg transition-colors">Filter</button>
        <button onclick="resetFilters()" class="border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold text-sm px-5 py-2 rounded-lg transition-colors">Reset</button>
        <button onclick="exportExcel()" class="flex items-center gap-2 border border-emerald-300 text-emerald-700 hover:bg-emerald-50 font-semibold text-sm px-4 py-2 rounded-lg transition-colors ml-auto">
          <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Export Excel
        </button>
      </div>

      {{-- Attendance Grid + Timeline --}}
      <div class="flex gap-5 items-start">

        {{-- Attendance Grid --}}
        <div class="flex-1 bg-white rounded-2xl border border-slate-200 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-slate-800">Attendance Summary</h2>
            <div id="grid-loading" class="hidden">
              <svg class="animate-spin size-4 text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-100">
                  <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide w-8">#</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Employee</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Code</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">First Check-In</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Last Check-Out</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Working Hours</th>
                  <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                </tr>
              </thead>
              <tbody id="attendance-tbody" class="divide-y divide-slate-50">
                <tr><td colspan="7" class="px-4 py-12 text-center text-slate-400">Loading attendance data…</td></tr>
              </tbody>
            </table>
          </div>
          {{-- Pagination --}}
          <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between text-sm">
            <span class="text-slate-500" id="pagination-info">Showing 0 records</span>
            <div class="flex items-center gap-1" id="pagination-controls"></div>
          </div>
        </div>

        {{-- Employee Timeline --}}
        <div class="w-96 flex-shrink-0 bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col" style="max-height:680px">
          <div class="px-4 py-4 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
            <h2 class="font-bold text-slate-800 text-sm">Employee Detail</h2>
            <select id="timeline-emp-select" onchange="loadTimeline(this.value)"
              class="text-xs border border-slate-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500 max-w-44">
              <option value="">Select employee</option>
              @foreach(\App\Models\Employee::active()->orderBy('name')->get() as $emp)
                <option value="{{ $emp->employee_code }}">{{ $emp->name }} ({{ $emp->employee_code }})</option>
              @endforeach
            </select>
          </div>
          <div id="timeline-content" class="px-4 py-4 overflow-y-auto scrollbar-thin flex-1">
            <div class="text-center py-10 text-slate-400 text-sm">
              <svg class="size-10 mx-auto mb-2 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              Select an employee to view their detail
            </div>
          </div>
        </div>
      </div>

      {{-- Sync Status Bar --}}
      <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h3 class="font-bold text-slate-800 mb-4">Sync &amp; Storage Information</h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
          <div class="text-center">
            <div class="size-10 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-2">
              <svg class="size-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
            <div class="text-xs text-slate-500">Last Device Sync</div>
            <div class="text-sm font-bold text-slate-800 mt-0.5" id="sync-last-time">—</div>
            <div class="text-xs text-green-600 font-semibold mt-0.5">Success</div>
          </div>
          <div class="text-center">
            <div class="size-10 bg-red-100 rounded-xl flex items-center justify-center mx-auto mb-2">
              <svg class="size-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="text-xs text-slate-500">Total Un-Synced</div>
            <div class="text-sm font-bold text-slate-800 mt-0.5" id="sync-unsynced">0</div>
            <div class="text-xs text-slate-500 font-semibold mt-0.5">All records synced</div>
          </div>
          <div class="text-center">
            <div class="size-10 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-2">
              <svg class="size-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="text-xs text-slate-500">Auto Sync</div>
            <div class="text-sm font-bold text-slate-800 mt-0.5">Every 5 Seconds</div>
            <div class="text-xs text-blue-600 font-semibold mt-0.5">Enabled</div>
          </div>
          <div class="text-center">
            <div class="size-10 bg-indigo-100 rounded-xl flex items-center justify-center mx-auto mb-2">
              <svg class="size-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582 4-8 4"/></svg>
            </div>
            <div class="text-xs text-slate-500">Data Stored In</div>
            <div class="text-sm font-bold text-slate-800 mt-0.5">MySQL Database</div>
            <div class="text-xs text-indigo-600 font-semibold mt-0.5">attendance_logs table</div>
          </div>
          <div class="text-center">
            <div class="size-10 bg-emerald-100 rounded-xl flex items-center justify-center mx-auto mb-2">
              <svg class="size-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <div class="text-xs text-slate-500">Data Backup</div>
            <div class="text-sm font-bold text-slate-800 mt-0.5">Daily at 11:59 PM</div>
            <div class="text-xs text-emerald-600 font-semibold mt-0.5">Enabled</div>
          </div>
        </div>
        <div class="mt-4 text-xs text-slate-400 text-center border-t border-slate-100 pt-4">
          All attendance data is automatically stored in the database. Even if the system is shut down, data is safe and will be available when the system restarts.
        </div>
      </div>

    </div>{{-- end scrollable --}}
  </div>{{-- end main --}}
</div>

<script>
// ── State ───────────────────────────────────────────────────────────────────
let currentPage    = 1;
let currentDate    = document.getElementById('date-filter').value;
let currentDept    = '';
let currentDevice  = '';
let currentSearch  = '';
let countdownSecs  = 5;
let refreshTimer;

// ── Helpers ──────────────────────────────────────────────────────────────────
const statusBadge = (status) => {
  const map = {
    present:            '<span class="badge bg-emerald-100 text-emerald-700">✓ Checked Out</span>',
    checked_out:        '<span class="badge bg-emerald-100 text-emerald-700">✓ Checked Out</span>',
    in_office:          '<span class="badge bg-blue-100 text-blue-700">● In Office</span>',
    late:               '<span class="badge bg-amber-100 text-amber-700">⏰ Late</span>',
    absent:             '<span class="badge bg-red-100 text-red-500">✗ Absent</span>',
    missing_out:        '<span class="badge bg-orange-100 text-orange-700">⚠ Missing Out Punch</span>',
    missing_in:         '<span class="badge bg-orange-100 text-orange-700">⚠ Missing In Punch</span>',
    incomplete:         '<span class="badge bg-yellow-100 text-yellow-700">◑ Incomplete</span>',
  };
  return map[status] || '<span class="badge bg-slate-100 text-slate-600">Unknown</span>';
};

const avatar = (initials, color) =>
  `<div class="size-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:${color}">${initials}</div>`;

// ── Grid Loading ─────────────────────────────────────────────────────────────
async function loadGrid(page = 1) {
  currentPage = page;
  document.getElementById('grid-loading').classList.remove('hidden');

  const params = new URLSearchParams({
    date: currentDate, department: currentDept, device: currentDevice,
    search: currentSearch, page, per_page: 10,
  });

  try {
    const res  = await fetch(`/api/attendance/grid?${params}`, { headers: { Accept: 'application/json' } });
    const data = await res.json();
    renderGrid(data);
    document.getElementById('last-sync-time').textContent = 'Updated ' + new Date().toLocaleTimeString();
  } catch(e) {
    document.getElementById('attendance-tbody').innerHTML =
      '<tr><td colspan="8" class="px-4 py-8 text-center text-red-400 text-sm">Failed to load data. Retrying…</td></tr>';
  } finally {
    document.getElementById('grid-loading').classList.add('hidden');
  }
}

function renderGrid(data) {
  const tbody = document.getElementById('attendance-tbody');
  if (!data.rows.length) {
    tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-12 text-center text-slate-400 text-sm">No attendance records found.</td></tr>';
    document.getElementById('pagination-info').textContent = 'Showing 0 records';
    document.getElementById('pagination-controls').innerHTML = '';
    return;
  }

  const offset = (data.page - 1) * data.per_page;
  tbody.innerHTML = data.rows.map((row, i) => `
    <tr class="hover-row fade-in" onclick="loadTimeline('${row.code}'); document.getElementById('timeline-emp-select').value='${row.code}';">
      <td class="px-4 py-3 text-slate-400 text-xs">${offset + i + 1}</td>
      <td class="px-4 py-3">
        <div class="flex items-center gap-3">
          ${avatar(row.initials, row.avatar_color)}
          <div>
            <div class="font-semibold text-slate-800 text-sm">${row.name}</div>
            <div class="text-xs text-slate-400">${row.department}</div>
          </div>
        </div>
      </td>
      <td class="px-4 py-3 text-slate-500 text-sm font-mono">${row.code}</td>
      <td class="px-4 py-3 font-semibold text-emerald-600 text-sm">${row.first_in ? '↗ ' + row.first_in : '<span class="text-slate-300">--</span>'}</td>
      <td class="px-4 py-3 font-semibold text-rose-500 text-sm">${row.last_out ? '↙ ' + row.last_out : '<span class="text-slate-300">--</span>'}</td>
      <td class="px-4 py-3 text-slate-700 text-sm font-semibold">${row.working_hours ?? '<span class="text-slate-300 font-normal">--</span>'}</td>
      <td class="px-4 py-3">${statusBadge(row.status)}</td>
    </tr>
  `).join('');

  const from = offset + 1;
  const to   = offset + data.rows.length;
  document.getElementById('pagination-info').textContent = `Showing ${from} to ${to} of ${data.total} records`;
  renderPagination(data.page, data.last_page);
}

function renderPagination(page, lastPage) {
  const el = document.getElementById('pagination-controls');
  if (lastPage <= 1) { el.innerHTML = ''; return; }

  let html = `<button onclick="loadGrid(${page-1})" ${page===1?'disabled':''} class="px-3 py-1.5 text-sm border rounded-lg ${page===1?'opacity-40 cursor-not-allowed':''}">&lt;</button>`;
  for (let p = 1; p <= lastPage; p++) {
    if (p === 1 || p === lastPage || (p >= page-1 && p <= page+1)) {
      html += `<button onclick="loadGrid(${p})" class="px-3 py-1.5 text-sm border rounded-lg ${p===page?'bg-blue-600 text-white border-blue-600':'hover:bg-slate-50'}">${p}</button>`;
    } else if (p === page-2 || p === page+2) {
      html += `<span class="px-1 text-slate-400">…</span>`;
    }
  }
  html += `<button onclick="loadGrid(${page+1})" ${page===lastPage?'disabled':''} class="px-3 py-1.5 text-sm border rounded-lg ${page===lastPage?'opacity-40 cursor-not-allowed':''}">></button>`;
  el.innerHTML = html;
}

// ── Timeline ──────────────────────────────────────────────────────────────────
async function loadTimeline(code) {
  if (!code) return;
  const content = document.getElementById('timeline-content');
  content.innerHTML = '<div class="text-center py-8 text-slate-400 text-sm">Loading…</div>';

  const res  = await fetch(`/api/attendance/timeline/${code}?date=${currentDate}`);
  const data = await res.json();
  renderTimeline(data, content);
}

function renderTimeline(data, el) {
  const emp = data.employee;

  if (!data.events || !data.events.length) {
    el.innerHTML = `
    <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-100">
      <div class="size-11 rounded-full flex items-center justify-center text-white font-bold" style="background:${emp?.color ?? '#6366f1'}">${emp?.initials ?? '?'}</div>
      <div>
        <div class="font-bold text-slate-800">${emp?.name ?? 'Employee'}</div>
        <div class="text-xs text-slate-400">${emp?.department ?? '--'}</div>
      </div>
    </div>
    <div class="text-center py-8 text-slate-400 text-sm">No attendance recorded for this date.</div>`;
    return;
  }

  const sum = data.summary;
  const sessions = data.sessions ?? [];

  // Status label + style
  const statusStyles = {
    checked_out: { cls: 'bg-emerald-50 text-emerald-700 border-emerald-200', label: '✓ Checked Out' },
    in_office:   { cls: 'bg-blue-50 text-blue-700 border-blue-200',          label: '● Currently In Office' },
    absent:      { cls: 'bg-red-50 text-red-600 border-red-200',             label: '✗ Absent' },
    missing_out: { cls: 'bg-orange-50 text-orange-700 border-orange-200',    label: '⚠ Missing Out Punch' },
    incomplete:  { cls: 'bg-yellow-50 text-yellow-700 border-yellow-200',    label: '◑ Incomplete Session' },
  };
  const ss = statusStyles[sum.status] ?? { cls: 'bg-slate-50 text-slate-600 border-slate-200', label: sum.status };

  // Session cards
  const sessionHtml = sessions.length ? sessions.map(s => `
    <div class="bg-slate-50 rounded-xl p-3 border border-slate-100">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">Session ${s.index}</span>
        ${s.admin_note ? `<span class="text-xs text-violet-600 font-medium">Edited</span>` : ''}
      </div>
      <div class="flex items-center gap-2 text-sm">
        <div class="flex items-center gap-1.5">
          <span class="size-2.5 rounded-full bg-emerald-500 flex-shrink-0"></span>
          <span class="font-semibold text-slate-800">${s.check_in ?? '--'}</span>
        </div>
        <span class="text-slate-300 font-bold">→</span>
        <div class="flex items-center gap-1.5">
          <span class="size-2.5 rounded-full ${s.check_out ? 'bg-red-400' : 'bg-slate-300'} flex-shrink-0"></span>
          <span class="font-semibold ${s.check_out ? 'text-slate-800' : 'text-slate-400'}">${s.check_out ?? 'In Office'}</span>
        </div>
      </div>
      ${s.duration ? `<div class="text-xs text-slate-400 mt-1.5 font-medium">${s.duration}</div>` : ''}
    </div>
  `).join('') : `<div class="text-xs text-slate-400 text-center py-2">No paired sessions yet</div>`;

  // Raw events timeline — separate visible vs duplicate
  const visibleEvents = data.events.filter(e => !e.is_duplicate);
  const dupEvents     = data.events.filter(e => e.is_duplicate);
  const dupCount      = dupEvents.length;

  const eventTypeStyle = {
    check_in:  { dot: 'bg-emerald-500', text: 'text-emerald-700', label: 'CHECK-IN' },
    check_out: { dot: 'bg-red-400',     text: 'text-red-600',     label: 'CHECK-OUT' },
    duplicate: { dot: 'bg-amber-400',   text: 'text-amber-600',   label: 'DUPLICATE' },
    unknown:   { dot: 'bg-slate-300',   text: 'text-slate-500',   label: 'SCAN' },
  };

  const buildEventRow = (ev) => {
    const st = eventTypeStyle[ev.type] ?? eventTypeStyle.unknown;
    return `
    <div class="flex items-start gap-3 py-2">
      <div class="flex flex-col items-center flex-shrink-0 mt-1">
        <span class="size-2.5 rounded-full ${st.dot}"></span>
        <span class="w-px flex-1 bg-slate-100 mt-1" style="min-height:14px"></span>
      </div>
      <div class="flex-1 pb-1">
        <div class="flex items-center justify-between">
          <span class="text-xs font-bold ${st.text}">${st.label}</span>
          <span class="text-xs text-slate-400">${ev.verify_label ?? ''}</span>
        </div>
        <div class="text-sm font-semibold text-slate-700 mt-0.5">${ev.time}</div>
      </div>
    </div>`;
  };

  const dupSection = dupCount > 0 ? `
    <div class="mt-1">
      <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('span').textContent = this.nextElementSibling.classList.contains('hidden') ? 'Show' : 'Hide';"
        class="text-xs text-slate-400 hover:text-slate-600 flex items-center gap-1">
        <span>Show</span> ${dupCount} duplicate scan${dupCount > 1 ? 's' : ''} hidden
      </button>
      <div class="hidden mt-1 pl-1 border-l-2 border-amber-100">
        ${dupEvents.map(buildEventRow).join('')}
      </div>
    </div>` : '';

  el.innerHTML = `
  {{-- Employee header --}}
  <div class="flex items-center gap-3 mb-4 pb-4 border-b border-slate-100">
    <div class="size-11 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0" style="background:${emp.color}">${emp.initials}</div>
    <div class="flex-1 min-w-0">
      <div class="font-bold text-slate-800 truncate">${emp.name}</div>
      <div class="text-xs text-slate-400">${emp.department}</div>
    </div>
    <span class="text-xs font-semibold px-2.5 py-1 rounded-full border ${ss.cls} whitespace-nowrap flex-shrink-0">${ss.label}</span>
  </div>

  {{-- Summary row --}}
  <div class="grid grid-cols-3 gap-2 mb-4 text-xs text-center">
    <div class="bg-slate-50 rounded-xl p-2.5">
      <div class="text-slate-400">First In</div>
      <div class="font-bold text-emerald-600 mt-0.5">${sum.first_in ?? '--'}</div>
    </div>
    <div class="bg-slate-50 rounded-xl p-2.5">
      <div class="text-slate-400">Last Out</div>
      <div class="font-bold text-rose-500 mt-0.5">${sum.last_out ?? '--'}</div>
    </div>
    <div class="bg-slate-50 rounded-xl p-2.5">
      <div class="text-slate-400">Hours</div>
      <div class="font-bold text-slate-800 mt-0.5">${sum.working_hours ?? '--'}</div>
    </div>
  </div>

  {{-- Sessions --}}
  <div class="mb-4">
    <div class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-2">Today's Sessions</div>
    <div class="space-y-2">${sessionHtml}</div>
  </div>

  {{-- Raw Events Timeline --}}
  <div>
    <div class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-2">Raw Events</div>
    <div class="pl-1">
      ${visibleEvents.map(buildEventRow).join('')}
      ${dupSection}
    </div>
  </div>`;
}

// ── Recent Punches ────────────────────────────────────────────────────────────
let lastRecentPunchId = null;

async function loadRecentPunches() {
  try {
    const res  = await fetch('/api/attendance/recent-punches?limit=10');
    const data = await res.json();
    renderRecentPunches(data.punches);
    document.getElementById('recent-updated').textContent =
      'Updated ' + new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit',second:'2-digit'});
  } catch(e) {}
}

function renderRecentPunches(punches) {
  const el = document.getElementById('recent-punches-list');
  if (!punches || !punches.length) {
    el.innerHTML = '<div class="px-5 py-4 text-center text-slate-400 text-sm">No punches yet.</div>';
    return;
  }

  const isNew = lastRecentPunchId !== null && punches[0]?.id !== lastRecentPunchId;
  lastRecentPunchId = punches[0]?.id;

  el.innerHTML = `<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 divide-x divide-slate-50">
    ${punches.slice(0,10).map((p, i) => {
      const isIn  = p.direction === 'in';
      const isOut = p.direction === 'out';
      const dirBadge = isIn
        ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">↗ IN</span>'
        : isOut
        ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-600">↙ OUT</span>'
        : '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500">— —</span>';
      const highlightCls = (i === 0 && isNew) ? 'bg-blue-50' : '';
      return `
      <div class="px-4 py-3 flex items-center gap-3 ${highlightCls} ${i===0&&isNew?'fade-in':''}">
        <div class="size-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
             style="background:${p.color}">${p.initials}</div>
        <div class="min-w-0">
          <div class="font-semibold text-slate-800 text-xs truncate">${p.name}</div>
          <div class="text-[11px] text-slate-400 font-mono">${p.punch_date} &nbsp;${p.punch_time}</div>
          <div class="mt-0.5">${dirBadge}</div>
        </div>
      </div>`;
    }).join('')}
  </div>`;
}

// ── Stats ─────────────────────────────────────────────────────────────────────
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

// ── Sync Status ───────────────────────────────────────────────────────────────
async function loadSyncStatus() {
  try {
    const res  = await fetch('/api/sync/status');
    const data = await res.json();

    document.getElementById('sync-unsynced').textContent = data.total_unsynced;

    const devices = data.devices;
    if (devices.length) {
      const d = devices[0];
      document.getElementById('sync-last-time').textContent = d.last_activity_ts ?? '—';
      document.getElementById('sidebar-sync-status').innerHTML = devices.map(dv => {
        let clockLine = '';
        if (dv.device_time) {
          const ok = dv.clock_ok;
          const drift = dv.clock_drift_min;
          const driftTxt = (drift === 0) ? 'in sync'
            : (drift > 0 ? `+${drift} min ahead` : `${drift} min behind`);
          clockLine = `
            <div class="mt-1 pt-1 border-t border-slate-700/50">
              <div class="flex items-center justify-between">
                <span class="text-[10px] text-slate-500">Device Clock</span>
                <span class="text-[10px] font-semibold ${ok?'text-emerald-400':'text-rose-400'}">${ok?'✓ OK':'⚠ '+driftTxt}</span>
              </div>
              <div class="text-[11px] font-mono ${ok?'text-slate-300':'text-rose-300'}">${dv.device_time}</div>
            </div>`;
        }
        return `
        <div class="flex items-center justify-between">
          <span class="truncate">${dv.serial_number}</span>
          <span class="flex items-center gap-1 text-[10px] ${dv.is_online?'text-emerald-400':'text-slate-500'}">
            <span class="size-1.5 rounded-full ${dv.is_online?'bg-emerald-400 pulse-dot':'bg-slate-500'}"></span>
            ${dv.is_online?'Active':'Offline'}
          </span>
        </div>
        <div class="text-[10px] text-slate-500">Synced: ${dv.last_activity}</div>
        ${clockLine}`;
      }).join('');
    }
  } catch(e) {}
}

// ── Filters ───────────────────────────────────────────────────────────────────
function applyFilters() {
  currentDate   = document.getElementById('date-filter').value;
  currentDept   = document.getElementById('dept-filter').value;
  currentDevice = document.getElementById('device-filter').value;
  currentSearch = document.getElementById('search-input').value;
  loadGrid(1);
  loadStats();
}

function resetFilters() {
  document.getElementById('date-filter').value  = '{{ today()->toDateString() }}';
  document.getElementById('dept-filter').value  = '';
  document.getElementById('device-filter').value = '';
  document.getElementById('search-input').value  = '';
  currentDate = '{{ today()->toDateString() }}';
  currentDept = ''; currentDevice = ''; currentSearch = '';
  loadGrid(1); loadStats();
}

document.getElementById('date-filter').addEventListener('change', () => {
  currentDate = document.getElementById('date-filter').value;
  loadGrid(1); loadStats();
});
document.getElementById('search-input').addEventListener('input', debounce(() => {
  currentSearch = document.getElementById('search-input').value;
  loadGrid(1);
}, 400));

function debounce(fn, ms) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
}

function exportExcel() {
  const params = new URLSearchParams({ date: currentDate, department: currentDept, format: 'csv' });
  alert('Export feature: download from /api/attendance/export?' + params.toString());
}

// ── Sidebar nav ───────────────────────────────────────────────────────────────
function setPage(id) {
  document.querySelectorAll('.sidebar-nav').forEach(el => {
    el.classList.remove('bg-blue-600', 'text-white');
    el.classList.add('text-slate-400');
  });
  const btn = document.getElementById(id);
  if (btn) { btn.classList.add('bg-blue-600', 'text-white'); btn.classList.remove('text-slate-400'); }
}

// ── Countdown ─────────────────────────────────────────────────────────────────
function startCountdown() {
  countdownSecs = 5;
  const el = document.getElementById('countdown');
  const interval = setInterval(() => {
    countdownSecs--;
    if (countdownSecs <= 0) {
      clearInterval(interval);
      refreshAll();
      startCountdown();
    } else {
      el.textContent = '00:00:0' + countdownSecs;
    }
  }, 1000);
}

function refreshAll() {
  loadGrid(currentPage);
  loadStats();
  loadSyncStatus();
  loadRecentPunches();
}

// ── Init ──────────────────────────────────────────────────────────────────────
loadGrid(1);
loadStats();
loadSyncStatus();
loadRecentPunches();
startCountdown();
</script>
</body>
</html>
