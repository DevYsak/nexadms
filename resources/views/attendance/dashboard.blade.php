<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>BioSync — Attendance Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{height:100%;font-family:'Inter',system-ui,sans-serif;background:#F1F5F9;color:#0F172A}
::-webkit-scrollbar{width:4px;height:4px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:#CBD5E1;border-radius:99px}

/* ── Layout ── */
.shell{display:flex;height:100vh;overflow:hidden}
.sidebar{width:210px;flex-shrink:0;background:#fff;border-right:1px solid #E2E8F0;display:flex;flex-direction:column}
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0}
.topbar{background:#fff;border-bottom:1px solid #E2E8F0;padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;gap:16px}
.body{flex:1;overflow-y:auto;padding:20px 24px;display:flex;flex-direction:column;gap:16px}

/* ── Sidebar ── */
.logo-wrap{padding:18px 16px 14px;border-bottom:1px solid #F1F5F9;display:flex;align-items:center;gap:10px}
.logo-icon{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#2563EB,#7C3AED);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.logo-name{font-size:14px;font-weight:800;color:#0F172A}
.logo-sub{font-size:11px;color:#94A3B8;margin-top:1px}
.nav{flex:1;padding:10px 8px}
.nav-btn{display:flex;align-items:center;gap:10px;width:100%;padding:8px 12px;border-radius:8px;border:none;background:none;font-size:13px;font-weight:500;color:#64748B;cursor:pointer;font-family:inherit;text-align:left;transition:all .12s}
.nav-btn:hover{background:#F8FAFC;color:#0F172A}
.nav-btn.active{background:#EFF6FF;color:#2563EB;font-weight:600}
.nav-btn svg{flex-shrink:0;opacity:.7}
.nav-btn.active svg{opacity:1}
.sync-widget{padding:10px 10px 14px;border-top:1px solid #F1F5F9}
.sync-box{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:12px}
.sync-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}
.sync-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94A3B8}
.sync-status{font-size:12px;color:#64748B;line-height:1.4}
.sync-next{font-size:11px;color:#94A3B8;text-align:center;margin-top:8px}
.sync-time{font-weight:700;color:#2563EB;font-variant-numeric:tabular-nums}

/* ── Topbar ── */
.page-title{font-size:18px;font-weight:800;color:#0F172A}
.page-sub{font-size:12px;color:#94A3B8;margin-top:2px}
.live-pill{display:flex;align-items:center;gap:6px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:99px;padding:5px 14px;font-size:12px;font-weight:600;color:#16A34A}
.date-input{height:36px;border:1px solid #E2E8F0;border-radius:8px;padding:0 12px;font-size:13px;font-family:inherit;outline:none;color:#0F172A}
.date-input:focus{border-color:#2563EB;box-shadow:0 0 0 3px rgba(37,99,235,.1)}

/* ── KPI Cards ── */
.kpi-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:12px}
.kpi{background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:16px 20px;display:flex;align-items:center;gap:14px}
.kpi-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.kpi-num{font-size:26px;font-weight:900;color:#0F172A;line-height:1;letter-spacing:-1px}
.kpi-lbl{font-size:11.5px;color:#64748B;font-weight:500;margin-top:3px}

/* ── Toolbar ── */
.toolbar{background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:12px 16px;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.tb-search{position:relative;flex:1;min-width:180px}
.tb-search svg{position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none}
.tb-input{height:36px;border:1px solid #E2E8F0;border-radius:8px;padding:0 12px;font-size:13px;font-family:inherit;outline:none;color:#0F172A;width:100%}
.tb-search .tb-input{padding-left:32px}
.tb-input:focus{border-color:#2563EB;box-shadow:0 0 0 3px rgba(37,99,235,.1)}
.btn-primary{height:36px;padding:0 18px;background:#2563EB;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;transition:background .12s}
.btn-primary:hover{background:#1D4ED8}
.btn-ghost{height:36px;padding:0 14px;background:#fff;color:#64748B;border:1px solid #E2E8F0;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;font-family:inherit;transition:all .12s}
.btn-ghost:hover{background:#F8FAFC;color:#0F172A}
.btn-export{height:36px;padding:0 14px;background:#fff;color:#059669;border:1px solid #D1FAE5;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:6px;transition:all .12s}
.btn-export:hover{background:#ECFDF5}

/* ── Card ── */
.card{background:#fff;border:1px solid #E2E8F0;border-radius:12px}

/* ── Table ── */
.data-table{width:100%;border-collapse:collapse;font-size:13.5px}
.data-table thead th{padding:10px 16px;text-align:left;font-size:11px;font-weight:700;color:#94A3B8;text-transform:uppercase;letter-spacing:.05em;background:#F8FAFC;border-bottom:1px solid #E2E8F0;white-space:nowrap;position:sticky;top:0;z-index:5}
.data-table tbody tr{border-bottom:1px solid #F1F5F9;cursor:pointer;transition:background .1s}
.data-table tbody tr:last-child{border-bottom:none}
.data-table tbody tr:hover{background:#F8FAFC}
.data-table tbody tr.selected{background:#EFF6FF}
.data-table td{padding:11px 16px;vertical-align:middle}

/* ── Avatar ── */
.av{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:#fff;flex-shrink:0}

/* ── Status badges ── */
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;white-space:nowrap}
.b-green{background:#ECFDF5;color:#059669}
.b-blue{background:#EFF6FF;color:#2563EB}
.b-amber{background:#FFFBEB;color:#D97706}
.b-red{background:#FEF2F2;color:#DC2626}
.b-gray{background:#F1F5F9;color:#64748B}
.dot{width:6px;height:6px;border-radius:50%;display:inline-block;flex-shrink:0}
.dg{background:#10B981} .db{background:#2563EB} .da{background:#F59E0B} .dr{background:#EF4444} .ds{background:#94A3B8}

/* ── Pagination ── */
.pg-btn{min-width:32px;height:32px;padding:0 8px;border:1px solid #E2E8F0;border-radius:7px;background:#fff;font-size:12px;color:#64748B;cursor:pointer;font-family:inherit;transition:all .12s}
.pg-btn:hover:not(:disabled){background:#F8FAFC;color:#0F172A}
.pg-btn.act{background:#2563EB;border-color:#2563EB;color:#fff;font-weight:700}
.pg-btn:disabled{opacity:.4;cursor:not-allowed}

/* ── Pulse ── */
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.35}}
.pulse{animation:pulse 2s infinite}
@keyframes fadeUp{from{opacity:0;transform:translateY(3px)}to{opacity:1;transform:none}}
.fu{animation:fadeUp .2s ease both}
@keyframes spin{to{transform:rotate(360deg)}}
.spin{animation:spin .6s linear infinite}

/* ── Journey Section ── */
.journey-scroll{display:flex;align-items:flex-start;gap:0;overflow-x:auto;padding:4px 0 8px}
.j-card{flex-shrink:0;width:168px;border-radius:12px;padding:14px 16px;border:1.5px solid}
.j-card-in{background:#F0FDF4;border-color:#86EFAC}
.j-card-out{background:#FFF1F2;border-color:#FCA5A5}
.j-card-status{background:#EFF6FF;border-color:#BFDBFE}
.j-arrow{flex-shrink:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;padding:0 10px;min-width:90px}
.j-arrow-line{font-size:18px;color:#CBD5E1}
.j-arrow-label{font-size:11px;font-weight:600;color:#475569;text-align:center;line-height:1.3}
.j-arrow-sub{font-size:10px;color:#94A3B8;text-align:center}
.j-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:6px}
.j-time{font-size:22px;font-weight:900;letter-spacing:-1px;line-height:1;margin-bottom:8px}
.j-meta{font-size:11px;color:#64748B;font-weight:500}

/* ── Session summary cards ── */
.sess-grid{display:grid;gap:12px;margin-top:12px}
.sess-card{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:14px 16px}
.sess-head{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94A3B8;margin-bottom:8px}
.sess-time{font-size:15px;font-weight:800;color:#0F172A;margin-bottom:4px}
.sess-meta{font-size:12px;color:#64748B;font-weight:500}
.sess-total{background:linear-gradient(135deg,#EFF6FF,#F0FDF4);border:1px solid #BFDBFE;border-radius:10px;padding:14px 16px}

/* ── Right Panel ── */
.panel-emp{display:flex;align-items:center;gap:12px;padding-bottom:14px;border-bottom:1px solid #F1F5F9;margin-bottom:14px}
.panel-stat{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px}
.pstat-in{background:#F0FDF4;border:1.5px solid #86EFAC;border-radius:10px;padding:14px}
.pstat-out{border:1.5px solid;border-radius:10px;padding:14px}
.pstat-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:5px}
.pstat-val{font-size:20px;font-weight:900;color:#0F172A;letter-spacing:-.5px;line-height:1}
.pstat-row{display:flex;gap:8px;margin-bottom:12px}
.pstat-sm{flex:1;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:12px}
.pstat-sm-label{font-size:10px;font-weight:600;color:#94A3B8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px}
.pstat-sm-val{font-size:18px;font-weight:900;color:#0F172A;letter-spacing:-.5px}
.curr-status{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:12px 14px;margin-bottom:12px;display:flex;align-items:center;gap:10px}

/* ── Right panel timeline ── */
.tl-item{display:flex;gap:12px;padding:8px 0}
.tl-dot-col{display:flex;flex-direction:column;align-items:center;padding-top:3px;flex-shrink:0;width:14px}
.tl-dot{width:12px;height:12px;border-radius:50%;border:2px solid;flex-shrink:0}
.tl-line{width:2px;background:#E2E8F0;flex:1;margin:3px 0;min-height:12px}
.tl-time{font-size:12px;font-weight:700;color:#64748B;width:54px;flex-shrink:0;padding-top:1px;font-variant-numeric:tabular-nums}
.tl-body{flex:1;min-width:0}
.tl-type{font-size:13px;font-weight:700;color:#0F172A;line-height:1.2}
.tl-meta{font-size:11.5px;color:#94A3B8;margin-top:2px}
.tl-badge{display:inline-block;font-size:10.5px;font-weight:600;padding:2px 8px;border-radius:99px;margin-top:4px}
.tl-worked{color:#059669;background:#ECFDF5}
.tl-open{color:#2563EB;background:#EFF6FF}

/* ── Dup banner ── */
.dup-banner{background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:10px;margin-top:10px;cursor:pointer}
.dup-icon{width:28px;height:28px;border-radius:7px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.dup-text{flex:1;font-size:12px;font-weight:600;color:#92400E}
.dup-sub{font-size:11px;color:#B45309;font-weight:400;display:block;margin-top:1px}

/* ── Split layout ── */
.split{display:flex;gap:14px;align-items:flex-start;min-height:0}
.split-left{flex:1;min-width:0;display:flex;flex-direction:column;gap:14px}
.split-right{width:370px;flex-shrink:0;display:flex;flex-direction:column;gap:0;max-height:calc(100vh - 210px);overflow-y:auto}
</style>
</head>

<body>
<div class="shell">

{{-- ═══════════ SIDEBAR ═══════════ --}}
<aside class="sidebar">
  <div class="logo-wrap">
    <div class="logo-icon">
      <svg width="18" height="18" fill="none" stroke="#fff" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/></svg>
    </div>
    <div><div class="logo-name">BioSync</div><div class="logo-sub">Attendance System</div></div>
  </div>

  <nav class="nav">
    @php $navItems=[['id'=>'nd','label'=>'Dashboard','ico'=>'home'],['id'=>'nl','label'=>'Attendance Log','ico'=>'list','active'=>true],['id'=>'nlv','label'=>'Live Monitor','ico'=>'activity'],['id'=>'ne','label'=>'Employees','ico'=>'users'],['id'=>'ndev','label'=>'Devices','ico'=>'cpu'],['id'=>'nr','label'=>'Reports','ico'=>'bar'],['id'=>'ns','label'=>'Sync Status','ico'=>'refresh'],['id'=>'nset','label'=>'Settings','ico'=>'settings']]; @endphp
    @foreach($navItems as $n)
    <button class="nav-btn {{ ($n['active']??false)?'active':'' }}" id="{{ $n['id'] }}" onclick="setNav('{{ $n['id'] }}')">
      @if($n['ico']==='home')<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
      @elseif($n['ico']==='list')<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      @elseif($n['ico']==='activity')<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      @elseif($n['ico']==='users')<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
      @elseif($n['ico']==='cpu')<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><path d="M9 1v3M15 1v3M9 20v3M15 20v3M1 9h3M1 15h3M20 9h3M20 15h3"/></svg>
      @elseif($n['ico']==='bar')<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      @elseif($n['ico']==='refresh')<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/></svg>
      @else<svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93A10 10 0 0 0 4.93 19.07"/><path d="M19.07 19.07A10 10 0 0 0 4.93 4.93"/></svg>@endif
      {{ $n['label'] }}
    </button>
    @endforeach
  </nav>

  <div class="sync-widget">
    <div class="sync-box">
      <div class="sync-row"><span class="sync-label">Device Sync Status</span><span class="dot dg pulse"></span></div>
      <div class="sync-status" id="sidebar-devices">Loading…</div>
      <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:11px;color:#94A3B8;">
        <div>Last Sync<br><strong id="last-sync-rel" style="color:#0F172A;font-size:12px;">—</strong></div>
        <div style="text-align:right;">Next Sync<br><strong id="next-sync-time" style="color:#0F172A;font-size:12px;">—</strong></div>
      </div>
      <div style="margin-top:8px;padding-top:8px;border-top:1px solid #F1F5F9;display:flex;justify-content:space-between;font-size:11px;">
        <span>Device Status <strong id="device-online-label" style="color:#94A3B8;">—</strong></span>
        <span>Pending <strong id="pending-punches">0</strong></span>
      </div>
    </div>
  </div>
</aside>

{{-- ═══════════ MAIN ═══════════ --}}
<div class="main">

  {{-- Topbar --}}
  <div class="topbar">
    <div>
      <div class="page-title">Attendance Dashboard</div>
      <div class="page-sub">Live biometric attendance monitoring system</div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
      <div class="live-pill">
        <span class="dot dg pulse"></span>
        <span id="last-sync-time">Live sync: just now</span>
      </div>
      <input type="date" id="date-filter" value="{{ $date }}" class="date-input">
    </div>
  </div>

  {{-- Body --}}
  <div class="body">

    {{-- KPI cards --}}
    <div class="kpi-grid">
      @php $kpis=[
        ['id'=>'k-emp',  'lbl'=>'Total Employees','val'=>$stats['total_employees'],'bg'=>'#EFF6FF','ic'=>'#2563EB','ico'=>'users'],
        ['id'=>'k-pres', 'lbl'=>'Present Today',  'val'=>$stats['present'],        'bg'=>'#ECFDF5','ic'=>'#059669','ico'=>'check'],
        ['id'=>'k-abs',  'lbl'=>'Absent Today',   'val'=>$stats['absent'],         'bg'=>'#FEF2F2','ic'=>'#DC2626','ico'=>'x'],
        ['id'=>'k-late', 'lbl'=>'Late Arrivals',  'val'=>$stats['late'],           'bg'=>'#FFFBEB','ic'=>'#D97706','ico'=>'clock'],
        ['id'=>'k-pun',  'lbl'=>'Total Punches',  'val'=>$stats['total_punches'],  'bg'=>'#F5F3FF','ic'=>'#7C3AED','ico'=>'finger'],
        ['id'=>'k-dev',  'lbl'=>'Devices Online', 'val'=>$stats['devices_online'], 'bg'=>'#ECFEFF','ic'=>'#0891B2','ico'=>'wifi'],
      ]; @endphp
      @foreach($kpis as $k)
      <div class="kpi">
        <div class="kpi-icon" style="background:{{ $k['bg'] }}">
          <svg width="20" height="20" fill="none" stroke="{{ $k['ic'] }}" stroke-width="2" viewBox="0 0 24 24">
            @if($k['ico']==='users')<path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
            @elseif($k['ico']==='check')<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            @elseif($k['ico']==='x')<path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            @elseif($k['ico']==='clock')<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            @elseif($k['ico']==='finger')<path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4"/>
            @else<path stroke-linecap="round" stroke-linejoin="round" d="M5 12.55a11 11 0 0114.08 0M1.42 9a16 16 0 0121.16 0M8.53 16.11a6 6 0 016.95 0"/><line x1="12" y1="20" x2="12.01" y2="20"/>@endif
          </svg>
        </div>
        <div><div class="kpi-num" id="{{ $k['id'] }}">{{ $k['val'] }}</div><div class="kpi-lbl">{{ $k['lbl'] }}</div></div>
      </div>
      @endforeach
    </div>

    {{-- Toolbar --}}
    <div class="toolbar">
      <div class="tb-search">
        <svg width="14" height="14" fill="none" stroke="#94A3B8" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="search-input" placeholder="Search employee by name or code…" class="tb-input">
      </div>
      <select id="dept-filter" class="tb-input" style="min-width:150px;">
        <option value="">All Departments</option>
        @foreach($departments as $d)<option value="{{ $d }}" {{ $department===$d?'selected':'' }}>{{ $d }}</option>@endforeach
      </select>
      <select id="device-filter" class="tb-input" style="min-width:130px;">
        <option value="">All Devices</option>
        @foreach($devices as $dv)<option value="{{ $dv->id }}" {{ $deviceId==$dv->id?'selected':'' }}>{{ $dv->serial_number }}</option>@endforeach
      </select>
      <button class="btn-primary" onclick="applyFilters()">Apply</button>
      <button class="btn-ghost" onclick="resetFilters()">Reset</button>
      <button class="btn-export" onclick="exportExcel()" style="margin-left:auto;">
        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Export
      </button>
    </div>

    {{-- Split: table + right panel --}}
    <div class="split">

      {{-- Left column --}}
      <div class="split-left">

        {{-- Attendance Table --}}
        <div class="card">
          <div style="padding:14px 18px;border-bottom:1px solid #F1F5F9;display:flex;align-items:center;gap:10px;">
            <span style="font-size:14px;font-weight:700;color:#0F172A;">Attendance Summary</span>
            <span id="total-badge" style="background:#F1F5F9;color:#64748B;font-size:11px;font-weight:700;padding:2px 9px;border-radius:99px;">0</span>
            <div id="grid-loading" style="display:none;margin-left:auto;">
              <svg class="spin" width="16" height="16" fill="none" stroke="#2563EB" stroke-width="2.5" viewBox="0 0 24 24"><path opacity=".25" stroke-linecap="round" d="M12 2a10 10 0 100 20A10 10 0 0012 2z"/><path stroke-linecap="round" d="M12 2a10 10 0 0110 10"/></svg>
            </div>
          </div>
          <div style="overflow-x:auto;max-height:360px;overflow-y:auto;">
            <table class="data-table">
              <thead>
                <tr>
                  <th style="width:36px;">#</th>
                  <th>Employee</th>
                  <th>Code</th>
                  <th>First Check-In</th>
                  <th>Last Check-Out</th>
                  <th>Working Hours</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="attendance-tbody">
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#94A3B8;font-size:13px;">Loading…</td></tr>
              </tbody>
            </table>
          </div>
          <div style="padding:10px 18px;border-top:1px solid #F1F5F9;display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:12px;color:#64748B;" id="pagination-info">—</span>
            <div style="display:flex;gap:4px;" id="pagination-controls"></div>
          </div>
        </div>

        {{-- Journey Section (hidden until employee selected) --}}
        <div id="journey-section" style="display:none;" class="card">
          <div style="padding:14px 18px;border-bottom:1px solid #F1F5F9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
            <span style="font-size:14px;font-weight:700;color:#0F172A;">Today's Attendance Journey</span>
            <div style="display:flex;gap:8px;" id="journey-stats">
              <span style="background:#F1F5F9;color:#64748B;font-size:11.5px;font-weight:600;padding:4px 10px;border-radius:99px;">Total Punches: <span id="js-punches">—</span></span>
              <span style="background:#EFF6FF;color:#2563EB;font-size:11.5px;font-weight:600;padding:4px 10px;border-radius:99px;">Sessions: <span id="js-sessions">—</span></span>
              <span style="background:#ECFDF5;color:#059669;font-size:11.5px;font-weight:600;padding:4px 10px;border-radius:99px;">Total Working: <span id="js-hours">—</span></span>
            </div>
          </div>
          <div style="padding:16px 18px;">
            <div class="journey-scroll" id="journey-cards"></div>
            <div id="session-summary-cards"></div>
            <div id="dup-note"></div>
            <div id="dup-note-info" style="display:none;margin-top:10px;padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;font-size:11.5px;color:#64748B;">
              <svg style="display:inline;vertical-align:middle;margin-right:4px;" width="14" height="14" fill="none" stroke="#94A3B8" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Note: Duplicate punches within 60 seconds are automatically hidden and not used in calculations.
            </div>
          </div>
        </div>

      </div>{{-- end left column --}}

      {{-- Right column: Employee Detail --}}
      <div class="split-right">
        <div class="card" style="flex:1;">
          <div style="padding:14px 18px;border-bottom:1px solid #F1F5F9;display:flex;align-items:center;justify-content:space-between;">
            <span style="font-size:13.5px;font-weight:700;color:#0F172A;">Employee Detail</span>
            <select id="timeline-emp-select" onchange="loadTimeline(this.value)" class="tb-input" style="font-size:12px;height:30px;max-width:180px;">
              <option value="">Select employee</option>
              @foreach(\App\Models\Employee::active()->orderBy('name')->get() as $emp)
                <option value="{{ $emp->employee_code }}">{{ $emp->name }} ({{ $emp->employee_code }})</option>
              @endforeach
            </select>
          </div>
          <div id="timeline-content" style="padding:16px 18px;overflow-y:auto;">
            <div style="text-align:center;padding:48px 0;color:#94A3B8;">
              <svg width="40" height="40" fill="none" stroke="#E2E8F0" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 10px;display:block;"><circle cx="12" cy="8" r="4"/><path stroke-linecap="round" d="M4 20c0-4 3.582-7 8-7s8 3 8 7"/></svg>
              <span style="font-size:13px;">Select an employee</span>
            </div>
          </div>
        </div>
      </div>

    </div>{{-- end split --}}

  </div>{{-- end body --}}
</div>{{-- end main --}}
</div>{{-- end shell --}}

<script>
// ══════════════════════════════════════════════
// STATE
// ══════════════════════════════════════════════
let currentPage   = 1;
let currentDate   = document.getElementById('date-filter').value;
let currentDept   = '', currentDevice = '', currentSearch = '';
let countdownSecs = 5;
let lastSelected  = null;

// ══════════════════════════════════════════════
// STATUS BADGE
// ══════════════════════════════════════════════
const badge = s => {
  const m = {
    checked_out:   `<span class="badge b-green"><span class="dot dg"></span>Checked Out</span>`,
    present:       `<span class="badge b-green"><span class="dot dg"></span>Checked Out</span>`,
    in_office:     `<span class="badge b-blue"><span class="dot db pulse"></span>In Office</span>`,
    late:          `<span class="badge b-amber"><span class="dot da"></span>Late</span>`,
    no_attendance: `<span class="badge b-gray">Absent</span>`,
    absent:        `<span class="badge b-red"><span class="dot dr"></span>Absent</span>`,
    missing_out:   `<span class="badge b-amber">Missing Out</span>`,
  };
  return m[s] || `<span class="badge b-gray">—</span>`;
};

const av = (initials, color) =>
  `<div class="av" style="background:${color}">${initials}</div>`;

// ══════════════════════════════════════════════
// GRID
// ══════════════════════════════════════════════
async function loadGrid(page = 1) {
  currentPage = page;
  document.getElementById('grid-loading').style.display = 'block';
  const p = new URLSearchParams({ date:currentDate, department:currentDept, device:currentDevice, search:currentSearch, page, per_page:10 });
  try {
    const res  = await fetch(`/api/attendance/grid?${p}`, { headers:{Accept:'application/json'} });
    const data = await res.json();
    renderGrid(data);
    document.getElementById('last-sync-time').textContent = 'Live sync: ' + new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
  } catch {
    document.getElementById('attendance-tbody').innerHTML = `<tr><td colspan="7" style="text-align:center;padding:40px;color:#EF4444;font-size:13px;">Failed to load. Retrying…</td></tr>`;
  } finally { document.getElementById('grid-loading').style.display = 'none'; }
}

function renderGrid(data) {
  const tb = document.getElementById('attendance-tbody');
  document.getElementById('total-badge').textContent = data.total;
  if (!data.rows.length) {
    tb.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:48px;color:#94A3B8;font-size:13px;">No attendance records found.</td></tr>`;
    document.getElementById('pagination-info').textContent = 'No records';
    document.getElementById('pagination-controls').innerHTML = '';
    return;
  }
  const off = (data.page - 1) * data.per_page;
  tb.innerHTML = data.rows.map((r, i) => `
    <tr class="fu" data-code="${r.code}" onclick="selectRow(this,'${r.code}')" style="animation-delay:${i*15}ms">
      <td style="color:#CBD5E1;font-size:12px;font-weight:600;">${off+i+1}</td>
      <td>
        <div style="display:flex;align-items:center;gap:10px;">
          ${av(r.initials, r.avatar_color)}
          <div>
            <div style="font-size:13.5px;font-weight:700;color:#0F172A;line-height:1.3">${r.name}</div>
            <div style="font-size:11.5px;color:#94A3B8">${r.department||'—'}</div>
          </div>
        </div>
      </td>
      <td style="font-size:12px;font-weight:600;color:#64748B;font-variant-numeric:tabular-nums">${r.code}</td>
      <td style="font-size:13px;font-weight:700;color:#059669">${r.first_in ? '↑ '+r.first_in : '<span style="color:#CBD5E1;font-weight:400">—</span>'}</td>
      <td style="font-size:13px;font-weight:700;color:#DC2626">${r.last_out ? '↓ '+r.last_out : '<span style="color:#CBD5E1;font-weight:400">—</span>'}</td>
      <td style="font-size:13.5px;font-weight:800;color:#2563EB">${r.working_hours || '<span style="color:#CBD5E1;font-weight:400;font-size:13px">—</span>'}</td>
      <td>${badge(r.status)}</td>
    </tr>`).join('');
  const from=off+1, to=off+data.rows.length;
  document.getElementById('pagination-info').textContent = `Showing ${from} to ${to} of ${data.total} employees`;
  renderPagination(data.page, data.last_page);
}

function selectRow(tr, code) {
  document.querySelectorAll('.data-table tbody tr').forEach(r => r.classList.remove('selected'));
  tr.classList.add('selected');
  document.getElementById('timeline-emp-select').value = code;
  loadTimeline(code);
}

function renderPagination(page, last) {
  const el = document.getElementById('pagination-controls');
  if (last <= 1) { el.innerHTML=''; return; }
  let h = `<button class="pg-btn" onclick="loadGrid(${page-1})" ${page===1?'disabled':''}>‹</button>`;
  for (let p=1; p<=last; p++) {
    if (p===1||p===last||(p>=page-1&&p<=page+1)) h += `<button class="pg-btn ${p===page?'act':''}" onclick="loadGrid(${p})">${p}</button>`;
    else if (p===page-2||p===page+2) h += `<span style="color:#CBD5E1;padding:0 4px;font-size:12px">…</span>`;
  }
  h += `<button class="pg-btn" onclick="loadGrid(${page+1})" ${page===last?'disabled':''}>›</button>`;
  el.innerHTML = h;
}

// ══════════════════════════════════════════════
// TIMELINE + JOURNEY
// ══════════════════════════════════════════════
async function loadTimeline(code) {
  if (!code) return;
  lastSelected = code;
  document.getElementById('timeline-content').innerHTML = `<div style="text-align:center;padding:48px 0;color:#94A3B8;font-size:13px;">Loading…</div>`;
  document.getElementById('journey-section').style.display = 'none';
  try {
    const res  = await fetch(`/api/attendance/timeline/${code}?date=${currentDate}`);
    const data = await res.json();
    renderPanel(data);
    renderJourney(data);
  } catch {
    document.getElementById('timeline-content').innerHTML = `<div style="text-align:center;padding:32px;color:#EF4444;font-size:13px;">Failed to load.</div>`;
  }
}

// ─── Right panel ────────────────────────────────────────────────
function renderPanel(data) {
  const emp = data.employee, sum = data.summary, sessions = data.sessions ?? [], dupCount = sum?.dup_count ?? 0;
  const el  = document.getElementById('timeline-content');

  const SC = {
    checked_out: {color:'#059669',bg:'#ECFDF5',border:'#86EFAC',label:'Checked Out'},
    in_office:   {color:'#2563EB',bg:'#EFF6FF',border:'#93C5FD',label:'In Office'},
    no_attendance:{color:'#94A3B8',bg:'#F8FAFC',border:'#E2E8F0',label:'No Attendance'},
  };
  const sc = SC[sum?.status] ?? SC.no_attendance;
  const hasOut = !!sum?.last_out;

  // Build timeline from events (non-duplicate)
  const visEvs = (data.events ?? []).filter(e => !e.is_duplicate);
  let prevType = null, tlHtml = '';

  visEvs.forEach((ev, i) => {
    const isIn = ev.type === 'check_in';
    const dotColor = isIn ? '#059669' : '#DC2626';
    const borderColor = isIn ? '#86EFAC' : '#FCA5A5';
    const isLast = i === visEvs.length - 1;
    const nextEv = visEvs[i + 1];

    // Find session with this check_in or check_out to get worked duration
    let workedLabel = '', sessionBadge = '';
    if (!isIn && prevType === 'check_in') {
      // Find session ending at this time
      const s = sessions.find(s => s.check_out === ev.time);
      if (s?.duration) workedLabel = `<span class="tl-badge tl-worked">Worked ${s.duration}</span>`;
    }
    if (isIn && isLast) {
      const s = sessions.find(s => s.check_in === ev.time && !s.check_out);
      if (s) sessionBadge = `<span class="tl-badge tl-open">Current Session Open</span>${s.current_duration ? `<br><span class="tl-badge tl-worked" style="margin-top:3px;">Worked ${s.current_duration}</span>` : ''}`;
    }

    tlHtml += `
      <div class="tl-item">
        <div class="tl-dot-col">
          <div class="tl-dot" style="border-color:${borderColor};background:${isIn?'#ECFDF5':'#FFF1F2'}">
            <div style="width:6px;height:6px;border-radius:50%;background:${dotColor};margin:1px;"></div>
          </div>
          ${!isLast ? '<div class="tl-line"></div>' : ''}
        </div>
        <div class="tl-time">${ev.time}</div>
        <div class="tl-body">
          <div class="tl-type" style="color:${dotColor}">${isIn ? 'Check-In' : 'Check-Out'}</div>
          ${ev.verify_label ? `<div class="tl-meta">Main Gate (${ev.verify_label})</div>` : ''}
          ${workedLabel}${sessionBadge}
        </div>
      </div>`;
    prevType = ev.type;
  });

  if (!tlHtml) tlHtml = `<div style="text-align:center;padding:20px 0;color:#94A3B8;font-size:13px;">No attendance recorded.</div>`;

  el.innerHTML = `
    <div class="panel-emp">
      <div class="av" style="width:42px;height:42px;font-size:14px;background:${emp?.color??'#6366f1'}">${emp?.initials??'?'}</div>
      <div style="flex:1;min-width:0">
        <div style="font-size:14px;font-weight:800;color:#0F172A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${emp?.name??'—'}</div>
        <div style="font-size:12px;color:#64748B;margin-top:1px">${emp?.department??'—'}</div>
      </div>
      <span style="display:inline-flex;align-items:center;gap:5px;background:${sc.bg};border:1px solid ${sc.border};color:${sc.color};font-size:11px;font-weight:700;padding:4px 10px;border-radius:99px;flex-shrink:0">
        <span style="width:6px;height:6px;border-radius:50%;background:${sc.color};display:inline-block;${sum?.status==='in_office'?'animation:pulse 2s infinite':''}"></span>
        ${sc.label}
      </span>
    </div>

    <div class="panel-stat">
      <div class="pstat-in">
        <div class="pstat-label" style="color:#059669">↑ FIRST CHECK-IN</div>
        <div class="pstat-val">${sum?.first_in??'—'}</div>
      </div>
      <div class="pstat-out" style="background:${hasOut?'#FFF1F2':'#F8FAFC'};border-color:${hasOut?'#FCA5A5':'#E2E8F0'}">
        <div class="pstat-label" style="color:${hasOut?'#DC2626':'#94A3B8'}">↓ LAST CHECK-OUT</div>
        <div class="pstat-val" style="color:${hasOut?'#0F172A':'#CBD5E1'}">${sum?.last_out??'Still Inside'}</div>
      </div>
    </div>

    <div class="pstat-row">
      <div class="pstat-sm">
        <div class="pstat-sm-label">Working Hours</div>
        <div class="pstat-sm-val" style="color:#2563EB">${sum?.working_hours??'—'}</div>
      </div>
      <div class="pstat-sm" style="text-align:right">
        <div class="pstat-sm-label">Sessions</div>
        <div class="pstat-sm-val">${sessions.length}</div>
      </div>
    </div>

    ${sum?.status === 'in_office' ? `
    <div class="curr-status" style="margin-bottom:12px">
      <svg width="24" height="24" fill="none" stroke="#2563EB" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path stroke-linecap="round" d="M3 20c0-4 4-7 9-7s9 3 9 7"/></svg>
      <div>
        <div style="font-size:13px;font-weight:700;color:#2563EB">In Office</div>
        ${sessions.find(s=>!s.check_out) ? `<div style="font-size:11.5px;color:#64748B">Since ${sessions.find(s=>!s.check_out).check_in}</div>` : ''}
      </div>
    </div>` : ''}

    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94A3B8;margin-bottom:8px;">Today's Timeline</div>
    ${tlHtml}

    ${dupCount > 0 ? `
    <div class="dup-banner" onclick="document.getElementById('dup-punches-detail').style.display=document.getElementById('dup-punches-detail').style.display==='none'?'block':'none'">
      <div class="dup-icon">
        <svg width="14" height="14" fill="none" stroke="#D97706" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      </div>
      <div class="dup-text">
        ${dupCount} duplicate punch${dupCount>1?'es have':' has'} been hidden
        <span class="dup-sub">Click to view all punches</span>
      </div>
      <svg width="16" height="16" fill="none" stroke="#B45309" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
    </div>
    <div id="dup-punches-detail" style="display:none;margin-top:8px;padding:10px;background:#FFFBEB;border-radius:8px;font-size:12px;color:#92400E;">
      ${(data.events??[]).filter(e=>e.is_duplicate).map(e=>`<div style="padding:3px 0;">${e.time} — ${e.verify_label??'Unknown'}</div>`).join('')}
    </div>` : ''}`;
}

// ─── Journey section (bottom center) ────────────────────────────
function renderJourney(data) {
  const sessions = data.sessions ?? [], sum = data.summary, dupCount = sum?.dup_count ?? 0;
  if (!sessions.length) return;

  document.getElementById('js-punches').textContent  = sum?.total_punches ?? 0;
  document.getElementById('js-sessions').textContent = sessions.length;
  document.getElementById('js-hours').textContent    = sum?.working_hours ?? '—';

  // ─ Horizontal journey cards ─
  let cards = '';
  sessions.forEach((s, i) => {
    // CHECK-IN card
    cards += `
      <div class="j-card j-card-in">
        <div class="j-title" style="color:#059669">CHECK-IN</div>
        <div class="j-time" style="color:#0F172A">${s.check_in??'—'}</div>
        <div class="j-meta">Device: Main Gate</div>
      </div>`;

    if (s.check_out) {
      // Worked arrow
      cards += `
        <div class="j-arrow">
          <div class="j-arrow-line">→</div>
          <div class="j-arrow-label">Worked</div>
          <div class="j-arrow-label" style="color:#0F172A;font-size:13px;font-weight:800">${s.duration}</div>
        </div>`;
      // CHECK-OUT card
      cards += `
        <div class="j-card j-card-out">
          <div class="j-title" style="color:#DC2626">CHECK-OUT</div>
          <div class="j-time" style="color:#0F172A">${s.check_out}</div>
          <div class="j-meta">Device: Main Gate</div>
        </div>`;
      // Break arrow (if next session)
      if (sessions[i+1]) {
        cards += `
          <div class="j-arrow">
            <div class="j-arrow-line">→</div>
            <div class="j-arrow-label">Break</div>
            <div class="j-arrow-label" style="color:#0F172A;font-size:13px;font-weight:700">${sessions[i+1].break_before??''}</div>
            <div class="j-arrow-sub">(Out of Office)</div>
          </div>`;
      }
    } else {
      // Open session arrow
      cards += `
        <div class="j-arrow">
          <div class="j-arrow-line">→</div>
        </div>
        <div class="j-card j-card-status">
          <div class="j-title" style="color:#2563EB">CURRENT STATUS</div>
          <div style="font-size:13px;font-weight:700;color:#2563EB;margin-bottom:4px;">Session Open</div>
          <div class="j-meta">Since ${s.check_in}</div>
          ${s.current_duration ? `<div class="j-meta" style="margin-top:4px;font-weight:600;color:#0F172A">${s.current_duration}</div>` : ''}
        </div>`;
    }
  });

  document.getElementById('journey-cards').innerHTML = cards;

  // ─ Session summary cards ─
  const cols = sessions.length + 1; // sessions + total
  let sessCards = `<div style="display:grid;grid-template-columns:repeat(${Math.min(cols,4)},1fr);gap:12px;">`;
  sessions.forEach(s => {
    sessCards += `
      <div class="sess-card">
        <div class="sess-head">Session ${s.index}</div>
        <div class="sess-time">${s.check_in??'—'} → ${s.check_out??'--:--:--'}</div>
        <div class="sess-meta">Working Duration</div>
        <div style="font-size:17px;font-weight:900;color:#0F172A;margin:2px 0 4px">${s.duration??'—'}</div>
        <div class="sess-meta">Punches: ${s.punch_count??'—'}</div>
      </div>`;
  });
  sessCards += `
      <div class="sess-total">
        <div class="sess-head">TOTAL</div>
        <div class="sess-meta">Total Working Hours</div>
        <div style="font-size:22px;font-weight:900;color:#2563EB;margin:2px 0 8px;letter-spacing:-1px">${sum?.working_hours??'—'}</div>
        <div class="sess-meta">Total Punches</div>
        <div style="font-size:18px;font-weight:900;color:#0F172A">${sum?.total_punches??0}</div>
      </div>`;
  sessCards += '</div>';
  document.getElementById('session-summary-cards').innerHTML = sessCards;

  // ─ Duplicate note ─
  if (dupCount > 0) {
    document.getElementById('dup-note').innerHTML = `
      <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px;padding:10px 14px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:8px;">
        <div style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#64748B;">
          <svg width="16" height="16" fill="none" stroke="#94A3B8" stroke-width="2" viewBox="0 0 24 24"><path d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
          ${dupCount} duplicate punch${dupCount>1?'es':''} hidden
        </div>
        <button onclick="toggleDupList(this)" style="font-size:12px;font-weight:600;color:#2563EB;background:none;border:none;cursor:pointer;font-family:inherit;">Show ▾</button>
      </div>
      <div id="dup-list" style="display:none"></div>`;
    document.getElementById('dup-note-info').style.display = 'block';
  } else {
    document.getElementById('dup-note').innerHTML = '';
    document.getElementById('dup-note-info').style.display = 'none';
  }

  document.getElementById('journey-section').style.display = 'block';
}

function toggleDupList(btn) {
  const el = document.getElementById('dup-list');
  const hidden = el.style.display === 'none';
  el.style.display = hidden ? 'block' : 'none';
  btn.textContent = hidden ? 'Hide ▴' : 'Show ▾';
}

// ══════════════════════════════════════════════
// STATS
// ══════════════════════════════════════════════
async function loadStats() {
  try {
    const d = await (await fetch(`/api/attendance/stats?date=${currentDate}`)).json();
    document.getElementById('k-emp').textContent  = d.total_employees;
    document.getElementById('k-pres').textContent = d.present;
    document.getElementById('k-abs').textContent  = d.absent;
    document.getElementById('k-late').textContent = d.late;
    document.getElementById('k-pun').textContent  = d.total_punches;
    document.getElementById('k-dev').textContent  = d.devices_online;
  } catch {}
}

// ══════════════════════════════════════════════
// SYNC STATUS
// ══════════════════════════════════════════════
async function loadSyncStatus() {
  try {
    const d = await (await fetch('/api/sync/status')).json();
    const devs = d.devices ?? [];
    const online = devs.filter(x => x.is_online);
    document.getElementById('sidebar-devices').innerHTML = devs.length
      ? devs.map(dv => `<div style="display:flex;align-items:center;justify-content:space-between;"><span style="font-size:12px;color:#0F172A;font-weight:600;overflow:hidden;text-overflow:ellipsis;max-width:110px">${dv.name||dv.serial_number}</span><span style="font-size:11px;font-weight:700;color:${dv.is_online?'#059669':'#94A3B8'}">${dv.is_online?'● Online':'○ Offline'}</span></div>`).join('')
      : `<span style="font-size:12px;color:#94A3B8">No devices</span>`;
    if (devs[0]) {
      document.getElementById('last-sync-rel').textContent   = devs[0].last_activity ?? '—';
      document.getElementById('next-sync-time').textContent  = new Date(Date.now()+60000).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
    }
    document.getElementById('device-online-label').innerHTML = online.length
      ? `<strong style="color:#059669">Online</strong>`
      : `<strong style="color:#94A3B8">Offline</strong>`;
    document.getElementById('pending-punches').textContent = d.total_unsynced ?? 0;
  } catch {}
}

// ══════════════════════════════════════════════
// FILTERS + HELPERS
// ══════════════════════════════════════════════
function applyFilters() {
  currentDate   = document.getElementById('date-filter').value;
  currentDept   = document.getElementById('dept-filter').value;
  currentDevice = document.getElementById('device-filter').value;
  currentSearch = document.getElementById('search-input').value;
  loadGrid(1); loadStats();
}

function resetFilters() {
  const t = '{{ today()->toDateString() }}';
  document.getElementById('date-filter').value = t;
  document.getElementById('dept-filter').value = '';
  document.getElementById('device-filter').value = '';
  document.getElementById('search-input').value = '';
  currentDate=t; currentDept=''; currentDevice=''; currentSearch='';
  loadGrid(1); loadStats();
}

document.getElementById('date-filter').addEventListener('change', () => {
  currentDate = document.getElementById('date-filter').value;
  document.getElementById('journey-section').style.display = 'none';
  loadGrid(1); loadStats();
  if (lastSelected) loadTimeline(lastSelected);
});

document.getElementById('search-input').addEventListener('input', debounce(() => {
  currentSearch = document.getElementById('search-input').value;
  loadGrid(1);
}, 380));

function debounce(fn, ms) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); }; }
function exportExcel() { alert('Export: /api/attendance/export?date=' + currentDate); }
function setNav(id) { document.querySelectorAll('.nav-btn').forEach(e => e.classList.remove('active')); document.getElementById(id)?.classList.add('active'); }

// ══════════════════════════════════════════════
// COUNTDOWN + INIT
// ══════════════════════════════════════════════
function startCountdown() {
  countdownSecs = 5;
  const iv = setInterval(() => {
    countdownSecs--;
    if (countdownSecs <= 0) { clearInterval(iv); refreshAll(); startCountdown(); }
  }, 1000);
}

function refreshAll() {
  loadGrid(currentPage); loadStats(); loadSyncStatus();
  if (lastSelected) loadTimeline(lastSelected);
}

loadGrid(1); loadStats(); loadSyncStatus(); startCountdown();
</script>
</body>
</html>
