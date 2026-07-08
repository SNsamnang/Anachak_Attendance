@extends('layouts.app')
@section('page-title','Attendance Log')
@section('content')

{{-- Filter card --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label">Date Range</label>
                    <div class="d-flex align-items-center gap-1">
                        <input type="date" name="date_from" class="form-control form-control-sm"
                            value="{{ request('date_from') }}">
                        <span class="text-muted small">to</span>
                        <input type="date" name="date_to" class="form-control form-control-sm"
                            value="{{ request('date_to') }}">
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Employee</label>
                    <select name="employee_id" class="form-select form-select-sm">
                        <option value="">All employees</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" @selected(request('employee_id')==$emp->id)>{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Location</label>
                    <select name="location_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" @selected(request('location_id')==$loc->id)>{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 col-md-1">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="in"  @selected(request('type')==='in')>In</option>
                        <option value="out" @selected(request('type')==='out')>Out</option>
                    </select>
                </div>
                <div class="col-4 col-md-1">
                    <label class="form-label">CI Status</label>
                    <select name="ci_status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="on_time" @selected(request('ci_status')==='on_time')>On Time</option>
                        <option value="late"    @selected(request('ci_status')==='late')>Late</option>
                    </select>
                </div>
                <div class="col-4 col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
    <small class="text-muted">{{ $attendances->total() }} record(s)</small>
    <div class="d-flex gap-1 flex-wrap">
        <a href="{{ route('attendance.export.excel') }}?{{ http_build_query(request()->only(['date_from','date_to','employee_id','location_id','type'])) }}"
           class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
        </a>
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-file-earmark-arrow-up me-1"></i>Import Excel
        </button>
        <a href="{{ route('attendance.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add Record
        </a>
    </div>
</div>

@if(session('import_errors') && count(session('import_errors')))
<div class="alert alert-warning small mb-2">
    <strong><i class="bi bi-exclamation-triangle me-1"></i>Import warnings:</strong>
    <ul class="mb-0 mt-1">
        @foreach(session('import_errors') as $err)
        <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Import Modal --}}
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2"></i>Import Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small mb-3">
                    <strong>Required columns (row 1 must be headers):</strong><br>
                    <code>employee_id</code> · <code>location</code> · <code>type</code> (in/out) · <code>scan_date_time</code> (YYYY-MM-DD HH:MM:SS)
                    <div class="mt-2">
                        <a href="{{ route('attendance.import.template') }}" class="btn btn-sm btn-outline-info">
                            <i class="bi bi-download me-1"></i>Download Template
                        </a>
                    </div>
                </div>
                <form id="importForm" method="POST" action="{{ route('attendance.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Select file</label>
                        <input type="file" name="file" class="form-control form-control-sm"
                            accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">Max 5 MB · .xlsx, .xls, .csv</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm"
                    onclick="document.getElementById('importForm').submit()">
                    <i class="bi bi-upload me-1"></i>Upload & Import
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Desktop table --}}
<div class="card d-none d-lg-block">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Employee</th>
                    <th>Location</th>
                    <th>Type</th>
                    <th>Scan Time</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>OT</th>
                    <th>GPS</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $att)
                <tr>
                    <td class="text-muted">{{ ($attendances->currentPage()-1)*$attendances->perPage()+$loop->iteration }}</td>
                    <td>
                        <div class="fw-semibold" style="font-size:13px">{{ $att->employee->name }}</div>
                        <small class="text-muted">
                            {{ $att->employee->employee_id }}
                            · {{ $att->employee->workStartFormatted() }}–{{ $att->employee->workEndFormatted() }}
                        </small>
                    </td>
                    <td><small>{{ $att->location->name }}</small></td>
                    <td>
                        @if($att->type === 'in')
                        <span class="badge badge-in">Check In</span>
                        @else
                        <span class="badge badge-out">Check Out</span>
                        @endif
                    </td>
                    <td>
                        <span class="fw-semibold">{{ $att->scanned_at->format('H:i') }}</span>
                        <small class="text-muted d-block">{{ $att->scanned_at->format('d M Y') }}</small>
                        @if($att->ip_address === 'admin')
                        <small class="text-primary d-block"><i class="bi bi-pencil-fill me-1"></i>Manual</small>
                        @endif
                    </td>
                    <td>{!! $att->checkInBadge() !!}</td>
                    <td>{!! $att->checkOutBadge() !!}</td>
                    <td>
                        @if($att->ot_seconds > 0)
                        <span class="badge" style="background:#ede9fe;color:#5b21b6">{{ $att->otFormatted() }}</span>
                        @else <span class="text-muted">—</span> @endif
                    </td>
                    <td>
                        @if($att->ip_address === 'admin')
                        <small class="text-muted"><i class="bi bi-pencil-fill"></i> Manual</small>
                        @elseif($att->location_verified)
                        <small class="text-success"><i class="bi bi-check-circle-fill"></i> {{ $att->distance_meters }}m</small>
                        @else
                        <small class="text-danger"><i class="bi bi-x-circle-fill"></i> Out of range</small>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="{{ route('attendance.edit', $att) }}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('attendance.destroy', $att) }}"
                                onsubmit="return confirm('Delete this record for {{ addslashes($att->employee->name) }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-5">
                        <i class="bi bi-clock-history d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                        No records found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($attendances->hasPages())
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">Page {{ $attendances->currentPage() }} of {{ $attendances->lastPage() }}</small>
        {{ $attendances->links() }}
    </div>
    @endif
