<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\SalarySummary;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SalarySummaryExport implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    protected Employee $employee;
    protected string   $dateFrom;
    protected string   $dateTo;
    protected $rows;

    public function __construct(Employee $employee, string $dateFrom, string $dateTo)
    {
        $this->employee = $employee;
        $this->dateFrom = $dateFrom;
        $this->dateTo   = $dateTo;
        $this->rows     = SalarySummary::where('employee_id', $employee->id)
            ->whereBetween('date_of_record', [$dateFrom, $dateTo])
            ->orderBy('date_of_record')
            ->get();
    }

    public function title(): string
    {
        return 'Salary Summary';
    }

    public function array(): array
    {
        $rows = $this->rows;
        $emp  = $this->employee;

        $totalDayoffAmount      = $rows->sum('dayoff_amount');
        $totalLateAmount        = $rows->sum('late_amount');
        $totalEarlyLeaveAmount  = $rows->sum('early_leave_amount');
        $totalOtAmount          = $rows->sum('ot_amount');
        $totalUnusedFreeDays    = $rows->sum('unused_free_days');
        $totalFreeDayoffBonus   = $rows->sum('free_dayoff_bonus');
        $baseSalary             = (float) $emp->salary;
        $netSalary              = $baseSalary - $totalDayoffAmount - $totalLateAmount - $totalEarlyLeaveAmount + $totalOtAmount + $totalFreeDayoffBonus;

        $data = [];

        // Title block
        $data[] = ['QR Check-In — Salary Summary'];
        $data[] = ['Employee', $emp->name . ' (' . $emp->employee_id . ')'];
        $data[] = ['Department', $emp->department ?? '—'];
        $data[] = ['Base Monthly Salary', '$' . number_format($baseSalary, 2)];
        $data[] = ['Period', $this->dateFrom . ' to ' . $this->dateTo];
        $data[] = [];

        // Header row (row 7)
        $data[] = [
            'Date',
            'Day Off',
            'Day Off ($)',
            'Late (h)',
            'Late ($)',
            'Early Leave (h)',
            'Early Leave ($)',
            'OT (h)',
            'OT ($)',
        ];

        // Data rows
        foreach ($rows as $row) {
            $data[] = [
                \Carbon\Carbon::parse($row->date_of_record)->format('D, d M Y'),
                $row->dayoff > 0 ? 'Day Off' : '—',
                $row->dayoff_amount > 0 ? '-' . number_format($row->dayoff_amount, 2) : '0.00',
                $row->late > 0 ? number_format($row->late, 2) : '0',
                $row->late_amount > 0 ? '-' . number_format($row->late_amount, 2) : '0.00',
                $row->early_leave > 0 ? number_format($row->early_leave, 2) : '0',
                $row->early_leave_amount > 0 ? '-' . number_format($row->early_leave_amount, 2) : '0.00',
                $row->ot > 0 ? number_format($row->ot, 2) : '0',
                $row->ot_amount > 0 ? '+' . number_format($row->ot_amount, 2) : '0.00',
            ];
        }

        // Totals row
        $data[] = [
            'TOTAL',
            $rows->sum('dayoff'),
            '-' . number_format($totalDayoffAmount, 2),
            number_format($rows->sum('late'), 2) . 'h',
            '-' . number_format($totalLateAmount, 2),
            number_format($rows->sum('early_leave'), 2) . 'h',
            '-' . number_format($totalEarlyLeaveAmount, 2),
            number_format($rows->sum('ot'), 2) . 'h',
            '+' . number_format($totalOtAmount, 2),
        ];

        $data[] = [];

        // Net salary block
        $data[] = ['Base Monthly Salary', '', '', '$' . number_format($baseSalary, 2)];
        if ($totalOtAmount > 0)
            $data[] = ['+ OT Earned', '', '', '+$' . number_format($totalOtAmount, 2)];
        if ($totalFreeDayoffBonus > 0)
            $data[] = ['+ Unused Dayoff (' . $totalUnusedFreeDays . ' day' . ($totalUnusedFreeDays != 1 ? 's' : '') . ')', '', '', '+$' . number_format($totalFreeDayoffBonus, 2)];
        if ($totalLateAmount > 0)
            $data[] = ['− Late Deduction', '', '', '-$' . number_format($totalLateAmount, 2)];
        if ($totalEarlyLeaveAmount > 0)
            $data[] = ['− Early Leave Deduction', '', '', '-$' . number_format($totalEarlyLeaveAmount, 2)];
        if ($totalDayoffAmount > 0)
            $data[] = ['− Day Off Deduction', '', '', '-$' . number_format($totalDayoffAmount, 2)];
        $data[] = ['NET SALARY', '', '', '$' . number_format($netSalary, 2)];

        return $data;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22,
            'B' => 12,
            'C' => 18,
            'D' => 12,
            'E' => 14,
            'F' => 16,
            'G' => 18,
            'H' => 10,
            'I' => 14,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->array());

        // Title — now spans A:I (9 columns)
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B5E20']],
        ]);

        // Info rows (2-5)
        $sheet->getStyle('A2:A5')->applyFromArray(['font' => ['bold' => true]]);

        // Header row (row 7)
        $sheet->getStyle('A7:I7')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E7D32']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Data area borders
        $dataStart = 7;
        $dataEnd   = $dataStart + $this->rows->count() + 1;
        $sheet->getStyle("A{$dataStart}:I{$dataEnd}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        // Totals row
        $totalRow = $dataEnd;
        $sheet->getStyle("A{$totalRow}:I{$totalRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F8E9']],
        ]);

        // Net salary label + value
        $sheet->getStyle("A{$lastRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C8E6C9']],
        ]);
        $sheet->getStyle("D{$lastRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C8E6C9']],
        ]);

        return [];
    }
}
