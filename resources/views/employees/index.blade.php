@extends('layouts.app')
@section('page-title', 'Employees')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h6 class="fw-bold mb-0">All Employees</h6>
        <small class="text-muted">{{ $employees->count() }} total</small>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->is_super_admin)
            @if($companies->isNotEmpty())
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-qr-code me-1"></i> QR Codes
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('get-qr.index') }}">All Companies</a></li>
                    <li><hr class="dropdown-divider"></li>
                    @foreach($companies as $c)
                    <li><a class="dropdown-item" href="{{ route('get-qr.show', $c->code) }}">{{ $c->name }}</a></li>
                    @endforeach
                </ul>
            </div>
            @else
            <a href="{{ route('get-qr.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-qr-code me-1"></i> QR Codes
            </a>
            @endif
        @else
            @php $code = auth()->user()->company?->code @endphp
            @if($code)
            <a href="{{ route('get-qr.show', $code) }}" class="btn btn-outline-secondary btn-sm" target="_blank">
                <i class="bi bi-qr-code me-1"></i> QR Codes
            </a>
            @endif
        @endif
        <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Add Employee
        </a>
    </div>
</div>

{{-- Desktop table --}}
<div class="card d-none d-md-block">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Employee ID</th>
                    <th>Department</th>
                    <th>Salary</th>
                    <th>Work Time</th>
                    <th class="text-center">Scans</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($employees as $emp)
                <tr>
                    <td class="text-muted">{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $emp->name }}</td>
                    <td><code>{{ $emp->employee_id }}</code></td>
                    <td>{{ $emp->department ?? '—' }}</td>
                    <td class="fw-semibold text-success">${{ number_format($emp->salary ?? 0, 2) }}</td>
                    <td>
                        <span class="badge" style="background:#f1f5f9;color:#475569">
                            {{ $emp->workStartFormatted() }} – {{ $emp->workEndFormatted() }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge" style="background:#e0f2fe;color:#0369a1">{{ $emp->attendances_count }}</span>
                    </td>
                    <td>
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="{{ route('employees.qr', $emp) }}" class="btn btn-sm btn-outline-secondary" title="QR Code"><i class="bi bi-qr-code"></i></a>
                            <a href="{{ route('employees.download-qr', $emp) }}" class="btn btn-sm btn-outline-secondary" title="Download QR"><i class="bi bi-download"></i></a>
                            <a href="{{ route('employees.edit', $emp) }}" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                            <a href="{{ route('employees.set-password', $emp) }}" class="btn btn-sm btn-outline-dark" title="Set Password"><i class="bi bi-key"></i></a>
                            <form method="POST" action="{{ route('employees.destroy', $emp) }}"
                                onsubmit="return confirm('Delete {{ addslashes($emp->name) }}? This also deletes all attendance records.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <i class="bi bi-people d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                        No employees yet. <a href="{{ route('employees.create') }}">Add one now →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Mobile cards --}}
<div class="d-md-none">
    @forelse($employees as $emp)
    <div class="card mb-2">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <div class="fw-bold">{{ $emp->name }}</div>
                    <code class="small">{{ $emp->employee_id }}</code>
                </div>
                <span class="fw-semibold text-success">${{ number_format($emp->salary ?? 0, 2) }}</span>
            </div>
            <div class="d-flex flex-wrap gap-1 mb-3">
                @if($emp->department)
                <span class="badge" style="background:#f1f5f9;color:#475569">{{ $emp->department }}</span>
                @endif
                <span class="badge" style="background:#f1f5f9;color:#475569">
                    <i class="bi bi-clock me-1"></i>{{ $emp->workStartFormatted() }}–{{ $emp->workEndFormatted() }}
                </span>
                <span class="badge" style="background:#e0f2fe;color:#0369a1">{{ $emp->attendances_count }} scans</span>
            </div>
            <div class="d-flex gap-1 flex-wrap">
                <a href="{{ route('employees.qr', $emp) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-qr-code me-1"></i>QR</a>
                <a href="{{ route('employees.download-qr', $emp) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i>DL</a>
                <a href="{{ route('employees.edit', $emp) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil me-1"></i>Edit</a>
                <a href="{{ route('employees.set-password', $emp) }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-key me-1"></i>PW</a>
                <form method="POST" action="{{ route('employees.destroy', $emp) }}"
                    onsubmit="return confirm('Delete {{ addslashes($emp->name) }}?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash me-1"></i>Del</button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-people d-block mb-2" style="font-size:2rem;opacity:.3"></i>
            No employees yet. <a href="{{ route('employees.create') }}">Add one →</a>
        </div>
    </div>
    @endforelse
</div>

@endsection