</div>

{{-- Mobile / Tablet cards --}}
<div class="d-lg-none">
    @forelse($attendances as $att)
    <div class="card mb-2">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                    <div class="fw-bold" style="font-size:13.5px">{{ $att->employee->name }}</div>
                    <small class="text-muted">{{ $att->employee->employee_id }}</small>
                </div>
                <div class="text-end">
                    <div class="fw-bold">{{ $att->scanned_at->format('H:i') }}</div>
                    <small class="text-muted">{{ $att->scanned_at->format('d M Y') }}</small>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-1 mb-2">
                @if($att->type === 'in')
                <span class="badge badge-in">Check In</span>
                @else
                <span class="badge badge-out">Check Out</span>
                @endif
                {!! $att->checkInBadge() !!}
                {!! $att->checkOutBadge() !!}
                @if($att->ot_seconds > 0)
                <span class="badge" style="background:#ede9fe;color:#5b21b6">OT {{ $att->otFormatted() }}</span>
                @endif
                @if($att->ip_address === 'admin')
                <span class="badge" style="background:#f1f5f9;color:#475569"><i class="bi bi-pencil-fill me-1"></i>Manual</span>
                @elseif($att->location_verified)
                <span class="badge" style="background:#dcfce7;color:#166534"><i class="bi bi-check-circle-fill me-1"></i>{{ $att->distance_meters }}m</span>
                @else
                <span class="badge" style="background:#fee2e2;color:#991b1b"><i class="bi bi-x-circle-fill me-1"></i>Out of range</span>
                @endif
            </div>
            <small class="text-muted d-block mb-2"><i class="bi bi-geo-alt me-1"></i>{{ $att->location->name }}</small>
            <div class="d-flex gap-1">
                <a href="{{ route('attendance.edit', $att) }}" class="btn btn-sm btn-outline-warning">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
                <form method="POST" action="{{ route('attendance.destroy', $att) }}"
                    onsubmit="return confirm('Delete this record?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Delete</button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-clock-history d-block mb-2" style="font-size:2rem;opacity:.3"></i>
            No records found.
        </div>
    </div>
    @endforelse
    @if($attendances->hasPages())
    <div class="mt-3">{{ $attendances->links() }}</div>
    @endif
</div>

@endsection
