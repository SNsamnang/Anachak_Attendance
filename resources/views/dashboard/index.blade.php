@extends('layouts.app')
@section('page-title','Dashboard')
@section('content')

{{-- Stat cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#eff6ff;color:#3b82f6">
                <i class="bi bi-people"></i>
            </div>
            <div>
                <div class="stat-num" style="color:#3b82f6">{{ $totalEmployees }}</div>
                <div class="stat-label">Employees</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#f0fdf4;color:#10b981">
                <i class="bi bi-geo-alt"></i>
            </div>
            <div>
                <div class="stat-num" style="color:#10b981">{{ $totalLocations }}</div>
                <div class="stat-label">Locations</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#f0fdf4;color:#16a34a">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div>
                <div class="stat-num" style="color:#16a34a">{{ $checkedInToday }}</div>
                <div class="stat-label">Checked In Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fff7ed;color:#f59e0b">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div>
                <div class="stat-num" style="color:#f59e0b">{{ $rejectedToday }}</div>
                <div class="stat-label">Rejected Today</div>
            </div>
        </div>
    </div>
</div>

{{-- Chart + Currently inside --}}
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-bar-chart me-2 text-primary"></i>Weekly Check-ins
            </div>
            <div class="card-body">
                <canvas id="weeklyChart" height="130"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="d-inline-block rounded-circle bg-success" style="width:8px;height:8px"></span>
                Currently Inside
                <span class="badge ms-auto" style="background:#dcfce7;color:#166534">{{ $currentlyInside->count() }}</span>
            </div>
            <div style="overflow-y:auto; max-height:220px">
                @forelse($currentlyInside as $emp)
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <div>
                        <div class="fw-semibold" style="font-size:13px">{{ $emp->name }}</div>
                        <small class="text-muted">{{ $emp->lastAttendance->location->name ?? '' }}</small>
                    </div>
                    <span class="badge" style="background:#dcfce7;color:#166534;font-size:11px">
                        {{ $emp->lastAttendance->scanned_at->format('H:i') }}
                    </span>
                </div>
                @empty
                <p class="text-muted small p-3 mb-0">Nobody is currently inside.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Location stats --}}
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-geo-alt me-2 text-success"></i>Location Stats Today</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Location</th>
                    <th>Radius</th>
                    <th class="text-center">Check-ins</th>
                    <th class="text-center">Rejected</th>
                    <th class="text-center">QR</th>
                </tr>
            </thead>
            <tbody>
                @foreach($locationStats as $loc)
                <tr>
                    <td class="fw-semibold">{{ $loc->name }}</td>
                    <td><span class="badge" style="background:#e0f2fe;color:#0369a1">{{ $loc->radius_meters }}m</span></td>
                    <td class="text-center"><span class="badge" style="background:#dcfce7;color:#166534">{{ $loc->checkins_today }}</span></td>
                    <td class="text-center">
                        @if($loc->rejected_today > 0)
                        <span class="badge" style="background:#fee2e2;color:#991b1b">{{ $loc->rejected_today }}</span>
                        @else <span class="text-muted">0</span> @endif
                    </td>
                    <td class="text-center">
                        <a href="{{ route('locations.qr', $loc) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-qr-code"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Recent activity --}}
<div class="card">
    <div class="card-header d-flex align-items-center gap-2 flex-wrap">
        <i class="bi bi-activity me-1 text-success"></i>Recent Activity
        <span class="badge ms-1" style="background:#dcfce7;color:#166534;font-size:10px">● LIVE</span>
        <!-- <a href="{{ route('dashboard.export') }}" class="btn btn-outline-secondary btn-sm ms-auto">
            <i class="bi bi-download me-1"></i>Export CSV
        </a> -->
    </div>
    <div class="table-responsive" id="activityFeed">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Location</th>
                    <th>Type</th>
                    <th>Distance</th>
                    <th class="text-center">GPS</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentActivity as $act)
                <tr>
                    <td>
                        <div class="fw-semibold" style="font-size:13px">{{ $act->employee->name }}</div>
                        <small class="text-muted">{{ $act->employee->employee_id }}</small>
                    </td>
                    <td><small>{{ $act->location->name }}</small></td>
                    <td>
                        @if($act->type === 'in')
                        <span class="badge badge-in">Check In</span>
                        @else
                        <span class="badge badge-out">Check Out</span>
                        @endif
                    </td>
                    <td>
                        @if($act->distance_meters)
                        <span class="{{ $act->location_verified ? 'text-success' : 'text-danger' }}">
                            {{ $act->distance_meters }}m
                        </span>
                        @else <span class="text-muted">—</span> @endif
                    </td>
                    <td class="text-center">
                        @if($act->location_verified)
                        <i class="bi bi-check-circle-fill text-success"></i>
                        @else
                        <i class="bi bi-x-circle-fill text-danger"></i>
                        @endif
                    </td>
                    <td><small class="text-muted fw-semibold">{{ $act->scanned_at->format('H:i') }}</small></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('weeklyChart'), {
    type: 'bar',
    data: {
        labels: @json($weeklyLabels),
        datasets: [{
            label: 'Check-ins',
            data: @json($weeklyData),
            backgroundColor: 'rgba(16,185,129,.75)',
            borderRadius: 6,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
        responsive: true,
    }
});
setInterval(() => {
    fetch('{{ route("dashboard.live") }}')
        .then(r => r.json())
        .then(data => { if (data.length) location.reload(); });
}, 30000);
</script>
@endpush
