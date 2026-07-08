@extends('layouts.app')
@section('page-title', 'Device Management')

@section('content')

{{-- Pending requests --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">📱 Device Management</h5>
    @if($pending->count())
    <span class="badge bg-warning text-dark fs-6">
        {{ $pending->count() }} pending request(s)
    </span>
    @endif
</div>

@if($pending->count())
<div class="card mb-4 border-warning">
    <div class="card-header bg-warning text-dark fw-semibold p-3">
        ⏳ Pending Approval ({{ $pending->count() }})
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Device Name</th>
                    <th>Requested At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pending as $device)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $device->employee->name }}</div>
                        <small class="text-muted">{{ $device->employee->employee_id }} · {{ $device->employee->department }}</small>
                    </td>
                    <td>
                        <i class="bi bi-phone"></i> {{ $device->name }}
                        @if($device->fingerprint)
                        <br><small class="text-muted">{{ Str::limit($device->fingerprint, 40) }}</small>
                        @endif
                    </td>
                    <td>
                        <small>{{ $device->requested_at?->format('H:i · d M Y') ?? '—' }}</small>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            {{-- Approve --}}
                            <form method="POST" action="{{ route('devices.approve', $device) }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm"
                                    onclick="return confirm('Approve device for {{ $device->employee->name }}?')">
                                    <i class="bi bi-check-lg"></i> Approve
                                </button>
                            </form>

                            {{-- Reject with reason --}}
                            <button type="button" class="btn btn-danger btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#rejectModal{{ $device->id }}">
                                <i class="bi bi-x-lg"></i> Reject
                            </button>
                        </div>

                        {{-- Reject Modal --}}
                        <div class="modal fade" id="rejectModal{{ $device->id }}" tabindex="-1">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h6 class="modal-title">Reject Device Request</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="{{ route('devices.reject', $device) }}">
                                        @csrf
                                        <div class="modal-body">
                                            <p class="small text-muted mb-2">
                                                Rejecting device for <strong>{{ $device->employee->name }}</strong>
                                            </p>
                                            <label class="form-label small fw-semibold">Reason (optional)</label>
                                            <input type="text" name="reason" class="form-control form-control-sm"
                                                placeholder="e.g. Not authorized">
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm"
                                                data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="alert alert-success mb-4">
    <i class="bi bi-check-circle"></i> No pending device requests.
</div>
@endif

{{-- Approved devices --}}
<div class="card mb-4">
    <div class="card-header fw-semibold p-3">
        ✅ Approved Devices ({{ $approved->count() }})
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Device Name</th>
                    <th>Approved At</th>
                    <th>Last Used</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($approved as $device)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $device->employee->name }}</div>
                        <small class="text-muted">{{ $device->employee->employee_id }}</small>
                    </td>
                    <td><i class="bi bi-phone-fill text-success"></i> {{ $device->name }}</td>
                    <td><small>{{ $device->approved_at?->format('d M Y') ?? '—' }}</small></td>
                    <td>
                        <small class="text-muted">
                            {{ $device->last_used_at ? $device->last_used_at->diffForHumans() : 'Never' }}
                        </small>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('devices.revoke', $device) }}"
                            onsubmit="return confirm('Revoke this device? Employee will not be able to check in.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-slash-circle"></i> Revoke
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">No approved devices yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Rejected --}}
@if($rejected->count())
<div class="card mb-4">
    <div class="card-header fw-semibold p-3 text-muted">
        ❌ Recently Rejected (last 30)
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Device</th>
                    <th>Reason</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rejected as $device)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $device->employee->name }}</div>
                        <small class="text-muted">{{ $device->employee->employee_id }}</small>
                    </td>
                    <td>{{ $device->name }}</td>
                    <td><small class="text-danger">{{ $device->rejected_reason ?? '—' }}</small></td>
                    <td><small class="text-muted">{{ $device->updated_at->format('d M Y') }}</small></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection