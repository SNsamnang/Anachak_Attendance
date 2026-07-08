<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'Dashboard') — QR Check-In</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="{{ asset('icon.ico') }}">
    <style>
        :root {
            --sidebar-w: 256px;
            --topbar-h: 60px;
            --sidebar-bg: #0f172a;
            --sidebar-hover: rgba(255,255,255,.07);
            --sidebar-active: rgba(16,185,129,.15);
            --sidebar-active-border: #10b981;
            --accent: #10b981;
            --accent-dark: #059669;
            --body-bg: #f1f5f9;
            --card-bg: #ffffff;
            --card-shadow: 0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.06);
            --card-radius: 12px;
            --text-primary: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--body-bg);
            color: var(--text-primary);
            font-size: 14px;
            margin: 0;
        }

        /* ── Sidebar ───────────────────────────────────────── */
        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar-w);
            background: var(--sidebar-bg);
            display: flex;
            flex-direction: column;
            z-index: 1040;
            transition: transform .28s cubic-bezier(.4,0,.2,1);
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 20px;
            height: var(--topbar-h);
            border-bottom: 1px solid rgba(255,255,255,.07);
            color: #fff;
            font-weight: 700;
            font-size: 15px;
            flex-shrink: 0;
            text-decoration: none;
        }
        .sidebar-brand-icon {
            width: 32px; height: 32px;
            background: var(--accent);
            border-radius: 8px;
            display: grid; place-items: center;
            font-size: 16px; flex-shrink: 0;
        }

        .sidebar-section {
            color: rgba(255,255,255,.3);
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            padding: 20px 20px 6px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 20px;
            color: rgba(255,255,255,.6);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: all .18s;
            margin: 1px 0;
        }
        .sidebar-link:hover {
            color: #fff;
            background: var(--sidebar-hover);
        }
        .sidebar-link.active {
            color: #fff;
            background: var(--sidebar-active);
            border-left-color: var(--sidebar-active-border);
        }
        .sidebar-link i { font-size: 16px; flex-shrink: 0; }

        .sidebar-footer {
            margin-top: auto;
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,.07);
            flex-shrink: 0;
        }
        .sidebar-user {
            font-size: 11px;
            color: rgba(255,255,255,.4);
            margin-bottom: 8px;
        }
        .sidebar-logout {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            background: rgba(255,255,255,.08);
            border: none;
            color: rgba(255,255,255,.7);
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            transition: background .18s;
        }
        .sidebar-logout:hover { background: rgba(255,255,255,.15); color: #fff; }

        /* ── Overlay (mobile) ──────────────────────────────── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.55);
            z-index: 1039;
            backdrop-filter: blur(3px);
        }
        .sidebar-overlay.show { display: block; }

        /* ── Main content ──────────────────────────────────── */
        .layout-main {
            margin-left: var(--sidebar-w);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Topbar ────────────────────────────────────────── */
        .topbar {
            position: sticky;
            top: 0;
            height: var(--topbar-h);
            background: var(--card-bg);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 24px;
            gap: 12px;
            z-index: 100;
            flex-shrink: 0;
        }
        .topbar-hamburger {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 20px;
            cursor: pointer;
            padding: 4px;
            border-radius: 6px;
            line-height: 1;
        }
        .topbar-hamburger:hover { background: var(--body-bg); }
        .topbar-title {
            font-weight: 700;
            font-size: 16px;
            color: var(--text-primary);
        }
        .topbar-date {
            font-size: 12px;
            color: var(--text-muted);
        }
        .topbar-actions { margin-left: auto; display: flex; gap: 8px; }

        /* ── Page body ─────────────────────────────────────── */
        .page-body {
            padding: 24px;
            flex: 1;
        }

        /* ── Cards ─────────────────────────────────────────── */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
        }
        .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border);
            padding: 14px 18px;
            font-weight: 600;
            font-size: 13.5px;
            border-radius: var(--card-radius) var(--card-radius) 0 0 !important;
        }
        .card-body { padding: 18px; }
        .card-footer {
            background: transparent;
            border-top: 1px solid var(--border);
            padding: 12px 18px;
        }

        /* ── Stat cards ────────────────────────────────────── */
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--card-radius);
            box-shadow: var(--card-shadow);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: grid; place-items: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .stat-num {
            font-size: 26px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 2px;
        }
        .stat-label {
            font-size: 11.5px;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        /* ── Tables ────────────────────────────────────────── */
        .table { font-size: 13.5px; }
        .table th {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--text-muted);
            border-bottom-width: 1px;
            padding: 10px 14px;
            white-space: nowrap;
        }
        .table td { padding: 10px 14px; vertical-align: middle; }
        .table-hover tbody tr:hover { background: #f8fafc; }

        /* ── Buttons ───────────────────────────────────────── */
        .btn { font-size: 13px; font-weight: 500; border-radius: 8px; }
        .btn-sm { font-size: 12px; padding: 5px 12px; }
        .btn-primary { background: var(--accent); border-color: var(--accent); }
        .btn-primary:hover { background: var(--accent-dark); border-color: var(--accent-dark); }
        .btn-outline-primary { color: var(--accent); border-color: var(--accent); }
        .btn-outline-primary:hover { background: var(--accent); border-color: var(--accent); }

        /* ── Badges ────────────────────────────────────────── */
        .badge { font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 6px; }
        .badge-in  { background: #dcfce7; color: #166534; }
        .badge-out { background: #fee2e2; color: #991b1b; }

        /* ── Forms ─────────────────────────────────────────── */
        .form-control, .form-select {
            border-color: var(--border);
            border-radius: 8px;
            font-size: 13.5px;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(16,185,129,.15);
        }
        .form-label { font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; }
        .input-group-text { border-color: var(--border); font-size: 13px; }

        /* ── Alert ─────────────────────────────────────────── */
        .alert { border-radius: 10px; font-size: 13.5px; border: none; }
        .alert-success { background: #dcfce7; color: #166534; }
        .alert-danger  { background: #fee2e2; color: #991b1b; }
        .alert-warning { background: #fef9c3; color: #854d0e; }

        /* ── Pagination ────────────────────────────────────── */
        .pagination { font-size: 13px; }
        .page-link { border-color: var(--border); color: var(--text-primary); border-radius: 6px; margin: 0 2px; }
        .page-link:hover { background: var(--body-bg); color: var(--accent); }
        .page-item.active .page-link { background: var(--accent); border-color: var(--accent); }

        /* ── Mobile ────────────────────────────────────────── */
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .layout-main { margin-left: 0; }
            .topbar-hamburger { display: block; }
            .topbar { padding: 0 16px; }
            .page-body { padding: 16px; }
            .topbar-date { display: none; }
            .stat-card { padding: 14px; }
            .stat-num { font-size: 22px; }
            .stat-icon { width: 40px; height: 40px; font-size: 18px; }
            .table th, .table td { padding: 8px 10px; }
        }

        @media (max-width: 575.98px) {
            .topbar-actions .btn span { display: none; }
            .topbar-actions .btn i { margin: 0 !important; }
        }

        /* ── Misc ──────────────────────────────────────────── */
        code { background: #f1f5f9; color: #0f172a; padding: 1px 6px; border-radius: 4px; font-size: 12px; }
        .text-muted { color: var(--text-muted) !important; }
        hr { border-color: var(--border); }
        .modal-content { border-radius: 14px; border: none; }
        .modal-header { border-bottom: 1px solid var(--border); }
        .modal-footer { border-top: 1px solid var(--border); }
    </style>
    @stack('styles')
</head>

<body>

{{-- Mobile overlay --}}
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

{{-- Sidebar --}}
<aside class="sidebar" id="sidebar">
    <a href="{{ route('dashboard') }}" class="sidebar-brand">
        <div class="sidebar-brand-icon">📋</div>
        QR Check-In
    </a>

    @php $authUser = auth()->user(); @endphp
    <nav style="flex:1; padding: 8px 0;">
        @if($authUser?->hasPermission('dashboard'))
        <div class="sidebar-section">Main</div>
        <a class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           href="{{ route('dashboard') }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        @endif

        @php $hasAnyManagement = $authUser?->hasPermission('employees') || $authUser?->hasPermission('ot-status') || $authUser?->hasPermission('attendance') || $authUser?->hasPermission('leave-requests') || $authUser?->hasPermission('salary-summaries') || $authUser?->hasPermission('devices'); @endphp
        @if($hasAnyManagement)
        <div class="sidebar-section">Management</div>
        @endif

        @if($authUser?->hasPermission('employees'))
        <a class="sidebar-link {{ request()->routeIs('employees*') ? 'active' : '' }}"
           href="{{ route('employees.index') }}">
            <i class="bi bi-people"></i> Employees
        </a>
        @endif

        @if($authUser?->hasPermission('ot-status') && ($authUser->is_super_admin || $authUser->company?->salary_enabled))
        <a class="sidebar-link {{ request()->routeIs('ot-status*') ? 'active' : '' }}"
           href="{{ route('ot-status.index') }}">
            <i class="bi bi-graph-up-arrow"></i> OT Eligibility
        </a>
        @endif

        @if($authUser?->hasPermission('attendance'))
        <a class="sidebar-link {{ request()->routeIs('attendance*') ? 'active' : '' }}"
           href="{{ route('attendance.index') }}">
            <i class="bi bi-clock-history"></i> Attendance Log
        </a>
        @endif

        @if($authUser?->hasPermission('locations'))
        <a class="sidebar-link {{ request()->routeIs('locations*') ? 'active' : '' }}"
           href="{{ route('locations.index') }}">
            <i class="bi bi-geo-alt"></i> Locations
        </a>
        @endif

        @if($authUser?->hasPermission('leave-requests'))
        <a class="sidebar-link {{ request()->routeIs('leave-requests*') ? 'active' : '' }}"
           href="{{ route('leave-requests.index') }}">
            <i class="bi bi-calendar-check"></i> Leave Requests
            @php $leaveCount = \App\Models\LeaveRequest::where('status','pending')->count(); @endphp
            @if($leaveCount)
            <span class="badge ms-auto" style="background:#f59e0b;color:#fff">{{ $leaveCount }}</span>
            @endif
        </a>
        @endif

        @if($authUser?->hasPermission('salary-summaries') && ($authUser->is_super_admin || $authUser->company?->salary_enabled))
        <a class="sidebar-link {{ request()->routeIs('salary-summaries*') ? 'active' : '' }}"
           href="{{ route('salary-summaries.index') }}">
            <i class="bi bi-cash-coin"></i> Salary Summaries
        </a>
        @endif

        @if($authUser?->hasPermission('devices'))
        <a class="sidebar-link {{ request()->routeIs('devices*') ? 'active' : '' }}"
           href="{{ route('devices.index') }}">
            <i class="bi bi-phone"></i> Devices
            @php $pendingCount = \App\Models\Device::where('status','pending')->count(); @endphp
            @if($pendingCount)
            <span class="badge ms-auto" style="background:#f59e0b;color:#fff">{{ $pendingCount }}</span>
            @endif
        </a>
        @endif

        @if($authUser?->hasPermission('users') || $authUser?->hasPermission('roles') || $authUser?->hasPermission('companies'))
        <div class="sidebar-section">Admin</div>
        @endif

        @if($authUser?->hasPermission('companies'))
        <a class="sidebar-link {{ request()->routeIs('companies*') ? 'active' : '' }}"
           href="{{ route('companies.index') }}">
            <i class="bi bi-building"></i> Companies
        </a>
        @endif

        @if($authUser?->hasPermission('users'))
        <a class="sidebar-link {{ request()->routeIs('users*') ? 'active' : '' }}"
           href="{{ route('users.index') }}">
            <i class="bi bi-person-gear"></i> Users
        </a>
        @endif

        @if($authUser?->hasPermission('roles'))
        <a class="sidebar-link {{ request()->routeIs('roles*') ? 'active' : '' }}"
           href="{{ route('roles.index') }}">
            <i class="bi bi-shield-lock"></i> Roles & Permissions
        </a>
        @endif
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <i class="bi bi-person-circle me-1"></i>
            {{ $authUser?->name ?? 'Admin' }}
            @if($authUser?->is_super_admin)
            <span class="ms-1" style="color:rgba(16,185,129,.8);font-size:10px">● Super Admin</span>
            @elseif($authUser?->role)
            <span class="ms-1" style="color:rgba(255,255,255,.35);font-size:10px">● {{ $authUser->role->name }}</span>
            @endif
        </div>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="sidebar-logout">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
    </div>
</aside>

{{-- Main --}}
<div class="layout-main">

    {{-- Topbar --}}
    <header class="topbar">
        <button class="topbar-hamburger" id="hamburgerBtn" onclick="openSidebar()" aria-label="Open menu">
            <i class="bi bi-list"></i>
        </button>
        <div>
            <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
            <div class="topbar-date">{{ now()->format('D, d M Y') }}</div>
        </div>
        <!-- <div class="topbar-actions">
            <a href="{{ route('locations.create') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-geo-alt-fill me-1"></i><span>Add Location</span>
            </a>
            <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i><span>Add Employee</span>
            </a>
        </div> -->
    </header>

    {{-- Flash messages --}}
    @if(session('success'))
    <div style="padding: 12px 24px 0">
        <div class="alert alert-success alert-dismissible fade show mb-0">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    @endif
    @if(session('error'))
    <div style="padding: 12px 24px 0">
        <div class="alert alert-danger alert-dismissible fade show mb-0">
            <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    @endif

    {{-- Page content --}}
    <main class="page-body">
        @yield('content')
    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function openSidebar() {
        document.getElementById('sidebar').classList.add('open');
        document.getElementById('sidebarOverlay').classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
        document.body.style.overflow = '';
    }
    // Close sidebar on Escape
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });
    // Auto-close on link click (mobile)
    document.querySelectorAll('.sidebar-link').forEach(l => {
        l.addEventListener('click', () => { if (window.innerWidth < 992) closeSidebar(); });
    });
</script>
@stack('scripts')
</body>
</html>
