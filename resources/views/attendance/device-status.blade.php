<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Device Status — BioSync</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',system-ui,sans-serif;background:#0F172A;color:#E2E8F0;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:40px 20px}
.page-header{text-align:center;margin-bottom:32px}
.logo{width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#2563EB,#7C3AED);display:flex;align-items:center;justify-content:center;margin:0 auto 14px}
.logo svg{width:28px;height:28px;stroke:#fff;fill:none}
h1{font-size:26px;font-weight:800;color:#F8FAFC;letter-spacing:-.5px}
.subtitle{font-size:14px;color:#64748B;margin-top:6px}
.server-time{font-size:13px;color:#94A3B8;margin-top:8px;font-weight:500}
.wrap{width:100%;max-width:620px}
.top-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.refresh-info{font-size:12px;color:#475569}
.refresh-btn{background:#1E293B;border:1px solid #334155;color:#94A3B8;padding:6px 14px;border-radius:8px;font-size:12px;cursor:pointer;font-family:inherit}
.refresh-btn:hover{background:#334155;color:#F1F5F9}
.summary{background:#1E293B;border:1px solid #334155;border-radius:12px;padding:16px 20px;display:flex;gap:28px;margin-bottom:16px;flex-wrap:wrap}
.sum-item{font-size:13px;color:#64748B}
.sum-item strong{color:#F1F5F9;font-weight:700}
.devices{display:flex;flex-direction:column;gap:14px}
.card{background:#1E293B;border-radius:16px;padding:24px;transition:border-color .2s}
.card.online{border:1px solid #22C55E}
.card.offline{border:1px solid #EF4444}
.card-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.dev-name{font-size:17px;font-weight:700;color:#F1F5F9}
.dev-sn{font-size:12px;color:#64748B;margin-top:3px;font-family:monospace}
.badge{display:flex;align-items:center;gap:7px;padding:7px 16px;border-radius:99px;font-size:13px;font-weight:700}
.badge.online{background:rgba(34,197,94,.15);color:#22C55E;border:1px solid rgba(34,197,94,.3)}
.badge.offline{background:rgba(239,68,68,.15);color:#EF4444;border:1px solid rgba(239,68,68,.3)}
.dot{width:8px;height:8px;border-radius:50%}
.dot.online{background:#22C55E;animation:ping 2s infinite}
.dot.offline{background:#EF4444}
@keyframes ping{0%{box-shadow:0 0 0 0 rgba(34,197,94,.5)}70%{box-shadow:0 0 0 8px rgba(34,197,94,0)}100%{box-shadow:0 0 0 0 rgba(34,197,94,0)}}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.stat{background:#0F172A;border-radius:10px;padding:14px;text-align:center}
.stat-val{font-size:22px;font-weight:800;color:#F1F5F9;line-height:1}
.stat-val.sm{font-size:13px;padding-top:4px}
.stat-lbl{font-size:10px;color:#64748B;margin-top:5px;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
.footer{margin-top:14px;padding-top:14px;border-top:1px solid #334155;display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px}
.footer span{font-size:12px;color:#64748B}
.footer strong{color:#94A3B8}
.empty{text-align:center;padding:60px 20px;color:#475569}
.empty svg{width:44px;height:44px;margin:0 auto 14px;opacity:.35;stroke:#475569;fill:none}
@keyframes fadeIn{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:none}}
.card{animation:fadeIn .25s ease both}
</style>
</head>
<body>

<div class="page-header">
    <div class="logo">
        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>
        </svg>
    </div>
    <h1>Device Status</h1>
    <p class="subtitle">Real-time biometric device connection monitor</p>
    <p class="server-time" id="serverTime">—</p>
</div>

<div class="wrap">
    <div class="top-bar">
        <span class="refresh-info" id="refreshInfo">Auto-refresh every 10s</span>
        <button class="refresh-btn" onclick="load()">↻ Refresh</button>
    </div>

    <div class="summary" id="summary" style="display:none">
        <div class="sum-item">Total: <strong id="sTotal">0</strong></div>
        <div class="sum-item">Online: <strong id="sOnline" style="color:#22C55E">0</strong></div>
        <div class="sum-item">Offline: <strong id="sOffline" style="color:#EF4444">0</strong></div>
        <div class="sum-item">Unsynced Logs: <strong id="sUnsynced">0</strong></div>
    </div>

    <div class="devices" id="devices">
        <div class="empty"><p>Loading...</p></div>
    </div>
</div>

<script>
let timer, cd = 10;

function load() {
    clearInterval(timer);
    cd = 10;
    document.getElementById('refreshInfo').textContent = 'Refreshing...';

    fetch('/api/sync/status')
        .then(r => r.json())
        .then(render)
        .catch(() => {
            document.getElementById('devices').innerHTML =
                '<div class="empty"><p style="color:#EF4444">Failed to connect to server.</p></div>';
            tick();
        });
}

function render(data) {
    document.getElementById('serverTime').textContent = 'Server Time: ' + (data.server_time ?? '—');

    const devs = data.devices ?? [];
    const online  = devs.filter(d => d.is_online).length;
    const offline = devs.length - online;

    const sum = document.getElementById('summary');
    sum.style.display = 'flex';
    document.getElementById('sTotal').textContent    = devs.length;
    document.getElementById('sOnline').textContent   = online;
    document.getElementById('sOffline').textContent  = offline;
    document.getElementById('sUnsynced').textContent = data.total_unsynced ?? 0;

    const box = document.getElementById('devices');

    if (!devs.length) {
        box.innerHTML = `<div class="empty">
            <svg viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/>
            </svg>
            <p>No devices registered yet.<br>Connect your biometric device to get started.</p>
        </div>`;
        tick(); return;
    }

    box.innerHTML = devs.map(d => {
        const cls = d.is_online ? 'online' : 'offline';
        const lastLog = d.last_attlog
            ? new Date(d.last_attlog).toLocaleString('en-IN',{day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit',hour12:true})
            : 'Never';

        return `<div class="card ${cls}">
            <div class="card-top">
                <div>
                    <div class="dev-name">${d.name}</div>
                    <div class="dev-sn">SN: ${d.serial_number}</div>
                </div>
                <div class="badge ${cls}">
                    <span class="dot ${cls}"></span>
                    ${d.is_online ? 'Online' : 'Offline'}
                </div>
            </div>
            <div class="stats">
                <div class="stat">
                    <div class="stat-val">${d.records_today}</div>
                    <div class="stat-lbl">Punches Today</div>
                </div>
                <div class="stat">
                    <div class="stat-val sm">${d.last_activity}</div>
                    <div class="stat-lbl">Last Seen</div>
                </div>
                <div class="stat">
                    <div class="stat-val sm">${d.firmware ?? '—'}</div>
                    <div class="stat-lbl">Firmware</div>
                </div>
            </div>
            <div class="footer">
                <span>Last Punch Log: <strong>${lastLog}</strong></span>
                <span>Mode: <strong>ADMS</strong></span>
            </div>
        </div>`;
    }).join('');

    tick();
}

function tick() {
    cd = 10;
    clearInterval(timer);
    timer = setInterval(() => {
        document.getElementById('refreshInfo').textContent = `Auto-refresh in ${--cd}s`;
        if (cd <= 0) load();
    }, 1000);
}

load();
</script>
</body>
</html>
