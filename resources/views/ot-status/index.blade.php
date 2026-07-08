@extends('layouts.app')
@section('page-title', 'OT Eligibility')
@section('content')

<div class="card">
    <div class="card-header p-3 d-flex justify-content-between align-items-center">
        <span>⏱️ OT Eligibility</span>
        <span class="text-muted small">{{ $employees->count() }} employees</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Work Hours</th>
                        <th class="text-center">OT Eligible</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td class="text-muted small">{{ $loop->iteration }}</td>
                        <td>
                            <div class="fw-semibold">{{ $employee->name }}</div>
                            <small class="text-muted">{{ $employee->employee_id }}</small>
                        </td>
                        <td><small class="text-muted">{{ $employee->department ?? '—' }}</small></td>
                        <td>
                            <small class="text-muted">
                                ⏱ {{ $employee->workStartFormatted() }} – {{ $employee->workEndFormatted() }}
                            </small>
                        </td>
                        <td class="text-center">
                            @if($employee->ot_eligible)
                            <span class="badge bg-success">Eligible</span>
                            @else
                            <span class="badge bg-secondary">Not Eligible</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <form method="POST" action="{{ route('ot-status.toggle', $employee) }}">
                                @csrf
                                @if($employee->ot_eligible)
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    Disable OT
                                </button>
                                @else
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    Enable OT
                                </button>
                                @endif
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No employees found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-info mt-3 small">
    <strong>How OT eligibility works:</strong> Employees marked
    <span class="badge bg-secondary">Not Eligible</span> will never receive OT pay,
    even if they check out late. Employees marked
    <span class="badge bg-success">Eligible</span> earn OT for any day where they
    check out more than 1 hour.
</div>

@endsection