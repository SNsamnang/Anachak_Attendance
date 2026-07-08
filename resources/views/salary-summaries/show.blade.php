@extends('layouts.app')
@section('page-title', 'Salary Summary Detail')
@section('content')

<div class="card mb-3">
    <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <div class="fw-bold fs-5">{{ $employee->name }}</div>
            <small class="text-muted">
                {{ $employee->employee_id }}
                @if($employee->department) · {{ $employee->department }} @endif
                · Salary: ${{ number_format($employee->salary ?? 0, 2) }}
            </small>
        </div>

        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted mb-0">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                    value="{{ $dateFrom }}" style="width:145px">
            </div>
            <div class="d-flex align-items-center gap-1">
                <label class="small text-muted mb-0">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                    value="{{ $dateTo }}" style="width:145px">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Go</button>
            <a href="{{ route('salary-summaries.export.excel', $employee) }}?date_from={{ $dateFrom }}&date_to={{ $dateTo }}"
               class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="{{ route('salary-summaries.export.pdf', $employee) }}?date_from={{ $dateFrom }}&date_to={{ $dateTo }}"
               class="btn btn-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="{{ route('salary-summaries.index') }}?date_from={{ $dateFrom }}&date_to={{ $dateTo }}"
               class="btn btn-outline-secondary btn-sm">← Back</a>
        </form>
    </div>
</div>

{{-- Regenerate for this employee --}}
<form method="POST" action="{{ route('salary-summaries.generate') }}" class="mb-3">
    @csrf
    <input type="hidden" name="employee_id" value="{{ $employee->id }}">
    <input type="hidden" name="date_from" value="{{ $dateFrom }}">
    <input type="hidden" name="date_to" value="{{ $dateTo }}">
    <button type="submit" class="btn btn-success btn-sm">
        <i class="bi bi-arrow-repeat"></i> Update / Generate for this Employee
    </button>
