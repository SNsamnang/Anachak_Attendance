@extends('layouts.app')
@section('page-title', 'Salary Summary')
@section('content')

{{-- Filter form --}}
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end mb-2">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                    value="{{ $dateFrom }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                    value="{{ $dateTo }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Employee</label>
                <select name="employee_id" class="form-select form-select-sm">
                    <option value="">All employees</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" @selected(request('employee_id')==$emp->id)>
                        {{ $emp->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">Filter</button>
                <a href="{{ route('salary-summaries.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>

        <hr class="my-2">

        {{-- Hidden POST form submitted by modal --}}
        <form id="generateForm" method="POST" action="{{ route('salary-summaries.generate') }}">
            @csrf
            <input type="hidden" name="date_from"    id="gen_date_from">
            <input type="hidden" name="date_to"      id="gen_date_to">
            <input type="hidden" name="employee_id"  id="gen_employee_id">
            <input type="hidden" name="free_dayoff"  id="gen_free_dayoff" value="0">
        </form>

        {{-- Visible generate row (reads filter values, opens modal) --}}
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">From</label>
                <input type="date" id="ui_date_from" class="form-control form-control-sm"
                    value="{{ $dateFrom }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">To</label>
                <input type="date" id="ui_date_to" class="form-control form-control-sm"
                    value="{{ $dateTo }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Employee</label>
                <select id="ui_employee_id" class="form-select form-select-sm">
                    <option value="">All employees</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-success btn-sm w-100" data-bs-toggle="modal" data-bs-target="#generateModal">
                    <i class="bi bi-arrow-repeat"></i> Update / Generate from Attendance
                </button>
            </div>
        </div>
        <small class="text-muted d-block mt-1">
            Recalculates day off, late, and OT for each day in the selected range based on attendance records.
        </small>
    </div>
</div>

{{-- Generate Confirmation Modal --}}
<div class="modal fade" id="generateModal" tabindex="-1" aria-labelledby="generateModalLabel" aria-hidden="true"
    data-employees="{{ json_encode($employees->pluck('name', 'id')->put('', 'All employees')) }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generateModalLabel">
                    <i class="bi bi-arrow-repeat me-1"></i> Generate Salary Summary
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 p-3 bg-light rounded small">
                    <div class="row">
                        <div class="col-6"><span class="text-muted">Period:</span></div>
                        <div class="col-6 fw-semibold" id="modal_period">—</div>
                        <div class="col-6 mt-1"><span class="text-muted">Employee:</span></div>
                        <div class="col-6 mt-1 fw-semibold" id="modal_employee">All</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        Free Dayoff Days
                        <span class="text-muted fw-normal">(paid leave — no salary deduction)</span>
                    </label>
                    <input type="number" id="modal_free_dayoff" class="form-control"
                        min="0" max="31" value="0" placeholder="0">
                    <div class="form-text">
                        Number of absent days in this period that are <strong>paid</strong>.
                        The first <em>N</em> dayoff days per employee will not deduct salary.
                    </div>
                </div>

                <div class="alert alert-warning mb-0 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Old records for this period will be <strong>deleted and regenerated</strong>.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmGenerate">
                    <i class="bi bi-check-lg me-1"></i> Confirm & Generate
                </button>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($grouped->isNotEmpty())
<div class="d-flex justify-content-end gap-2 mb-2">
    <a href="{{ route('salary-summaries.export-all.excel') }}?date_from={{ $dateFrom }}&date_to={{ $dateTo }}"
       class="btn btn-success btn-sm">
        <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
    </a>
    <a href="{{ route('salary-summaries.export-all.pdf') }}?date_from={{ $dateFrom }}&date_to={{ $dateTo }}"
       class="btn btn-danger btn-sm">
        <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
    </a>
</div>
@endif

{{-- Per-employee totals --}}
@php
    $grandBaseSalary   = 0;
    $grandDeductions   = 0;
    $grandOtBonus      = 0;
    $grandNet          = 0;
@endphp
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle" style="font-size:13px">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th class="text-center">Day Off</th>
                    <th class="text-center">Leave Day</th>
                    <th class="text-center">Late</th>
                    <th class="text-center">Early Leave</th>
                    <th class="text-center">OT</th>
                    <th class="text-center">Base Salary</th>
                    <th class="text-center">Net Pay</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($grouped as $group)
                @php
                    $baseSalary  = (float) ($group['employee']->salary ?? 0);
                    $deductions  = $group['total_dayoff_amount'] + $group['total_leave_day_amount']
                                 + $group['total_late_amount'] + $group['total_early_leave_amount'];
                    $otBonus     = $group['total_ot_amount'] + $group['total_free_dayoff_bonus'];
                    $netPay      = $baseSalary - $deductions + $otBonus;
                    $grandBaseSalary += $baseSalary;
                    $grandDeductions += $deductions;
                    $grandOtBonus    += $otBonus;
                    $grandNet        += $netPay;
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold" style="font-size:.9rem">{{ $group['employee']->name }}</div>
                        <small class="text-muted">{{ $group['employee']->employee_id }}</small>
                    </td>

                    {{-- Day Off --}}
                    <td class="text-center">
                        @if($group['total_dayoff'] > 0)
                            <div>{{ $group['total_dayoff'] }} day{{ $group['total_dayoff'] != 1 ? 's' : '' }}</div>
                            <div class="text-danger" style="font-size:11px">-${{ number_format($group['total_dayoff_amount'], 2) }}</div>
                            @if($group['total_unused_free_days'] > 0)
                            <div style="font-size:11px;color:#10b981">+{{ $group['total_unused_free_days'] }} unused</div>
                            @endif
                            @if($group['total_free_dayoff_bonus'] > 0)
                            <div style="font-size:11px;color:#10b981">+${{ number_format($group['total_free_dayoff_bonus'], 2) }}</div>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    {{-- Leave Day --}}
                    <td class="text-center">
                        @if($group['total_leave_day'] > 0)
                            <div>{{ $group['total_leave_day'] }} day{{ $group['total_leave_day'] != 1 ? 's' : '' }}</div>
                            <div class="text-danger" style="font-size:11px">-${{ number_format($group['total_leave_day_amount'], 2) }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    {{-- Late --}}
                    <td class="text-center">
                        @if($group['total_late'] > 0)
                            <div>{{ number_format($group['total_late'], 2) }}h</div>
                            <div class="text-danger" style="font-size:11px">-${{ number_format($group['total_late_amount'], 2) }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    {{-- Early Leave --}}
                    <td class="text-center">
                        @if($group['total_early_leave'] > 0)
                            <div>{{ number_format($group['total_early_leave'], 2) }}h</div>
                            <div class="text-danger" style="font-size:11px">-${{ number_format($group['total_early_leave_amount'], 2) }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    {{-- OT --}}
                    <td class="text-center">
                        @if($group['total_ot'] > 0)
                            <div>{{ number_format($group['total_ot'], 2) }}h</div>
                            <div class="text-success" style="font-size:11px">+${{ number_format($group['total_ot_amount'], 2) }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    {{-- Base Salary --}}
                    <td class="text-center fw-semibold">${{ number_format($baseSalary, 2) }}</td>

                    {{-- Net Pay --}}
                    <td class="text-center fw-bold {{ $netPay >= $baseSalary ? 'text-success' : 'text-danger' }}">
                        ${{ number_format($netPay, 2) }}
                    </td>

                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="{{ route('salary-summaries.show', $group['employee']) }}?date_from={{ $dateFrom }}&date_to={{ $dateTo }}"
                                class="btn btn-sm btn-outline-primary">
                                View Daily
                            </a>
                            <form method="POST"
                                action="{{ route('salary-summaries.destroy', $group['employee']) }}?date_from={{ $dateFrom }}&date_to={{ $dateTo }}"
                                data-name="{{ $group['employee']->name }}"
                                onsubmit="return confirm('Delete all salary records for ' + this.dataset.name + ' in this period?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        No salary summary records for this period.<br>
                        <small>Click "Update / Generate from Attendance" above to create them.</small>
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($grouped->isNotEmpty())
            <tfoot class="table-dark">
                <tr>
                    <td class="fw-bold">Total ({{ $grouped->count() }} employees)</td>
                    <td class="text-center text-danger fw-semibold">-${{ number_format($grouped->sum('total_dayoff_amount'), 2) }}</td>
                    <td class="text-center text-danger fw-semibold">-${{ number_format($grouped->sum('total_leave_day_amount'), 2) }}</td>
                    <td class="text-center text-danger fw-semibold">-${{ number_format($grouped->sum('total_late_amount'), 2) }}</td>
                    <td class="text-center text-danger fw-semibold">-${{ number_format($grouped->sum('total_early_leave_amount'), 2) }}</td>
                    <td class="text-center text-success fw-semibold">+${{ number_format($grandOtBonus, 2) }}</td>
                    <td class="text-center fw-semibold">${{ number_format($grandBaseSalary, 2) }}</td>
                    <td class="text-center fw-bold fs-6">${{ number_format($grandNet, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@if($grouped->isNotEmpty())
<div class="card mt-3">
    <div class="card-body">
        <div class="fw-semibold mb-3" style="font-size:13px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px">
            <i class="bi bi-calculator me-1"></i> Company Payment Summary — {{ $dateFrom }} to {{ $dateTo }}
        </div>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="p-3 rounded" style="background:#f8fafc;border:1px solid var(--border)">
                    <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px">Total Base Salary</div>
                    <div class="fw-bold mt-1" style="font-size:1.2rem">${{ number_format($grandBaseSalary, 2) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 rounded" style="background:#fff5f5;border:1px solid #fed7d7">
                    <div class="text-danger" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px">Total Deductions</div>
                    <div class="fw-bold mt-1 text-danger" style="font-size:1.2rem">-${{ number_format($grandDeductions, 2) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 rounded" style="background:#f0fff4;border:1px solid #c6f6d5">
                    <div class="text-success" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px">OT + Bonuses</div>
                    <div class="fw-bold mt-1 text-success" style="font-size:1.2rem">+${{ number_format($grandOtBonus, 2) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 rounded" style="background:#ebf8ff;border:1px solid #bee3f8">
                    <div class="text-primary" style="font-size:11px;text-transform:uppercase;letter-spacing:.5px">Total to Pay</div>
                    <div class="fw-bold mt-1 text-primary" style="font-size:1.4rem">${{ number_format($grandNet, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
const modal = document.getElementById('generateModal');
const employeeNames = JSON.parse(modal.dataset.employees);

modal.addEventListener('show.bs.modal', function () {
    const from = document.getElementById('ui_date_from').value;
    const to   = document.getElementById('ui_date_to').value;
    const empId = document.getElementById('ui_employee_id').value;

    document.getElementById('modal_period').textContent   = from + ' → ' + to;
    document.getElementById('modal_employee').textContent = employeeNames[empId] || 'All employees';
    document.getElementById('modal_free_dayoff').value    = 0;
});

document.getElementById('confirmGenerate').addEventListener('click', function () {
    const from  = document.getElementById('ui_date_from').value;
    const to    = document.getElementById('ui_date_to').value;
    const empId = document.getElementById('ui_employee_id').value;
    const free  = parseInt(document.getElementById('modal_free_dayoff').value) || 0;

    if (!from || !to) {
        alert('Please fill in the date range first.');
        return;
    }

    document.getElementById('gen_date_from').value   = from;
    document.getElementById('gen_date_to').value     = to;
    document.getElementById('gen_employee_id').value = empId;
    document.getElementById('gen_free_dayoff').value  = free;

    document.getElementById('generateForm').submit();
});
</script>
@endpush

@endsection
