<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Location;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AttendanceImportTemplate implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new AttendanceImportTemplateSheet(),
            new AttendanceImportReferenceSheet(),
        ];
    }
}

class AttendanceImportTemplateSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string { return 'Import Data'; }

    public function array(): array
    {
        // Get first real employee/location as example
        $emp = Employee::first();
        $loc = Location::first();

        return [
            ['employee_id', 'location', 'type', 'scan_date_time'],
            [
                $emp?->employee_id ?? 'EMP-001',
                $loc?->name ?? 'Your Location Name',
                'in',
                now()->format('Y-m-d') . ' 08:00:00',
            ],
            [
                $emp?->employee_id ?? 'EMP-001',
                $loc?->name ?? 'Your Location Name',
                'out',
                now()->format('Y-m-d') . ' 17:30:00',
            ],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 16, 'B' => 28, 'C' => 10, 'D' => 22];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0f4c81']],
        ]);
        $sheet->getStyle('A2:D3')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF4FF']],
            'font' => ['italic' => true, 'color' => ['rgb' => '555555']],
        ]);
        // Note in E1
        $sheet->setCellValue('F1', '← Fill from row 2 down. See "Reference" sheet for valid names.');
        $sheet->getStyle('F1')->applyFromArray([
            'font' => ['color' => ['rgb' => 'E55100'], 'italic' => true, 'size' => 9],
        ]);
        return [];
    }
}

class AttendanceImportReferenceSheet implements FromArray, WithTitle, WithStyles, WithColumnWidths
{
    public function title(): string { return 'Reference'; }

    public function array(): array
    {
        $rows = [['--- EMPLOYEES ---', '', '--- LOCATIONS ---']];
        $rows[] = ['employee_id', 'name', 'location name'];

        $employees = Employee::orderBy('name')->get();
        $locations = Location::orderBy('name')->get();
        $max = max($employees->count(), $locations->count());

        for ($i = 0; $i < $max; $i++) {
            $emp = $employees[$i] ?? null;
            $loc = $locations[$i] ?? null;
            $rows[] = [
                $emp?->employee_id ?? '',
                $emp?->name ?? '',
                $loc?->name ?? '',
            ];
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return ['A' => 16, 'B' => 24, 'C' => 28];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1B5E20']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
        ]);
        $sheet->getStyle('A2:C2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E7D32']],
        ]);
        return [];
    }
}
