<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SalarySummaryAllExport implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    protected Collection $grouped;
    protected string     $dateFrom;
    protected string     $dateTo;

    public function __construct(Collection $grouped, string $dateFrom, string $dateTo)
    {
        $this->grouped  = $grouped;
        $this->dateFrom = $dateFrom;
        $this->dateTo   = $dateTo;
    }

    public function title(): string
    {
        return 'Salary Summary';
    }

    public function array(): array
    {
        $data = [];

        $data[] = ['QR Check-In — Company Salary Summary'];
        $data[] = ['Period', $this->dateFrom . ' to ' . $this->dateTo];
        $data[] = ['Generated', now()->format('d M Y H:i')];
        $data[] = [];

        $data[] = [
            'Employee',
            'ID',
            'Base Salary ($)',
            'Day Off (-$)',
            'Leave Day (-$)',
            'Late (-$)',
            'Early Leave (-$)',
            'OT (+$)',
            'Bonus (+$)',
            'Net Pay ($)',
        ];

        $totalBase       = 0;
        $totalDayoff     = 0;
        $totalLeave      = 0;
        $totalLate       = 0;
        $totalEarly      = 0;
        $totalOt         = 0;
        $totalBonus      = 0;
        $totalNet        = 0;

        foreach ($this->grouped as $group) {
            $base    = (float) ($group['employee']->salary ?? 0);
            $dayoff  = (float) $group['total_dayoff_amount'];
            $leave   = (float) $group['total_leave_day_amount'];
            $late    = (float) $group['total_late_amount'];
            $early   = (float) $group['total_early_leave_amount'];
            $ot      = (float) $group['total_ot_amount'];
            $bonus   = (float) $group['total_free_dayoff_bonus'];
            $net     = $base - $dayoff - $leave - $late - $early + $ot + $bonus;

            $data[] = [
                $group['employee']->name,
                $group['employee']->employee_id,
                number_format($base, 2),
                $dayoff > 0 ? '-' . number_format($dayoff, 2) : '0.00',
                $leave  > 0 ? '-' . number_format($leave, 2)  : '0.00',
                $late   > 0 ? '-' . number_format($late, 2)   : '0.00',
                $early  > 0 ? '-' . number_format($early, 2)  : '0.00',
                $ot     > 0 ? '+' . number_format($ot, 2)     : '0.00',
                $bonus  > 0 ? '+' . number_format($bonus, 2)  : '0.00',
                number_format($net, 2),
            ];

            $totalBase   += $base;
            $totalDayoff += $dayoff;
            $totalLeave  += $leave;
            $totalLate   += $late;
            $totalEarly  += $early;
            $totalOt     += $ot;
            $totalBonus  += $bonus;
            $totalNet    += $net;
        }

        $data[] = [
            'TOTAL (' . $this->grouped->count() . ' employees)',
            '',
            number_format($totalBase, 2),
            '-' . number_format($totalDayoff, 2),
            '-' . number_format($totalLeave, 2),
            '-' . number_format($totalLate, 2),
            '-' . number_format($totalEarly, 2),
            '+' . number_format($totalOt, 2),
            '+' . number_format($totalBonus, 2),
            number_format($totalNet, 2),
        ];

        return $data;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 24, 'B' => 10, 'C' => 16,
            'D' => 14, 'E' => 14, 'F' => 12,
            'G' => 16, 'H' => 12, 'I' => 12, 'J' => 14,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $rowCount = count($this->array());
        $dataRows = $this->grouped->count();

        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B5E20']],
        ]);

        $sheet->getStyle('A2:A3')->applyFromArray(['font' => ['bold' => true]]);

        // Header row (row 5)
        $sheet->getStyle('A5:J5')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E7D32']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Data area borders
        $sheet->getStyle('A5:J' . $rowCount)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        // Totals row (last row)
        $sheet->getStyle('A' . $rowCount . ':J' . $rowCount)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C8E6C9']],
        ]);

        return [];
    }
}
