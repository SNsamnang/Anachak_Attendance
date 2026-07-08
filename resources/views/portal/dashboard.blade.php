<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Attendance — {{ $employee->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="{{ asset('icon.ico') }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
        }

        /* Topbar */
        .topbar {
            background: #1e293b;
            border-bottom: 1px solid #334155;
            padding: 0 20px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .brand-icon {
            width: 34px; height: 34px;
            background: #10b981;
            border-radius: 9px;
            display: grid;
            place-items: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .topbar-name {
            font-weight: 700;
            font-size: 14px;
            color: #f1f5f9;
            line-height: 1.2;
        }
        .topbar-sub {
            font-size: 11.5px;
            color: #64748b;
            line-height: 1.2;
        }
        .btn-logout {
            background: transparent;
            border: 1px solid #334155;
            color: #94a3b8;
            border-radius: 8px;
            padding: 6px 12px;
            font-size: 12.5px;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-logout:hover { border-color: #ef4444; color: #ef4444; }

        /* Main content */
        .content {
            max-width: 720px;
            margin: 0 auto;
            padding: 24px 16px 48px;
        }

        /* Cards */
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .card-header {
            background: #1e293b;
            border-bottom: 1px solid #334155;
            padding: 13px 18px;
            font-size: 13px;
            font-weight: 600;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .card-body { padding: 16px 18px; }

        /* Employee profile strip */
        .emp-avatar {
            width: 44px; height: 44px;
            background: #10b981;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }
        .emp-info-name { font-weight: 700; font-size: 15px; color: #f1f5f9; }
        .emp-info-meta { font-size: 12px; color: #64748b; }

        /* Stat cards */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }
        @media (max-width: 540px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } }
        .stat-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 14px 12px;
            text-align: center;
        }
        .stat-num { font-size: 1.6rem; font-weight: 700; line-height: 1; }
        .stat-label { font-size: 10.5px; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-top: 4px; }

        /* Check-in card */
        .checkin-card {
            background: linear-gradient(135deg, #0d2137 0%, #0f2744 100%);
            border: 1px solid #1e3a5f;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 16px;
        }
        .checkin-title {
            font-size: 13px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .location-item {
            padding: 12px 14px;
            border: 1.5px solid #1e3a5f;
            border-radius: 10px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all .15s;
            background: rgba(255,255,255,.03);
            color: #cbd5e1;
        }
        .location-item:hover { border-color: #3b82f6; background: rgba(59,130,246,.06); }
        .location-item.selected { border-color: #10b981; background: rgba(16,185,129,.08); }
        .location-name { font-weight: 600; font-size: 13.5px; color: #f1f5f9; }
        .location-meta { font-size: 11.5px; color: #64748b; margin-top: 2px; }

        .btn-checkin {
            background: #10b981;
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 11px;
            font-size: 14px;
            font-weight: 600;
            width: 100%;
            margin-top: 4px;
            cursor: pointer;
            transition: background .15s;
        }
        .btn-checkin:hover:not(:disabled) { background: #059669; }
        .btn-checkin:disabled { opacity: .4; cursor: not-allowed; }

        .ci-msg { border-radius: 10px; padding: 10px 14px; font-size: 13px; margin-bottom: 10px; text-align: center; }
        .ci-msg.loading { background: rgba(255,255,255,.06); color: #94a3b8; }
        .ci-msg.success { background: rgba(16,185,129,.12); color: #6ee7b7; }
        .ci-msg.error   { background: rgba(239,68,68,.12);  color: #fca5a5; }

        .spin {
            display: inline-block;
            width: 13px; height: 13px;
            border: 2px solid rgba(255,255,255,.2);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin .6s linear infinite;
            vertical-align: middle;
            margin-right: 5px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Pending section */
        .pending-box {
            text-align: center;
            padding: 20px 0 8px;
            color: #94a3b8;
        }
        .pending-box .big-icon { font-size: 2.2rem; margin-bottom: 8px; }
        .pending-box h6 { font-weight: 700; color: #f1f5f9; margin-bottom: 6px; }
        .pending-box p { font-size: 12.5px; margin-bottom: 12px; line-height: 1.6; }
        .pending-info {
            background: rgba(255,255,255,.05);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 12px;
            color: #64748b;
        }
        .poll-status { margin-top: 12px; font-size: 12px; color: #475569; }

        /* Record rows */
        .day-group {
            background: #0f172a;
            padding: 7px 18px;
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .5px;
            border-bottom: 1px solid #334155;
        }
        .record-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 18px;
            border-bottom: 1px solid #1a2844;
            gap: 8px;
            flex-wrap: wrap;
        }
        .record-row:last-child { border-bottom: none; }
        .rec-time { font-weight: 600; font-size: 14px; color: #f1f5f9; }
        .rec-loc  { font-size: 11.5px; color: #64748b; }

        /* Badges */
        .bdg {
            display: inline-block;
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 11.5px;
            font-weight: 600;
        }
        .bdg-in      { background: rgba(16,185,129,.15); color: #6ee7b7; }
        .bdg-out     { background: rgba(239,68,68,.12);  color: #fca5a5; }
        .bdg-ontime  { background: rgba(16,185,129,.12); color: #6ee7b7; }
        .bdg-late    { background: rgba(239,68,68,.12);  color: #fca5a5; }
        .bdg-early   { background: rgba(245,158,11,.12); color: #fcd34d; }
        .bdg-ot      { background: rgba(139,92,246,.12); color: #c4b5fd; }
        .bdg-oor     { background: rgba(239,68,68,.12);  color: #fca5a5; }

        /* Date filter form */
        .filter-form { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .filter-label { font-size: 11.5px; color: #64748b; }
        .form-control-dark {
            background: #0f172a;
            border: 1px solid #334155;
            color: #f1f5f9;
            border-radius: 8px;
            padding: 5px 10px;
            font-size: 12.5px;
            font-family: 'Inter', system-ui, sans-serif;
            width: 140px;
        }
        .form-control-dark:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59,130,246,.15);
        }
        .btn-filter {
            background: #3b82f6;
            border: none;
            color: #fff;
            border-radius: 8px;
            padding: 5px 14px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Inter', system-ui, sans-serif;
            transition: background .15s;
        }
        .btn-filter:hover { background: #2563eb; }

        /* OT summary strip */
        .ot-strip {
            background: #0f172a;
            border-bottom: 1px solid #334155;
            padding: 9px 18px;
            font-size: 12.5px;
            color: #64748b;
        }
        .ot-strip strong { color: #a78bfa; }

        /* Empty state */
        .empty { padding: 32px; text-align: center; color: #475569; font-size: 13px; }

        /* Reload btn (inside success msg) */
        .btn-reload {
            margin-top: 8px;
            padding: 4px 14px;
            border: 1px solid rgba(16,185,129,.4);
            border-radius: 7px;
            background: transparent;
            color: #6ee7b7;
            cursor: pointer;
            font-size: 12px;
            font-family: 'Inter', system-ui, sans-serif;
        }
        .btn-reload:hover { background: rgba(16,185,129,.1); }

        .gps-hint { text-align: center; font-size: 11px; color: #334155; margin-top: 6px; }
    </style>
</head>
<body>

{{-- Topbar --}}
<div class="topbar">
    <div class="topbar-left">
        <div class="brand-icon">📋</div>
        <div>
            <div class="topbar-name">{{ $employee->name }}</div>
            <div class="topbar-sub">{{ $employee->employee_id }}@if($employee->department) · {{ $employee->department }}@endif</div>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
        <a href="{{ route('portal.leave-request') }}"
           style="background:#1e3a5f;border:1px solid #334155;color:#60a5fa;font-size:12px;font-weight:600;
                  padding:6px 12px;border-radius:8px;text-decoration:none;display:flex;align-items:center;gap:5px;
                  transition:background .15s"
           onmouseover="this.style.background='#1e40af'"
           onmouseout="this.style.background='#1e3a5f'">
            <i class="bi bi-calendar-plus"></i> Request
        </a>
        <form method="POST" action="{{ route('portal.logout') }}">
            @csrf
            <button type="submit" class="btn-logout">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
    </div>
</div>

<div class="content">

    {{-- Employee profile --}}
    <div class="card">
        <div class="card-body d-flex align-items-center gap-3 py-3">
            <div class="emp-avatar">{{ strtoupper(substr($employee->name, 0, 1)) }}</div>
            <div>
                <div class="emp-info-name">{{ $employee->name }}</div>
                <div class="emp-info-meta">
                    <i class="bi bi-clock me-1" style="color:#10b981"></i>
                    {{ $employee->workStartFormatted() }} – {{ $employee->workEndFormatted() }}
                    &nbsp;·&nbsp;
                    <i class="bi bi-cash me-1" style="color:#10b981"></i>
                    ${{ number_format($employee->salary ?? 0, 2) }}/month
                </div>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-num" style="color:#3b82f6">{{ $totalDays }}</div>
            <div class="stat-label">Days Present</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:#10b981">{{ $onTimeDays }}</div>
            <div class="stat-label">On Time</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:#ef4444">{{ $lateDays }}</div>
            <div class="stat-label">Late Days</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:#a78bfa;font-size:1.15rem;padding-top:4px">
                {{ \App\Http\Controllers\EmployeePortalController::formatOt($totalOtSeconds) }}
            </div>
            <div class="stat-label">Total OT</div>
        </div>
    </div>

    {{-- Check-in / Check-out --}}
    <div class="checkin-card">
        <div class="checkin-title">
            <i class="bi bi-geo-alt-fill" style="color:#10b981"></i> Check-In / Check-Out
        </div>

        {{-- Pending --}}
        <div id="pendingSection" style="display:none">
            <div class="pending-box">
                <div class="big-icon">⏳</div>
                <h6>Waiting for Approval</h6>
                <p>Your device change request is pending admin approval.<br>
                   <strong style="color:#f1f5f9">Check-in will unlock automatically once approved.</strong></p>
                <div class="pending-info">
                    Admin will approve your device — <strong style="color:#94a3b8">no need to reload</strong>.
                </div>
                <div class="poll-status">
                    <span class="spin"></span> Checking every 10 seconds…
                </div>
            </div>
        </div>

        {{-- Check-in section --}}
        <div id="checkinSection">
            <div id="ciMsg"></div>

            @forelse($locations as $loc)
            <div class="location-item" data-id="{{ $loc->id }}">
                <div class="location-name">
                    <i class="bi bi-pin-map me-1" style="color:#10b981"></i>{{ $loc->name }}
                </div>
                @if($loc->address)
                <div class="location-meta">{{ $loc->address }}</div>
                @endif
                <div class="location-meta">Radius: {{ $loc->radius_meters }}m</div>
            </div>
            @empty
            <p class="empty">No active locations.</p>
            @endforelse

            <button class="btn-checkin" id="ciBtn" disabled>
                <i class="bi bi-check-circle me-1"></i> Confirm Check-In / Out
            </button>
            <div class="gps-hint"><i class="bi bi-geo-alt me-1"></i>GPS location will be verified</div>
        </div>
    </div>

    {{-- Today's records --}}
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-calendar-day me-2" style="color:#10b981"></i>Today — {{ now()->format('D, d M Y') }}</span>
        </div>
        @forelse($todayRecords as $rec)
        <div class="record-row">
            <div class="d-flex align-items-center gap-2">
                <span class="bdg {{ $rec->type === 'in' ? 'bdg-in' : 'bdg-out' }}">
                    {{ $rec->type === 'in' ? 'Check In' : 'Check Out' }}
                </span>
                <div>
                    <div class="rec-time">{{ $rec->scanned_at->format('H:i:s') }}</div>
                    <div class="rec-loc"><i class="bi bi-geo-alt me-1"></i>{{ $rec->location->name }}</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1 flex-wrap justify-content-end">
                @if($rec->type === 'in' && $rec->check_in_status)
                    <span class="bdg {{ $rec->check_in_status === 'on_time' ? 'bdg-ontime' : 'bdg-late' }}">
                        {{ $rec->check_in_status === 'on_time' ? 'On Time' : 'Late' }}
                    </span>
                @endif
                @if($rec->type === 'out' && $rec->check_out_status)
                    <span class="bdg {{ $rec->check_out_status === 'on_time' ? 'bdg-ontime' : 'bdg-early' }}">
                        {{ $rec->check_out_status === 'on_time' ? 'On Time' : 'Early' }}
                    </span>
                @endif
                @if($rec->ot_seconds > 0)
                    <span class="bdg bdg-ot">OT {{ \App\Http\Controllers\EmployeePortalController::formatOt($rec->ot_seconds) }}</span>
                @endif
                @if(!$rec->location_verified)
                    <span class="bdg bdg-oor">Out of range</span>
                @endif
            </div>
        </div>
        @empty
        <div class="empty">No records today.</div>
        @endforelse
    </div>

    {{-- History --}}
    <div class="card">
        <div class="card-header">
            <span><i class="bi bi-clock-history me-2" style="color:#10b981"></i>Attendance History</span>
            <form method="GET" class="filter-form">
                <span class="filter-label">From</span>
                <input type="date" name="date_from" class="form-control-dark" value="{{ $dateFrom }}">
                <span class="filter-label">To</span>
                <input type="date" name="date_to" class="form-control-dark" value="{{ $dateTo }}">
                <button type="submit" class="btn-filter">Go</button>
            </form>
        </div>

        @if(count($history))
        <div class="ot-strip">
            OT ({{ \Carbon\Carbon::parse($dateFrom)->format('d M') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}):
            <strong>{{ \App\Http\Controllers\EmployeePortalController::formatOt($monthlyOt) }}</strong>
        </div>
        @endif

        @forelse($history as $date => $records)
        <div class="day-group">
            {{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}
            <span style="float:right;font-weight:400;color:#475569">{{ $records->count() }} scan(s)</span>
        </div>
        @foreach($records as $rec)
        <div class="record-row">
            <div class="d-flex align-items-center gap-2">
                <span class="bdg {{ $rec->type === 'in' ? 'bdg-in' : 'bdg-out' }}">
                    {{ $rec->type === 'in' ? 'In' : 'Out' }}
                </span>
                <div>
                    <div class="rec-time">{{ $rec->scanned_at->format('H:i') }}</div>
                    <div class="rec-loc"><i class="bi bi-geo-alt me-1"></i>{{ $rec->location->name }}</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1 flex-wrap justify-content-end">
                @if($rec->type === 'in' && $rec->check_in_status)
                    <span class="bdg {{ $rec->check_in_status === 'on_time' ? 'bdg-ontime' : 'bdg-late' }}">
                        {{ $rec->check_in_status === 'on_time' ? 'On Time' : 'Late' }}
                    </span>
                @endif
                @if($rec->type === 'out' && $rec->check_out_status)
                    <span class="bdg {{ $rec->check_out_status === 'on_time' ? 'bdg-ontime' : 'bdg-early' }}">
                        {{ $rec->check_out_status === 'on_time' ? 'On Time' : 'Early' }}
                    </span>
                @endif
                @if($rec->ot_seconds > 0)
                    <span class="bdg bdg-ot">OT {{ \App\Http\Controllers\EmployeePortalController::formatOt($rec->ot_seconds) }}</span>
                @endif
            </div>
        </div>
        @endforeach
        @empty
        <div class="empty">
            No records from {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}
            to {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}.
        </div>
        @endforelse
    </div>

</div>

<script>
    const CSRF = document.querySelector('meta[name=csrf-token]').content;

    const FINGERPRINT = (() => {
        const ua = navigator.userAgent;
        const os = ua.match(/\(([^)]+)\)/)?.[1]
            ?.replace(/\d+_\d+_?\d*/g, v => v.split('_')[0])
            ?.replace(/like Mac OS X/g, '')
            ?.trim() ?? 'unknown';
        return [os, screen.width + 'x' + screen.height, navigator.language,
                Intl.DateTimeFormat().resolvedOptions().timeZone].join('|');
    })();

    let selectedLocationId = null;
    let pollInterval = null;

    window.addEventListener('DOMContentLoaded', () => checkDeviceStatus(false));

    async function checkDeviceStatus(autoCheckIn = false) {
        try {
            const resp = await fetch('{{ route("portal.device-status") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ fingerprint: FINGERPRINT }),
            });
            const data = await resp.json();
            if (data.status === 'approved') {
                stopPolling();
                if (autoCheckIn && selectedLocationId) { showCheckin(); await doCheckIn(); }
                else showCheckin();
            } else if (data.status === 'pending') {
                showPending(); startPolling();
            } else {
                showCheckin();
            }
        } catch (e) { showCheckin(); }
    }

    function startPolling() {
        if (pollInterval) return;
        pollInterval = setInterval(() => checkDeviceStatus(true), 10000);
    }
    function stopPolling() {
        if (pollInterval) { clearInterval(pollInterval); pollInterval = null; }
    }
    function showCheckin() {
        document.getElementById('checkinSection').style.display = 'block';
        document.getElementById('pendingSection').style.display = 'none';
    }
    function showPending() {
        document.getElementById('checkinSection').style.display = 'none';
        document.getElementById('pendingSection').style.display = 'block';
    }

    document.querySelectorAll('.location-item').forEach(item => {
        item.addEventListener('click', () => {
            document.querySelectorAll('.location-item').forEach(i => i.classList.remove('selected'));
            item.classList.add('selected');
            selectedLocationId = parseInt(item.dataset.id);
            document.getElementById('ciBtn').disabled = false;
        });
    });

    document.getElementById('ciBtn').addEventListener('click', async () => {
        if (!selectedLocationId) { showCiMsg('Please select a location.', 'error'); return; }
        await doCheckIn();
    });

    async function doCheckIn() {
        showCiMsg('<span class="spin"></span>Getting GPS location…', 'loading');
        document.getElementById('ciBtn').disabled = true;
        try {
            const pos = await new Promise((res, rej) =>
                navigator.geolocation.getCurrentPosition(res, rej,
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 })
            );
            const { latitude, longitude } = pos.coords;
            showCiMsg('<span class="spin"></span>Submitting…', 'loading');

            const resp = await fetch('{{ route("portal.checkin") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ location_id: selectedLocationId, fingerprint: FINGERPRINT, latitude, longitude }),
            });
            const data = await resp.json();

            if (data.success) {
                showCiMsg(
                    `<strong>${data.message}</strong><br>` +
                    `<span style="font-size:12px;opacity:.8">${data.location} · ${data.time} · ${data.distance}m</span><br>` +
                    `<button class="btn-reload" onclick="location.reload()"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>`,
                    'success'
                );
                document.getElementById('ciBtn').disabled = true;
                document.getElementById('ciBtn').innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Done';
            } else {
                document.getElementById('ciBtn').disabled = false;
                if (data.status === 'wrong_device') {
                    showPending(); startPolling();
                } else {
                    showCiMsg(
                        `${data.message}<br><span style="font-size:11.5px;opacity:.7">Distance: ${data.distance ?? '?'}m / Max: ${data.radius ?? '?'}m</span>`,
                        'error'
                    );
                }
            }
        } catch (err) {
            document.getElementById('ciBtn').disabled = false;
            if (err.code === 1) showCiMsg('Location permission denied.', 'error');
            else if (err.code === 3) showCiMsg('GPS timeout. Try again.', 'error');
            else showCiMsg('Error: ' + (err.message || 'Unknown'), 'error');
        }
    }

    function showCiMsg(html, type) {
        const box = document.getElementById('ciMsg');
        box.innerHTML = html ? `<div class="ci-msg ${type}">${html}</div>` : '';
    }
</script>
</body>
</html>
