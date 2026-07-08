<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Check-In — {{ $employee->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1d2e 0%, #2d3561 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .card {
            background: #fff;
            border-radius: 24px;
            padding: 2rem;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .3);
        }

        .emp-icon {
            font-size: 3rem;
            text-align: center;
        }

        .emp-name {
            font-size: 1.4rem;
            font-weight: 700;
            text-align: center;
        }

        .emp-sub {
            color: #999;
            font-size: .9rem;
            text-align: center;
        }

        .location-item {
            padding: 1rem;
            border: 2px solid #eee;
            border-radius: 12px;
            margin-bottom: .8rem;
            cursor: pointer;
            transition: all .2s;
            background: #f9f9f9;
        }

        .location-item:hover {
            border-color: #0d6efd;
            background: #f0f7ff;
        }

        .location-item.selected {
            border-color: #0d6efd;
            background: #d1e7ff;
        }

        .loc-name {
            font-weight: 600;
        }

        .loc-addr {
            font-size: .85rem;
            color: #666;
        }

        .loc-rad {
            font-size: .8rem;
            color: #999;
        }

        .msg {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .msg-loading {
            background: #e3f2fd;
            color: #1976d2;
        }

        .msg-success {
            background: #e8f5e9;
            color: #388e3c;
        }

        .msg-error {
            background: #ffebee;
            color: #d32f2f;
        }

        .msg-warning {
            background: #fff8e1;
            color: #f57f17;
        }

        .btn-primary {
            border-radius: 12px;
            padding: .8rem 2rem;
            font-size: 1.05rem;
            font-weight: 600;
        }

        .spin {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(0, 0, 0, .1);
            border-radius: 50%;
            border-top-color: currentColor;
            animation: spin .6s linear infinite;
            margin-right: .5rem;
            vertical-align: middle;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .step-info {
            background: #f5f5f5;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: .9rem;
            color: #666;
        }

        .step-num {
            display: inline-block;
            background: #0d6efd;
            color: #fff;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
            margin-right: .5rem;
            font-size: .8rem;
            font-weight: bold;
        }

        #pendingSection {
            display: none;
            text-align: center;
        }

        .pending-icon {
            font-size: 3rem;
            margin-bottom: .5rem;
        }
    </style>
</head>

<body>
    <div class="card">

        {{-- Employee header --}}
        <div class="mb-3">
            <div class="emp-icon">👤</div>
            <div class="emp-name">{{ strtoupper($employee->name) }}</div>
            <div class="emp-sub">{{ $employee->employee_id }}</div>
            @if($employee->department)
            <div class="emp-sub">{{ $employee->department }}</div>
            @endif
        </div>

        <div id="msgBox"></div>

        {{-- ── Main check-in section ── --}}
        <div id="checkInSection" style="display:none">
            <div class="step-info">
                <div><span class="step-num">1</span> Select your current location</div>
                <div><span class="step-num">2</span> Grant location permission (for GPS verification)</div>
                <div><span class="step-num">3</span> Confirm your check-in or check-out</div>
            </div>

            <h6 class="fw-bold mb-2">📍 Office Locations</h6>
            <div id="locationList">
                @forelse($locations as $location)
                <div class="location-item" data-id="{{ $location->id }}">
                    <div class="loc-name">📌 {{ $location->name }}</div>
                    @if($location->address)
                    <div class="loc-addr">{{ $location->address }}</div>
                    @endif
                    <div class="loc-rad">Radius: {{ $location->radius_meters }}m</div>
                </div>
                @empty
                <div class="alert alert-warning">No active locations.</div>
                @endforelse
            </div>

            <div class="d-grid mt-3">
                <button class="btn btn-primary" id="checkInBtn" disabled>
                    ✓ Confirm Check-In/Out
                </button>
            </div>
            <div class="text-center mt-2">
                <small class="text-muted">Your location will be verified with GPS</small>
            </div>
        </div>

        {{-- ── Pending section ── --}}
        <div id="pendingSection">
            <div class="pending-icon">⏳</div>
            <h5 class="fw-bold">Device Change Pending</h5>
            <p class="text-muted small" id="pendingMsg">
                Your device change request has been submitted and is waiting for admin approval.
            </p>
            <div class="alert alert-info small mt-3">
                <strong>What happens next?</strong><br>
                Admin will review your request. Once approved, you can use this device to check in.
            </div>
        </div>

    </div>

    <script>
        const QR_TOKEN = '{{ $token }}';
        const CSRF = document.querySelector('meta[name=csrf-token]').content;
        const EMP_ID_KEY = 'device_token_{{ $employee->employee_id }}';
        const PEND_KEY = 'device_pending_{{ $employee->employee_id }}';

        let selectedLocationId = null;
        let deviceToken = localStorage.getItem(EMP_ID_KEY) || '';
        let pollInterval = null;

        // Normalize fingerprint — remove version numbers that change between browsers
        const FINGERPRINT = (() => {
            const ua = navigator.userAgent;

            // Extract only stable device info — OS name + major version + screen + lang + timezone
            const os = ua.match(/\(([^)]+)\)/)?.[1]
                ?.replace(/\d+_\d+_?\d*/g, v => v.split('_')[0]) // "17_5_1" → "17"
                ?.replace(/like Mac OS X/g, '')
                ?.trim() ?? 'unknown';

            return [
                os,
                screen.width + 'x' + screen.height,
                navigator.language,
                Intl.DateTimeFormat().resolvedOptions().timeZone,
            ].join('|');
        })();

        // ── On load: auto-detect device state ────────────────────────
        window.addEventListener('DOMContentLoaded', async () => {
            showMsg('<span class="spin"></span>Checking device...', 'loading');
            await checkDevice();
        });

        async function checkDevice() {
            try {
                const resp = await fetch('{{ route("devices.register") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':        'application/json',
                        'X-CSRF-TOKEN':  CSRF
                    },
                    body: JSON.stringify({
                        qr_token:    QR_TOKEN,
                        name:        navigator.userAgent.substring(0, 80),
                        fingerprint: FINGERPRINT,
                    }),
                });

                const data = await resp.json();

                if (data.success && data.token) {
                    // ✅ Approved (first-time auto-registered or same device)
                    stopPolling();
                    localStorage.setItem(EMP_ID_KEY, data.token);
                    localStorage.removeItem(PEND_KEY);
                    deviceToken = data.token;
                    clearMsg();
                    showCheckIn();

                } else if (data.status === 'pending') {
                    // ⏳ Different device — waiting for admin approval
                    localStorage.setItem(PEND_KEY, '1');
                    localStorage.removeItem(EMP_ID_KEY);
                    deviceToken = '';
                    showPending(data.message);
                    startPolling();

                } else {
                    // Unexpected response — fall back to check-in if we have a cached token
                    stopPolling();
                    clearMsg();
                    if (deviceToken) showCheckIn();
                    else showMsg('Could not verify device. Please try again.', 'warning');
                }
            } catch (e) {
                stopPolling();
                clearMsg();
                if (deviceToken) showCheckIn();
                else showMsg('Connection error. Please try again.', 'warning');
            }
        }

        // ── Polling ───────────────────────────────────────────────────
        function startPolling() {
            if (pollInterval) return; // already running
            // Update pending message to show it's checking
            showPending('Waiting for admin approval... checking automatically every 10 seconds.');
            pollInterval = setInterval(async () => {
                await checkDevice();
            }, 10000); // check every 10 seconds
        }

        function stopPolling() {
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
        }

        // ── UI helpers ────────────────────────────────────────────────
        function showCheckIn() {
            stopPolling();
            document.getElementById('checkInSection').style.display = 'block';
            document.getElementById('pendingSection').style.display = 'none';
        }

        function showPending(msg) {
            document.getElementById('checkInSection').style.display = 'none';
            document.getElementById('pendingSection').style.display = 'block';
            if (msg) document.getElementById('pendingMsg').textContent = msg;
        }

        function showMsg(html, type) {
            document.getElementById('msgBox').innerHTML =
                `<div class="msg msg-${type}">${html}</div>`;
        }

        function clearMsg() {
            document.getElementById('msgBox').innerHTML = '';
        }

        // ── Location selection ────────────────────────────────────────
        document.querySelectorAll('.location-item').forEach(item => {
            item.addEventListener('click', () => {
                document.querySelectorAll('.location-item')
                    .forEach(i => i.classList.remove('selected'));
                item.classList.add('selected');
                selectedLocationId = parseInt(item.dataset.id);
                document.getElementById('checkInBtn').disabled = false;
            });
        });

        // ── Check-in button ───────────────────────────────────────────
        document.getElementById('checkInBtn').addEventListener('click', async () => {
            if (!selectedLocationId) {
                showMsg('Please select a location.', 'error');
                return;
            }
            if (!deviceToken) {
                showMsg('<span class="spin"></span>Verifying device...', 'loading');
                await checkDevice();
                return;
            }

            showMsg('<span class="spin"></span>Locking GPS signal...', 'loading');

            let watchId  = null;
            let bestPos  = null;
            const ACCURACY_GOAL = 50;   // accept once ≤ 50 m
            const MAX_WAIT_MS   = 30000;

            try {
                const pos = await new Promise((resolve, reject) => {
                    const timer = setTimeout(() => {
                        if (watchId !== null) navigator.geolocation.clearWatch(watchId);
                        if (bestPos) resolve(bestPos);
                        else reject({ code: 3, message: 'GPS timeout' });
                    }, MAX_WAIT_MS);

                    watchId = navigator.geolocation.watchPosition(
                        (p) => {
                            const acc = Math.round(p.coords.accuracy);
                            showMsg(`<span class="spin"></span>GPS accuracy: ±${acc}m&hellip;`, 'loading');
                            if (!bestPos || acc < bestPos.coords.accuracy) bestPos = p;
                            if (acc <= ACCURACY_GOAL) {
                                clearTimeout(timer);
                                navigator.geolocation.clearWatch(watchId);
                                resolve(p);
                            }
                        },
                        (err) => {
                            clearTimeout(timer);
                            if (watchId !== null) navigator.geolocation.clearWatch(watchId);
                            reject(err);
                        },
                        { enableHighAccuracy: true, maximumAge: 0, timeout: 30000 }
                    );
                });

                const { latitude, longitude } = pos.coords;

                showMsg('<span class="spin"></span>Submitting...', 'loading');

                const resp = await fetch('{{ route("attendance.employee-process") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':        'application/json',
                        'X-CSRF-TOKEN':  CSRF
                    },
                    body: JSON.stringify({
                        employee_token: QR_TOKEN,
                        location_id:    selectedLocationId,
                        device_token:   deviceToken,
                        fingerprint:    FINGERPRINT,
                        latitude,
                        longitude,
                    }),
                });

                const data = await resp.json();

                if (data.success) {
                    showMsg(
                        `<strong>${data.message}</strong><br>` +
                        `${data.name} @ ${data.location}<br>` +
                        `🕐 ${data.time} · 📏 ${data.distance}m`,
                        'success'
                    );
                    document.getElementById('checkInBtn').disabled = true;
                    document.getElementById('checkInBtn').textContent = '✓ Done';
                } else {
                    if (resp.status === 403 && (data.status === 'pending' || data.status === 'wrong_device' || data.status === 'no_device')) {
                        // Device issue — re-verify silently (auto-registers first time or shows pending)
                        localStorage.removeItem(EMP_ID_KEY);
                        deviceToken = '';
                        if (data.status === 'pending') {
                            localStorage.setItem(PEND_KEY, '1');
                            showPending(data.message);
                            startPolling();
                        } else {
                            showMsg('<span class="spin"></span>Re-verifying device...', 'loading');
                            await checkDevice();
                        }
                    } else {
                        // Location rejection or other error — stay on check-in, just show message
                        showMsg(`⚠️ ${data.message}`, 'error');
                    }
                }
            } catch (err) {
                if (err.code === 1) showMsg('Location permission denied. Please enable GPS.', 'error');
                else if (err.code === 3) showMsg('GPS timeout. Try again outdoors.', 'error');
                else showMsg('Error: ' + (err.message || 'Unknown error'), 'error');
            }
        });

    </script>
</body>

</html>