<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }

    .header { background: #1B5E20; color: #fff; padding: 12px 16px; margin-bottom: 14px; }
    .header h1 { font-size: 16px; margin-bottom: 4px; }
    .header p  { font-size: 11px; opacity: .85; }

    .info-grid { display: table; width: 100%; margin-bottom: 14px; }
    .info-row  { display: table-row; }
    .info-label, .info-value { display: table-cell; padding: 3px 6px; }
    .info-label { font-weight: bold; width: 140px; color: #555; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    thead th {
        background: #2E7D32; color: #fff;
        padding: 6px 8px; text-align: center; font-size: 10px;
    }
    tbody tr:nth-child(even) { background: #f9f9f9; }
    tbody td { padding: 5px 8px; border-bottom: 1px solid #e0e0e0; }
    tfoot td { font-weight: bold; background: #F1F8E9; padding: 6px 8px; border-top: 2px solid #2E7D32; }

    .td-center { text-align: center; }
    .td-right  { text-align: right; }
    .text-danger  { color: #c62828; }
    .text-success { color: #2E7D32; }
    .text-muted   { color: #999; }
    .badge-dayoff { background: #FFA000; color: #fff; padding: 1px 6px; border-radius: 3px; font-size: 9px; }

    .net-box { border: 1.5px solid #2E7D32; border-radius: 4px; padding: 10px 14px; margin-top: 4px; }
    .net-row { display: table; width: 260px; }
    .net-cell { display: table-cell; padding: 3px 0; }
    .net-cell.right { text-align: right; width: 110px; }
    .net-label { font-size: 11px; color: #444; }
    .net-divider { border: none; border-top: 1px solid #ccc; margin: 5px 0; }
    .net-total { font-size: 13px; font-weight: bold; color: #1B5E20; }
    .badge-full { background: #2E7D32; color: #fff; padding: 3px 10px; border-radius: 4px; font-size: 11px; display: inline-block; margin-top: 6px; }
    .pct-note   { font-size: 10px; color: #777; margin-top: 4px; }

    .footer { margin-top: 20px; font-size: 9px; color: #aaa; text-align: right; }
</style>
</head>
<body>

<div class="header">
    <h1>Salary Summary — {{ $employee->name }}</h1>
    <p>{{ $employee->employee_id }}{{ $employee->department ? ' · ' . $employee->department : '' }} · Period: {{ $dateFrom }} to {{ $dateTo }}</p>
</div>

<div class="info-grid">
    <div class="info-row">
        <div class="info-label">Base Monthly Salary</div>
        <div class="info-value">${{ number_format($employee->salary ?? 0, 2) }}</div>
    </div>
    <div class="info-row">
        <div class="info-label">OT Eligibility</div>
        <div class="info-value">{{ $otEligible ? 'Eligible' : 'Not Eligible' }}</div>
    </div>
    <div class="info-row">
        <div class="info-label">Generated on</div>
        <div class="info-value">{{ now()->format('d M Y, H:i') }}</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="text-align:left">Date</th>
            <th>Day Off</th>
            <th>Day Off ($)</th>
            <th>Leave Day</th>
            <th>Leave Day ($)</th>
            <th>Late (h)</th>
            <th>Late ($)</th>
            <th>Early Leave (h)</th>
            <th>Early Leave ($)</th>
            <th>OT (h)</th>
            <th>OT ($)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
        <tr>
            <td>{{ \Carbon\Carbon::parse($row->date_of_record)->format('D, d M Y') }}</td>
            <td class="td-center">
                @if($row->dayoff > 0)
                    <span class="badge-dayoff">Day Off</span>
                @else
                    <span class="text-muted">—</span>
                @endif
            </td>
            <td class="td-right {{ $row->dayoff_amount > 0 ? 'text-danger' : 'text-muted' }}">
                {{ $row->dayoff_amount > 0 ? '-$'.number_format($row->dayoff_amount,2) : '$0.00' }}
            </td>
            <td class="td-center">
                @if($row->leave_day > 0)
                    <span style="background:#7c3aed;color:#fff;padding:1px 6px;border-radius:3px;font-size:9px">Leave</span>
                @else
                    <span class="text-muted">—</span>
                @endif
            </td>
            <td class="td-right {{ $row->leave_day_amount > 0 ? 'text-danger' : 'text-muted' }}">
                {{ $row->leave_day_amount > 0 ? '-$'.number_format($row->leave_day_amount,2) : '—' }}
            </td>
            <td class="td-center {{ $row->late > 0 ? 'text-danger' : 'text-muted' }}">
                {{ $row->late > 0 ? number_format($row->late,2).'h' : '0' }}
            </td>
            <td class="td-right {{ $row->late_amount > 0 ? 'text-danger' : 'text-muted' }}">
                {{ $row->late_amount > 0 ? '-$'.number_format($row->late_amount,2) : '$0.00' }}
            </td>
            <td class="td-center {{ $row->early_leave > 0 ? 'text-danger' : 'text-muted' }}">
                {{ $row->early_leave > 0 ? number_format($row->early_leave,2).'h' : '0' }}
            </td>
            <td class="td-right {{ $row->early_leave_amount > 0 ? 'text-danger' : 'text-muted' }}">
                {{ $row->early_leave_amount > 0 ? '-$'.number_format($row->early_leave_amount,2) : '$0.00' }}
            </td>
            <td class="td-center {{ $row->ot > 0 ? 'text-success' : 'text-muted' }}">
                {{ $row->ot > 0 ? number_format($row->ot,2).'h' : '0' }}
            </td>
            <td class="td-right {{ $row->ot_amount > 0 ? 'text-success' : 'text-muted' }}">
                {{ $row->ot_amount > 0 ? '+$'.number_format($row->ot_amount,2) : '$0.00' }}
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td>Total</td>
            <td class="td-center">{{ $rows->sum('dayoff') }}</td>
            <td class="td-right text-danger">-${{ number_format($rows->sum('dayoff_amount'),2) }}</td>
            <td class="td-center">{{ $rows->sum('leave_day') ?: '—' }}</td>
            <td class="td-right text-danger">{{ $rows->sum('leave_day_amount') > 0 ? '-$'.number_format($rows->sum('leave_day_amount'),2) : '—' }}</td>
            <td class="td-center">{{ number_format($rows->sum('late'),2) }}h</td>
            <td class="td-right text-danger">-${{ number_format($rows->sum('late_amount'),2) }}</td>
            <td class="td-center">{{ number_format($rows->sum('early_leave'),2) }}h</td>
            <td class="td-right text-danger">-${{ number_format($rows->sum('early_leave_amount'),2) }}</td>
            <td class="td-center">{{ number_format($rows->sum('ot'),2) }}h</td>
            <td class="td-right text-success">+${{ number_format($rows->sum('ot_amount'),2) }}</td>
        </tr>
    </tfoot>
</table>

@php
    $baseSalary             = (float) $employee->salary;
    $totalDayoffAmount      = $rows->sum('dayoff_amount');
    $totalLeaveDayAmount    = $rows->sum('leave_day_amount');
    $totalLateAmount        = $rows->sum('late_amount');
    $totalEarlyLeaveAmount  = $rows->sum('early_leave_amount');
    $totalOtAmount          = $rows->sum('ot_amount');
    $totalUnusedFreeDays    = $rows->sum('unused_free_days');
    $totalFreeDayoffBonus   = $rows->sum('free_dayoff_bonus');
    $netSalary              = $baseSalary - $totalDayoffAmount - $totalLeaveDayAmount - $totalLateAmount - $totalEarlyLeaveAmount + $totalOtAmount + $totalFreeDayoffBonus;
    $isFull                 = $totalDayoffAmount == 0 && $totalLeaveDayAmount == 0 && $totalLateAmount == 0 && $totalEarlyLeaveAmount == 0 && $totalOtAmount == 0 && $totalFreeDayoffBonus == 0;
    $netPct                 = $baseSalary > 0 ? round(($netSalary / $baseSalary) * 100, 1) : 100;
@endphp

<div class="net-box">
    <div class="net-row">
        <div class="net-cell net-label">Base Monthly Salary</div>
        <div class="net-cell right">${{ number_format($baseSalary, 2) }}</div>
    </div>
    @if($totalOtAmount > 0)
    <div class="net-row">
        <div class="net-cell net-label text-success">+ OT Earned</div>
        <div class="net-cell right text-success">+${{ number_format($totalOtAmount, 2) }}</div>
    </div>
    @endif
    @if($totalFreeDayoffBonus > 0)
    <div class="net-row">
        <div class="net-cell net-label text-success">+ Unused Dayoff ({{ $totalUnusedFreeDays }} day{{ $totalUnusedFreeDays != 1 ? 's' : '' }})</div>
        <div class="net-cell right text-success">+${{ number_format($totalFreeDayoffBonus, 2) }}</div>
    </div>
    @endif
    @if($totalLateAmount > 0)
    <div class="net-row">
        <div class="net-cell net-label text-danger">− Late Deduction</div>
        <div class="net-cell right text-danger">-${{ number_format($totalLateAmount, 2) }}</div>
    </div>
    @endif
    @if($totalEarlyLeaveAmount > 0)
    <div class="net-row">
        <div class="net-cell net-label text-danger">− Early Leave</div>
        <div class="net-cell right text-danger">-${{ number_format($totalEarlyLeaveAmount, 2) }}</div>
    </div>
    @endif
    @if($totalDayoffAmount > 0)
    <div class="net-row">
        <div class="net-cell net-label text-danger">− Day Off</div>
        <div class="net-cell right text-danger">-${{ number_format($totalDayoffAmount, 2) }}</div>
    </div>
    @endif
    @if($totalLeaveDayAmount > 0)
    <div class="net-row">
        <div class="net-cell net-label text-danger">− Leave Day</div>
        <div class="net-cell right text-danger">-${{ number_format($totalLeaveDayAmount, 2) }}</div>
    </div>
    @endif
    <hr class="net-divider">
    <div class="net-row">
        <div class="net-cell net-total">Net Salary</div>
        <div class="net-cell right net-total">${{ number_format($netSalary, 2) }}</div>
    </div>
    @if($isFull)
        <div><span class="badge-full">100% Full Salary</span></div>
    @else
        <div class="pct-note">{{ $netPct }}% of base salary</div>
    @endif
</div>

<div class="footer">QR Check-In System · {{ now()->format('d M Y H:i') }}</div>

</body>
</html>
