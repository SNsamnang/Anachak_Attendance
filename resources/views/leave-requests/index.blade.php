@extends('layouts.app')
@section('page-title','Leave Requests')
@section('content')

{{-- Filter --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="pending"  @selected(request('status')==='pending')>Pending</option>
                        <option value="approved" @selected(request('status')==='approved')>Approved</option>
                        <option value="rejected" @selected(request('status')==='rejected')>Rejected</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="day_off"  @selected(request('type')==='day_off')>Day Off</option>
                        <option value="time_off" @selected(request('type')==='time_off')>Time Off</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">Employee</label>
                    <select name="employee_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" @selected(request('employee_id')==$emp->id)>{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="{{ route('leave-requests.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>
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
                    <th>Type</th>
                    <th>Date / Period</th>
                    <th>Reason</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Admin Note</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr>
                    <td class="text-muted">{{ ($requests->currentPage()-1)*$requests->perPage()+$loop->iteration }}</td>
                    <td>
                        <div class="fw-semibold" style="font-size:13px">{{ $req->employee->name }}</div>
                        <small class="text-muted">{{ $req->employee->employee_id }}</small>
                    </td>
                    <td>
                        @if($req->type === 'day_off')
                        <span class="badge" style="background:#ede9fe;color:#5b21b6">Day Off</span>
                        @else
                        <span class="badge" style="background:#e0f2fe;color:#0369a1">Time Off</span>
                        @endif
                    </td>
                    <td>
                        <span class="fw-semibold">{{ $req->durationLabel() }}</span>
                    </td>
                    <td style="max-width:180px"><small class="text-muted">{{ $req->reason ?: '—' }}</small></td>
                    <td><small class="text-muted">{{ $req->created_at->format('d M Y') }}</small></td>
                    <td>{!! $req->statusBadge() !!}</td>
                    <td style="max-width:160px"><small class="text-muted">{{ $req->admin_note ?: '—' }}</small></td>
                    <td>
                        @if($req->status === 'pending')
                        <div class="d-flex gap-1 justify-content-center">
                            <form method="POST" action="{{ route('leave-requests.approve', $req) }}"
                                onsubmit="return confirm('Approve this leave request?')">
                                @csrf
                                <button class="btn btn-sm btn-success" title="Approve">
                                    <i class="bi bi-check-lg me-1"></i>Approve
                                </button>
                            </form>
                            <button class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal" data-bs-target="#rejectModal{{ $req->id }}">
                                <i class="bi bi-x-lg me-1"></i>Reject
                            </button>
                        </div>

                        {{-- Reject modal --}}
                        <div class="modal fade" id="rejectModal{{ $req->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('leave-requests.reject', $req) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Reject Leave Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p class="text-muted small mb-3">
                                                <strong>{{ $req->employee->name }}</strong> — {{ $req->typeLabel() }}<br>
                                                {{ $req->durationLabel() }}
                                                @if($req->reason)<br><em>{{ $req->reason }}</em>@endif
                                            </p>
                                            <label class="form-label fw-semibold">
                                                Reason for rejection <span class="text-danger">*</span>
                                            </label>
                                            <textarea name="admin_note" class="form-control form-control-sm"
                                                rows="3" placeholder="Enter reason..." required></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class="bi bi-x-lg me-1"></i>Reject
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @else
                        <span class="text-muted small d-block text-center">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                        <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                        No leave requests found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($requests->hasPages())
    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">Page {{ $requests->currentPage() }} of {{ $requests->lastPage() }}</small>
        {{ $requests->links() }}
    </div>
    @endif
</div>

{{-- Mobile cards --}}
<div class="d-lg-none">
    @forelse($requests as $req)
    <div class="card mb-2">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="fw-bold" style="font-size:13.5px">{{ $req->employee->name }}</div>
                    <small class="text-muted">{{ $req->employee->employee_id }}</small>
                </div>
                {!! $req->statusBadge() !!}
            </div>
            <div class="d-flex flex-wrap gap-1 mb-2">
                @if($req->type === 'day_off')
                <span class="badge" style="background:#ede9fe;color:#5b21b6">Day Off</span>
                @else
                <span class="badge" style="background:#e0f2fe;color:#0369a1">Time Off</span>
                @endif
                <span class="badge" style="background:#f1f5f9;color:#0f172a">{{ $req->durationLabel() }}</span>
            </div>
            @if($req->reason)
            <small class="text-muted d-block mb-1"><i class="bi bi-chat-left-text me-1"></i>{{ $req->reason }}</small>
            @endif
            @if($req->admin_note)
            <small class="text-muted d-block mb-2"><i class="bi bi-reply me-1"></i>{{ $req->admin_note }}</small>
            @endif
            @if($req->status === 'pending')
            <div class="d-flex gap-1 mt-2">
                <form method="POST" action="{{ route('leave-requests.approve', $req) }}"
                    onsubmit="return confirm('Approve?')" class="flex-fill">
                    @csrf
                    <button class="btn btn-success btn-sm w-100"><i class="bi bi-check-lg me-1"></i>Approve</button>
                </form>
                <button class="btn btn-outline-danger btn-sm"
                    data-bs-toggle="modal" data-bs-target="#rejectModal{{ $req->id }}">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;opacity:.3"></i>
            No leave requests found.
        </div>
    </div>
    @endforelse
    @if($requests->hasPages())
    <div class="mt-3">{{ $requests->links() }}</div>
    @endif
</div>

@endsection
