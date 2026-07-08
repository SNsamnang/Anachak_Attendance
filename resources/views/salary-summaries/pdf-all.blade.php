<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }

    .header { background: #1B5E20; color: #fff; padding: 10px 14px; margin-bottom: 12px; }
    .header h1 { font-size: 15px; margin-bottom: 3px; }
    .header p  { font-size: 10px; opacity: .85; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    thead th {
        background: #2E7D32; color: #fff;
        padding: 5px 6px; text-align: center; font-size: 9px;
    }
    thead th:first-child { text-align: left; }
    tbody tr:nth-child(even) { background: #f9f9f9; }
    tbody td { padding: 5px 6px; border-bottom: 1px solid #e0e0e0; font-size: 9.5px; }
    tfoot td { font-weight: bold; background: #C8E6C9; padding: 6px 6px; border-top: 2px solid #2E7D32; font-size: 10px; }

    .td-center { text-align: center; }
    .td-right  { text-align: right; }
    .text-danger  { color: #c62828; }
    .text-success { color: #2E7D32; }
    .text-primary { color: #1565c0; }

    .summary-box { margin-top: 4px; border: 1px solid #ccc; border-radius: 4px; padding: 8px 12px; }
    .summary-row { display: table; width: 100%; }
    .summary-col { display: table-cell; width: 25%; padding: 4px 6px; }
    .summary-label { font-size: 8px; color: #666; text-transform: uppercase; letter-spacing: .5px; }
    .summary-val   { font-size: 13px; font-weight: bold; margin-top: 2px; }
    .footer { margin-top: 16px; font-size: 9px; color: #999; text-align: right; }
</style>
</head>
<body>

<div class="header">
    <h1>Company Salary Summary</h1>
    <p>Period: {{ $dateFrom }} to {{ $dateTo }} &nbsp;|&nbsp; Generated: {{ now()->format('d M Y H:i') }}</p>
</div>

<table>
    <thead>
        <tr>
            <th style="text-align:left;width:18%">Employee</th>
            <th class="td-right" style="width:10%">Base ($)</th>
            <th class="td-right" style="width:10%">Day Off (-$)</th>
            <th class="td-right" style="width:10%">Leave (-$)</th>
            <th class="td-right" style="width:9%">Late (-$)</th>
            <th class="td-right" style="width:10%">Early (-$)</th>
            <th class="td-right" style="width:9%">OT (+$)</th>
            <th class="td-right" style="width:9%">Bonus (+$)</th>
            <th class="td-right" style="width:11%">Net Pay ($)</th>
        </tr>
    </thead>
    <tbody>
        @php
            $grandBase   = 0; $grandDayoff = 0; $grandLeave = 0;
            $grandLate   = 0; $grandEarly  = 0; $grandOt    = 0;
            $grandBonus  = 0; $grandNet    = 0;
        @endphp
        @foreach($grouped as $group)
        @php
            $base   = (float) ($group['employee']->salary ?? 0);
            $dayoff = (float) $group['total_dayoff_amount'];
            $leave  = (float) $group['total_leave_day_amount'];
            $late   = (float) $group['total_late_amount'];
            $early  = (float) $group['total_early_leave_amount'];
            $ot     = (float) $group['total_ot_amount'];
            $bonus  = (float) $group['total_free_dayoff_bonus'];
            $net    = $base - $dayoff - $leave - $late - $early + $ot + $bonus;
            $grandBase  += $base;  $grandDayoff += $dayoff; $grandLeave += $leave;
            $grandLate  += $late;  $grandEarly  += $early;  $grandOt    += $ot;
            $grandBonus += $bonus; $grandNet    += $net;
        @endphp
        <tr>
            <td>
                <strong>{{ $group['employee']->name }}</strong><br>
                <span style="color:#999;font-size:8.5px">{{ $group['employee']->employee_id }}</span>
            </td>
            <td class="td-right">${{ number_format($base, 2) }}</td>
            <td class="td-right {{ $dayoff > 0 ? 'text-danger' : '' }}">{{ $dayoff > 0 ? '-$'.number_format($dayoff,2) : '—' }}</td>
            <td class="td-right {{ $leave > 0 ? 'text-danger' : '' }}">{{ $leave  > 0 ? '-$'.number_format($leave,2)  : '—' }}</td>
            <td class="td-right {{ $late  > 0 ? 'text-danger' : '' }}">{{ $late   > 0 ? '-$'.number_format($late,2)   : '—' }}</td>
            <td class="td-right {{ $early > 0 ? 'text-danger' : '' }}">{{ $early  > 0 ? '-$'.number_format($early,2)  : '—' }}</td>
            <td class="td-right {{ $ot    > 0 ? 'text-success' : '' }}">{{ $ot    > 0 ? '+$'.number_format($ot,2)    : '—' }}</td>
            <td class="td-right {{ $bonus > 0 ? 'text-success' : '' }}">{{ $bonus > 0 ? '+$'.number_format($bonus,2) : '—' }}</td>
            <td class="td-right {{ $net < $base ? 'text-danger' : 'text-success' }}"><strong>${{ number_format($net, 2) }}</strong></td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td>TOTAL ({{ $grouped->count() }} employees)</td>
            <td class="td-right">${{ number_format($grandBase, 2) }}</td>
            <td class="td-right text-danger">-${{ number_format($grandDayoff, 2) }}</td>
            <td class="td-right text-danger">-${{ number_format($grandLeave, 2) }}</td>
            <td class="td-right text-danger">-${{ number_format($grandLate, 2) }}</td>
            <td class="td-right text-danger">-${{ number_format($grandEarly, 2) }}</td>
            <td class="td-right text-success">+${{ number_format($grandOt, 2) }}</td>
            <td class="td-right text-success">+${{ number_format($grandBonus, 2) }}</td>
            <td class="td-right text-primary"><strong>${{ number_format($grandNet, 2) }}</strong></td>
        </tr>
    </tfoot>
</table>

<div class="summary-box">
    <div class="summary-row">
        <div class="summary-col">
            <div class="summary-label">Total Base Salary</div>
            <div class="summary-val">${{ number_format($grandBase, 2) }}</div>
        </div>
        <div class="summary-col">
            <div class="summary-label">Total Deductions</div>
            <div class="summary-val text-danger">-${{ number_format($grandDayoff + $grandLeave + $grandLate + $grandEarly, 2) }}</div>
        </div>
        <div class="summary-col">
            <div class="summary-label">OT + Bonuses</div>
            <div class="summary-val text-success">+${{ number_format($grandOt + $grandBonus, 2) }}</div>
        </div>
        <div class="summary-col">
            <div class="summary-label">Total to Pay</div>
            <div class="summary-val text-primary">${{ number_format($grandNet, 2) }}</div>
        </div>
    </div>
</div>

<div class="footer">QR Check-In System &nbsp;|&nbsp; Printed: {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
