<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Check-In — {{ $location->name }}</title>
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

        .scan-card {
            background: #fff;
            border-radius: 24px;
            padding: 2rem;
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .3);
        }

        .status-icon { font-size: 5rem; margin-bottom: 1rem; }

        .btn-scan {
            border-radius: 12px;
            padding: .8rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
        }

        #qrInput {
            border-radius: 12px;
            text-align: center;
            font-size: 1rem;
            letter-spacing: 2px;
        }

        .distance-bar {
            height: 8px;
            border-radius: 999px;
            background: #eee;
            overflow: hidden;
            margin: .5rem 0;
        }

        .distance-fill {
            height: 100%;
            border-radius: 999px;
            transition: width .5s;
        }

        .step { padding: 1rem; border-radius: 12px; margin-bottom: .5rem; text-align: left; }
        #step1 { background: #f8f9fa; }
        #step2 { background: #e8f4fd; display: none; }
        #step3 { display: none; }

        .accuracy-badge {
            display: inline-block;
            font-size: .75rem;
            padding: 2px 8px;
            border-radius: 999px;
            font-weight: 600;
            transition: background .3s;
        }
        .acc-good    { background: #d1fae5; color: #065f46; }
        .acc-medium  { background: #fef3c7; color: #92400e; }
        .acc-poor    { background: #fee2e2; color: #991b1b; }
    </style>
</head>

<body>
    <div class="scan-card">
        <div style="font-size:3rem;margin-bottom:.5rem">📍</div>
        <h5 class="fw-bold mb-1">{{ $location->name }}</h5>
        <p class="text-muted small mb-3">{{ $location->address ?? '' }}</p>
        <p class="text-muted small mb-4">Allowed radius: <strong>{{ $location->radius_meters }}m</strong></p>

        {{-- Step 1: Enter employee ID --}}
        <div id="step1" class="step">
            <p class="fw-semibold mb-2" style="font-size:.9rem">
                <i class="bi bi-person-badge"></i> Enter your Employee ID
            </p>
            <input type="text" id="qrInput" class="form-control mb-3"
                placeholder="e.g. EMP001" autocomplete="off"
                inputmode="text" style="letter-spacing:1px">
            <button class="btn btn-primary btn-scan" onclick="startGps()">
                <i class="bi bi-geo-alt-fill"></i> Get My Location & Check In
            </button>
        </div>

        {{-- Step 2: Getting GPS --}}
        <div id="step2" class="step">
            <div class="text-center py-2">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <p class="fw-semibold mb-1">Locking GPS signal…</p>
                <p class="text-muted small mb-2">Please allow location access when prompted.</p>
                <div id="accuracyInfo" style="display:none;">
                    <span class="text-muted small">GPS accuracy: </span>
                    <span class="accuracy-badge" id="accuracyBadge">—</span>
                </div>
                <p class="text-muted mt-2" style="font-size:.75rem">
                    Waiting for a precise fix. This may take up to 30 seconds indoors.
                </p>
            </div>
        </div>

        {{-- Step 3: Result --}}
        <div id="step3">
            <div class="status-icon" id="resultIcon"></div>
            <h4 id="resultTitle" class="fw-bold mb-2"></h4>
            <p id="resultMessage" class="text-muted mb-3"></p>

            <div id="distanceInfo" style="display:none;" class="mb-3">
                <div class="d-flex justify-content-between small text-muted mb-1">
                    <span>Your distance</span>
                    <span id="distanceText"></span>
                </div>
                <div class="distance-bar">
                    <div class="distance-fill" id="distanceFill"></div>
                </div>
                <div class="text-muted mt-1" style="font-size:.72rem" id="accuracyResult"></div>
            </div>

            <div id="timeInfo" class="text-muted small mb-3"></div>

            <button class="btn btn-outline-secondary btn-scan mt-2" onclick="resetPage()">
                <i class="bi bi-arrow-repeat"></i> Scan Again
            </button>
        </div>
    </div>

    <script>
        const LOCATION_TOKEN = '{{ $token }}';
        const CSRF           = document.querySelector('meta[name=csrf-token]').content;
        const MAX_RADIUS     = parseInt('{{ $location->radius_meters }}', 10);
        const ACCURACY_GOAL  = 50;   // accept position if accuracy ≤ 50 m
        const MAX_WAIT_MS    = 30000; // give up after 30 s and use best seen so far

        let watchId      = null;
        let waitTimer    = null;
        let bestPosition = null; // track best (most accurate) position seen

        function show(id) {
            ['step1', 'step2', 'step3'].forEach(s => {
                document.getElementById(s).style.display = 'none';
            });
            document.getElementById(id).style.display = 'block';
        }

        function startGps() {
            const empToken = document.getElementById('qrInput').value.trim();
            if (!empToken) {
                alert('Please enter your employee QR token first.');
                return;
            }

            show('step2');
            document.getElementById('accuracyInfo').style.display = 'none';
            bestPosition = null;

            if (!navigator.geolocation) {
                showError('GPS not supported', 'Your browser does not support geolocation.');
                return;
            }

            // Use watchPosition so we keep receiving updates until we get a good fix.
            watchId = navigator.geolocation.watchPosition(
                pos => onPosition(empToken, pos),
                err => {
                    stopWatch();
                    let msg = 'Could not get your location. ';
                    if (err.code === 1) msg += 'Please allow location access in your browser settings.';
                    else if (err.code === 2) msg += 'GPS signal unavailable. Please try outside.';
                    else msg += 'Please try again.';
                    showError('Location Error', msg);
                },
                { enableHighAccuracy: true, maximumAge: 0, timeout: 30000 }
            );

            // Fallback: after MAX_WAIT_MS, submit the best position we have seen
            waitTimer = setTimeout(() => {
                stopWatch();
                if (bestPosition) {
                    submitAttendance(empToken, bestPosition.coords.latitude,
                                     bestPosition.coords.longitude, bestPosition.coords.accuracy);
                } else {
                    showError('GPS Timeout', 'Could not get a GPS fix. Please try again outside or near a window.');
                }
            }, MAX_WAIT_MS);
        }

        function onPosition(empToken, pos) {
            const acc = Math.round(pos.coords.accuracy);

            // Update accuracy display
            const info = document.getElementById('accuracyInfo');
            info.style.display = 'block';
            const badge = document.getElementById('accuracyBadge');
            badge.textContent = acc + 'm';
            badge.className = 'accuracy-badge ' +
                (acc <= 20 ? 'acc-good' : acc <= 60 ? 'acc-medium' : 'acc-poor');

            // Track the most accurate position seen
            if (!bestPosition || acc < bestPosition.coords.accuracy) {
                bestPosition = pos;
            }

            // Submit immediately if accuracy is good enough
            if (acc <= ACCURACY_GOAL) {
                stopWatch();
                submitAttendance(empToken, pos.coords.latitude, pos.coords.longitude, acc);
            }
        }

        function stopWatch() {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
            if (waitTimer !== null) {
                clearTimeout(waitTimer);
                waitTimer = null;
            }
        }

        function submitAttendance(empToken, lat, lng, accuracy) {
            fetch('{{ route("attendance.process") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({
                    employee_token: empToken,
                    location_token: LOCATION_TOKEN,
                    latitude:  lat,
                    longitude: lng,
                })
            })
            .then(r => r.json())
            .then(data => {
                show('step3');

                if (data.success) {
                    document.getElementById('resultIcon').textContent   = data.type === 'in' ? '✅' : '👋';
                    document.getElementById('resultTitle').textContent  = data.type === 'in' ? 'Checked In!' : 'Checked Out!';
                    document.getElementById('resultTitle').className    = 'fw-bold mb-2 text-success';
                    document.getElementById('resultMessage').textContent = data.name + ' at ' + data.location;

                    const distInfo = document.getElementById('distanceInfo');
                    distInfo.style.display = 'block';
                    document.getElementById('distanceText').textContent = data.distance + 'm away';
                    const pct = Math.min((data.distance / MAX_RADIUS) * 100, 100);
                    document.getElementById('distanceFill').style.width      = pct + '%';
                    document.getElementById('distanceFill').style.background = '#28a745';
                    document.getElementById('timeInfo').textContent = '🕐 ' + data.time;
                    if (accuracy) {
                        document.getElementById('accuracyResult').textContent = 'GPS accuracy: ±' + Math.round(accuracy) + 'm';
                    }
                } else {
                    document.getElementById('resultIcon').textContent   = '❌';
                    document.getElementById('resultTitle').textContent  = 'Out of Range';
                    document.getElementById('resultTitle').className    = 'fw-bold mb-2 text-danger';
                    document.getElementById('resultMessage').textContent = data.message;

                    if (data.distance !== undefined) {
                        const distInfo = document.getElementById('distanceInfo');
                        distInfo.style.display = 'block';
                        document.getElementById('distanceText').textContent = data.distance + 'm away';
                        const pct = Math.min((data.distance / MAX_RADIUS) * 100, 100);
                        document.getElementById('distanceFill').style.width      = pct + '%';
                        document.getElementById('distanceFill').style.background = '#dc3545';
                        if (accuracy) {
                            document.getElementById('accuracyResult').textContent = 'GPS accuracy: ±' + Math.round(accuracy) + 'm';
                        }
                    }
                }
            })
            .catch(() => showError('Error', 'Could not connect to server. Please try again.'));
        }

        function showError(title, msg) {
            show('step3');
            document.getElementById('resultIcon').textContent  = '⚠️';
            document.getElementById('resultTitle').textContent = title;
            document.getElementById('resultTitle').className   = 'fw-bold mb-2 text-warning';
            document.getElementById('resultMessage').textContent = msg;
        }

        function resetPage() {
            stopWatch();
            document.getElementById('qrInput').value = '';
            show('step1');
        }
    </script>
</body>

</html>