</form>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle" style="font-size:13px">
            <thead class="table-light">
                <tr>
                    <th style="min-width:130px">Date</th>
                    <th class="text-center">Day Off</th>
                    <th class="text-center">Leave Day</th>
                    <th class="text-center">Late</th>
                    <th class="text-center">Early Leave</th>
                    <th class="text-center">OT</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td class="text-muted" style="font-size:12px">{{ $row->date_of_record->format('D, d M Y') }}</td>

                    {{-- Day Off --}}
                    <td class="text-center">
                        @if($row->dayoff > 0)
                            <span class="badge bg-warning text-dark">Day Off</span>
                            <div class="text-danger" style="font-size:11px;margin-top:2px">-${{ number_format($row->dayoff_amount, 2) }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    {{-- Leave Day --}}
                    <td class="text-center">
                        @if($row->leave_day > 0)
                            <span class="badge" style="background:#ede9fe;color:#5b21b6">Leave</span>
                            <div class="text-danger" style="font-size:11px;margin-top:2px">-${{ number_format($row->leave_day_amount, 2) }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    {{-- Late --}}
                    <td class="text-center">
                        @if($row->late > 0)
                            <span class="badge bg-danger">{{ number_format($row->late, 2) }}h</span>
                            <div class="text-danger" style="font-size:11px;margin-top:2px">-${{ number_format($row->late_amount, 2) }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    {{-- Early Leave --}}
                    <td class="text-center">
                        @if($row->early_leave > 0)
                            <span class="badge" style="background:#fd7e14;color:#fff">{{ number_format($row->early_leave, 2) }}h</span>
                            <div class="text-danger" style="font-size:11px;margin-top:2px">-${{ number_format($row->early_leave_amount, 2) }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>

                    {{-- OT --}}
                    <td class="text-center">
                        @if($row->ot > 0)
                            <span class="badge bg-success">{{ number_format($row->ot, 2) }}h</span>
                            <div class="text-success" style="font-size:11px;margin-top:2px">+${{ number_format($row->ot_amount, 2) }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No records for this period.<br>
                        <small>Click "Update / Generate" above to create them.</small>
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($rows->count())
            @php
                $totalDayoff            = $rows->sum('dayoff');
                $totalDayoffAmount      = $rows->sum('dayoff_amount');
                $totalLeaveDay          = $rows->sum('leave_day');
                $totalLeaveDayAmount    = $rows->sum('leave_day_amount');
                $totalLate              = $rows->sum('late');
                $totalLateAmount        = $rows->sum('late_amount');
                $totalEarlyLeave        = $rows->sum('early_leave');
                $totalEarlyLeaveAmount  = $rows->sum('early_leave_amount');
                $totalOt                = $rows->sum('ot');
                $totalOtAmount          = $rows->sum('ot_amount');
                $totalUnusedFreeDays    = $rows->sum('unused_free_days');
                $totalFreeDayoffBonus   = $rows->sum('free_dayoff_bonus');
            @endphp
            <tfoot class="table-light fw-bold">
                <tr>
                    <td>Total</td>
                    <td class="text-center">
                        @if($totalDayoff > 0)
                            {{ $totalDayoff }} day{{ $totalDayoff != 1 ? 's' : '' }}
                            <div class="text-danger fw-normal" style="font-size:11px">-${{ number_format($totalDayoffAmount, 2) }}</div>
                        @else <span class="text-muted fw-normal">—</span> @endif
                    </td>
                    <td class="text-center">
                        @if($totalLeaveDay > 0)
                            {{ $totalLeaveDay }} day{{ $totalLeaveDay != 1 ? 's' : '' }}
                            <div class="text-danger fw-normal" style="font-size:11px">-${{ number_format($totalLeaveDayAmount, 2) }}</div>
                        @else <span class="text-muted fw-normal">—</span> @endif
                    </td>
                    <td class="text-center">
                        @if($totalLate > 0)
                            {{ number_format($totalLate, 2) }}h
                            <div class="text-danger fw-normal" style="font-size:11px">-${{ number_format($totalLateAmount, 2) }}</div>
                        @else <span class="text-muted fw-normal">—</span> @endif
                    </td>
                    <td class="text-center">
                        @if($totalEarlyLeave > 0)
                            {{ number_format($totalEarlyLeave, 2) }}h
                            <div class="text-danger fw-normal" style="font-size:11px">-${{ number_format($totalEarlyLeaveAmount, 2) }}</div>
                        @else <span class="text-muted fw-normal">—</span> @endif
                    </td>
                    <td class="text-center">
                        @if($totalOt > 0)
                            {{ number_format($totalOt, 2) }}h
                            <div class="text-success fw-normal" style="font-size:11px">+${{ number_format($totalOtAmount, 2) }}</div>
                        @else <span class="text-muted fw-normal">—</span> @endif
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

@if($rows->count() && $employee->salary)
@php
    $baseSalary = (float) $employee->salary;
    $netSalary  = $baseSalary - $totalDayoffAmount - $totalLeaveDayAmount - $totalLateAmount - $totalEarlyLeaveAmount + $totalOtAmount + $totalFreeDayoffBonus;
    $netPct     = $baseSalary > 0 ? round(($netSalary / $baseSalary) * 100, 1) : 100;
    $isFull     = $totalDayoffAmount == 0 && $totalLeaveDayAmount == 0 && $totalLateAmount == 0 && $totalEarlyLeaveAmount == 0 && $totalOtAmount == 0 && $totalFreeDayoffBonus == 0;
    $otEligible = optional($employee->otStatus)->eligible ?? true;
@endphp
<div class="card mt-3">
    <div class="card-header fw-semibold">
        Net Salary Calculation
        @if(!$otEligible)
        <span class="badge bg-secondary ms-2">OT Not Eligible</span>
        @endif
    </div>
    <div class="card-body pb-2">
        <div class="row g-0" style="max-width:420px">
            <div class="col-7 py-1 text-muted">Base Monthly Salary</div>
            <div class="col-5 py-1 text-end fw-semibold">${{ number_format($baseSalary, 2) }}</div>

            @if($totalOtAmount > 0)
            <div class="col-7 py-1 text-muted">+ OT Earned ({{ number_format($totalOt, 2) }}h)</div>
            <div class="col-5 py-1 text-end text-success">+${{ number_format($totalOtAmount, 2) }}</div>
            @endif

            @if($totalFreeDayoffBonus > 0)
            @php
                $workStart  = \Carbon\Carbon::parse('1970-01-01 ' . ($employee->work_start ?: '08:00:00'));
                $workEnd    = \Carbon\Carbon::parse('1970-01-01 ' . ($employee->work_end   ?: '17:00:00'));
                $workHrsDay = round($workStart->diffInMinutes($workEnd) / 60, 1);
                $bonusHrs   = $workHrsDay * $totalUnusedFreeDays;
            @endphp
            <div class="col-7 py-1 text-muted">
                + Unused Dayoff ({{ $totalUnusedFreeDays }} day{{ $totalUnusedFreeDays != 1 ? 's' : '' }} × {{ $workHrsDay }}h)
                <small class="d-block" style="font-size:11px;color:#94a3b8">= {{ number_format($bonusHrs, 1) }}h paid bonus</small>
            </div>
            <div class="col-5 py-1 text-end text-success">+${{ number_format($totalFreeDayoffBonus, 2) }}</div>
            @endif

            @if($totalLateAmount > 0)
            <div class="col-7 py-1 text-muted">− Late Deduction ({{ number_format($totalLate, 2) }}h)</div>
            <div class="col-5 py-1 text-end text-danger">-${{ number_format($totalLateAmount, 2) }}</div>
            @endif

            @if($totalEarlyLeaveAmount > 0)
            <div class="col-7 py-1 text-muted">− Early Leave ({{ number_format($totalEarlyLeave, 2) }}h)</div>
            <div class="col-5 py-1 text-end text-danger">-${{ number_format($totalEarlyLeaveAmount, 2) }}</div>
            @endif

            @if($totalDayoffAmount > 0)
            <div class="col-7 py-1 text-muted">− Day Off ({{ $totalDayoff }} day{{ $totalDayoff != 1 ? 's' : '' }})</div>
            <div class="col-5 py-1 text-end text-danger">-${{ number_format($totalDayoffAmount, 2) }}</div>
            @endif

            @if($totalLeaveDayAmount > 0)
            <div class="col-7 py-1 text-muted">− Leave Day ({{ $totalLeaveDay }} day{{ $totalLeaveDay != 1 ? 's' : '' }})</div>
            <div class="col-5 py-1 text-end text-danger">-${{ number_format($totalLeaveDayAmount, 2) }}</div>
            @endif

            <div class="col-12"><hr class="my-1"></div>

            <div class="col-7 py-1 fw-bold fs-5">Net Salary</div>
            <div class="col-5 py-1 text-end fw-bold fs-5 {{ $netSalary >= $baseSalary ? 'text-success' : ($isFull ? '' : 'text-danger') }}">
                ${{ number_format($netSalary, 2) }}
            </div>
        </div>

        @if($isFull)
        <div class="mt-2">
            <span class="badge bg-success fs-6 px-3 py-2">100% Full Salary</span>
        </div>
        @else
        <div class="mt-2 text-muted small">
            {{ $netPct }}% of base salary
        </div>
        @endif
    </div>
</div>
@endif

@endsection
