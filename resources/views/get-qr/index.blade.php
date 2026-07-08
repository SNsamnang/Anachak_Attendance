<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee QR Codes — QR Check-In</title>
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
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 15px;
            color: #f1f5f9;
        }
        .topbar-brand .brand-icon {
            width: 34px; height: 34px;
            background: #10b981;
            border-radius: 9px;
            display: grid;
            place-items: center;
            font-size: 16px;
        }
        .topbar-meta {
            font-size: 12.5px;
            color: #64748b;
        }

        /* Content */
        .content {
            max-width: 960px;
            margin: 32px auto;
            padding: 0 16px;
        }

        /* Page header */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .page-header h5 {
            font-size: 16px;
            font-weight: 700;
            color: #f1f5f9;
            margin: 0;
        }
        .page-header small {
            color: #64748b;
            font-size: 12.5px;
        }

        /* Card */
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 14px;
            overflow: hidden;
        }
        .card-header {
            background: #1e293b;
            border-bottom: 1px solid #334155;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13.5px;
            font-weight: 600;
            color: #f1f5f9;
        }
        .badge-count {
            background: #0f172a;
            color: #94a3b8;
            border: 1px solid #334155;
            border-radius: 20px;
            padding: 2px 10px;
            font-size: 12px;
            font-weight: 500;
        }

        /* Table */
        .table {
            color: #cbd5e1;
            font-size: 13.5px;
            margin: 0;
        }
        .table thead th {
            background: #0f172a;
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .6px;
            border-color: #334155;
            padding: 10px 16px;
        }
        .table tbody td {
            border-color: #1e293b;
            padding: 12px 16px;
            vertical-align: middle;
        }
        .table tbody tr {
            background: #1e293b;
            transition: background .12s;
        }
        .table tbody tr:hover { background: #263348; }

        .emp-name { font-weight: 600; color: #0f172a; font-size: 13.5px; }
        .emp-id   { color: #64748b; font-size: 12px; font-family: monospace; }
        .work-hrs { font-size: 12px; color: #94a3b8; }

        .dept-badge {
            display: inline-block;
            background: #0f172a;
            color: #94a3b8;
            border: 1px solid #334155;
            border-radius: 6px;
            padding: 2px 9px;
            font-size: 11.5px;
        }

        /* View QR button */
        .btn-qr {
            background: #10b981;
            border: none;
            color: #fff;
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            transition: background .15s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-qr:hover { background: #059669; color: #fff; }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: #475569;
        }
        .empty-state i { font-size: 2.5rem; opacity: .3; display: block; margin-bottom: 10px; }

        /* QR Modal overlay */
        .qr-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.7);
            backdrop-filter: blur(4px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .qr-overlay.show { display: flex; }

        .qr-box {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 20px;
            padding: 28px 24px 24px;
            max-width: 340px;
            width: 100%;
            text-align: center;
            position: relative;
            box-shadow: 0 24px 64px rgba(0,0,0,.5);
        }
        .qr-close {
            position: absolute;
            top: 14px; right: 14px;
            background: #334155;
            border: none;
            border-radius: 50%;
            width: 30px; height: 30px;
            color: #94a3b8;
            cursor: pointer;
            font-size: 14px;
            display: grid;
            place-items: center;
            transition: background .15s;
        }
        .qr-close:hover { background: #475569; color: #f1f5f9; }
        .qr-emp-name {
            font-size: 16px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 4px;
        }
        .qr-emp-sub {
            font-size: 12.5px;
            color: #64748b;
            margin-bottom: 18px;
        }
        .qr-img-wrap {
            background: #fff;
            border-radius: 14px;
            padding: 12px;
            display: inline-block;
            margin-bottom: 18px;
        }
        .qr-img-wrap img {
            width: 200px;
            height: 200px;
            display: block;
            border-radius: 6px;
        }
        .btn-dl {
            background: #10b981;
            border: none;
            color: #fff;
            border-radius: 10px;
            padding: 10px 20px;
            font-size: 13.5px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            text-decoration: none;
            display: block;
            transition: background .15s;
        }
        .btn-dl:hover { background: #059669; color: #fff; }

        /* Mobile list cards */
        .emp-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 10px;
        }
        .emp-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

{{-- Topbar --}}
<div class="topbar">
    <div class="topbar-brand">
        <div class="brand-icon">🔲</div>
        Employee QR Codes
    </div>
    <div class="topbar-meta">{{ now()->format('D, d M Y') }}</div>
</div>

<div class="content">

    <div class="page-header">
        <div>
            <h5>All Employees</h5>
            <small>{{ $employees->count() }} employees registered</small>
        </div>
    </div>

    {{-- Desktop table --}}
    <div class="card d-none d-md-block">
        <div class="card-header">
            <span><i class="bi bi-qr-code me-2" style="color:#10b981"></i>QR Code List</span>
            <span class="badge-count">{{ $employees->count() }} employees</span>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Work Hours</th>
                        <th class="text-center">QR Code</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    @php
                        $format      = extension_loaded('imagick') ? 'png' : 'svg';
                        $checkInUrl  = route('employees.check-in-page', $employee->qr_token);
                        $qrCode      = \SimpleSoftwareIO\QrCode\Facades\QrCode::format($format)
                                           ->size(300)->errorCorrection('H')->generate($checkInUrl);
                        $qrBase64    = base64_encode((string) $qrCode);
                        $qrMime      = $format === 'svg' ? 'image/svg+xml' : 'image/png';
                        $qrDataUrl   = "data:{$qrMime};base64,{$qrBase64}";
                    @endphp
                    <tr>
                        <td class="text-muted">{{ $loop->iteration }}</td>
                        <td>
                            <div class="emp-name">{{ $employee->name }}</div>
                            <div class="emp-id">{{ $employee->employee_id }}</div>
                        </td>
                        <td>
                            @if($employee->department)
                            <span class="dept-badge">{{ $employee->department }}</span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="work-hrs">
                                <i class="bi bi-clock me-1" style="color:#10b981"></i>
                                {{ $employee->workStartFormatted() }} – {{ $employee->workEndFormatted() }}
                            </span>
                        </td>
                        <td class="text-center">
                            <button class="btn-qr" onclick="showQr(
                                '{{ addslashes($employee->name) }}',
                                '{{ $employee->employee_id }}',
                                '{{ addslashes($employee->department ) }}',
                                '{{ $qrDataUrl }}',
                                '{{ $employee->employee_id }}-qr.{{ $format }}'
                            )">
                                <i class="bi bi-qr-code"></i> View QR
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="bi bi-people"></i>
                                No employees found.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mobile cards --}}
    <div class="d-md-none">
        @forelse($employees as $employee)
        @php
            $format      = extension_loaded('imagick') ? 'png' : 'svg';
            $checkInUrl  = route('employees.check-in-page', $employee->qr_token);
            $qrCode      = \SimpleSoftwareIO\QrCode\Facades\QrCode::format($format)
                               ->size(300)->errorCorrection('H')->generate($checkInUrl);
            $qrBase64    = base64_encode((string) $qrCode);
            $qrMime      = $format === 'svg' ? 'image/svg+xml' : 'image/png';
            $qrDataUrl   = "data:{$qrMime};base64,{$qrBase64}";
        @endphp
        <div class="emp-card">
            <div class="emp-card-top">
                <div>
                    <div class="emp-name">{{ $employee->name }}</div>
                    <div class="emp-id">{{ $employee->employee_id }}</div>
                </div>
                @if($employee->department)
                <span class="dept-badge">{{ $employee->department }}</span>
                @endif
            </div>
            <div class="work-hrs mb-3">
                <i class="bi bi-clock me-1" style="color:#10b981"></i>
                {{ $employee->workStartFormatted() }} – {{ $employee->workEndFormatted() }}
            </div>
            <button class="btn-qr w-100 justify-content-center" onclick="showQr(
                '{{ addslashes($employee->name) }}',
                '{{ $employee->employee_id }}',
                '{{ addslashes($employee->department ) }}',
                '{{ $qrDataUrl }}',
                '{{ $employee->employee_id }}-qr.{{ $format }}'
            )">
                <i class="bi bi-qr-code"></i> View QR Code
            </button>
        </div>
        @empty
        <div class="emp-card">
            <div class="empty-state">
                <i class="bi bi-people"></i>
                No employees found.
            </div>
        </div>
        @endforelse
    </div>

</div>

{{-- QR Modal --}}
<div class="qr-overlay" id="qrOverlay" onclick="closeQrOverlay(event)">
    <div class="qr-box">
        <button class="qr-close" onclick="hideQr()"><i class="bi bi-x-lg"></i></button>
        <div class="qr-emp-name" id="qrName"></div>
        <div class="qr-emp-sub" id="qrSub"></div>
        <div class="qr-img-wrap">
            <img id="qrImg" src="" alt="QR Code">
        </div>
        <a id="qrDownloadBtn" href="#" download="" class="btn-dl">
            <i class="bi bi-download me-2"></i>Save QR Code
        </a>
    </div>
</div>

<script>
function showQr(name, empId, dept, dataUrl, filename) {
    document.getElementById('qrName').textContent = name;
    document.getElementById('qrSub').textContent  = empId + (dept ? ' · ' + dept : '');
    document.getElementById('qrImg').src          = dataUrl;
    document.getElementById('qrDownloadBtn').href = dataUrl;
    document.getElementById('qrDownloadBtn').download = filename;
    document.getElementById('qrOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function hideQr() {
    document.getElementById('qrOverlay').classList.remove('show');
    document.body.style.overflow = '';
}
function closeQrOverlay(e) {
    if (e.target === document.getElementById('qrOverlay')) hideQr();
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') hideQr(); });
</script>

</body>
</html>
