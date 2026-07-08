<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AttendanceExport implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function __construct(protected array $filters = []) {}

    public function title(): string
    {
        return 'Attendance Log';
    }

    public function array(): array
    {
        $query = Attendance::with(['employee', 'location'])->orderByDesc('scanned_at');

        if (!empty($this->filters['date'])) {
            $query->whereDate('scanned_at', $this->filters['date']);
        } elseif (!empty($this->filters['date_from']) || !empty($this->filters['date_to'])) {
            if (!empty($this->filters['date_from'])) {
                $query->whereDate('scanned_at', '>=', $this->filters['date_from']);
            }
            if (!empty($this->filters['date_to'])) {
                $query->whereDate('scanned_at', '<=', $this->filters['date_to']);
            }
        }
        if (!empty($this->filters['employee_id'])) {
            $query->where('employee_id', $this->filters['employee_id']);
        }
        if (!empty($this->filters['location_id'])) {
            $query->where('location_id', $this->filters['location_id']);
        }
        if (!empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }

        $rows = $query->get();

        $data = [];

        // Header
        $data[] = [
            'Employee ID',
            'Employee Name',
            'Location',
            'Type',
            'Scan Date Time',
            'Check-In Status',
            'Check-Out Status',
            'OT (h)',
            'Distance (m)',
            'GPS Verified',
            'Source',
        ];

        foreach ($rows as $row) {
            $data[] = [
                $row->employee->employee_id ?? '',
                $row->employee->name ?? '',
                $row->location->name ?? '',
                $row->type,
                $row->scanned_at->format('Y-m-d H:i:s'),
                $row->check_in_status ?? '—',
                $row->check_out_status ?? '—',
                $row->ot_seconds > 0 ? round($row->ot_seconds / 3600, 2) : '0',
                $row->distance_meters ?? '—',
                $row->location_verified ? 'Yes' : 'No',
                $row->ip_address === 'admin' ? 'Manual' : 'QR Scan',
            ];
        }

        return $data;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 14, 'B' => 22, 'C' => 20, 'D' => 10,
            'E' => 22, 'F' => 16, 'G' => 16, 'H' => 10,
            'I' => 14, 'J' => 14, 'K' => 12,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0f4c81']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle("A1:K{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['rgb' => 'DDDDDD'],
                    ],
                ],
            ]);
        }

        return [];
    }
}
