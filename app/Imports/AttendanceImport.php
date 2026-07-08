<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class AttendanceImport implements ToCollection, WithHeadingRow
{
    public array $errors  = [];
    public int   $imported = 0;
    public int   $skipped  = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            $rowNum = $i + 2; // +2 because row 1 is heading

            $empId    = trim($row['employee_id'] ?? '');
            $locName  = trim($row['location']    ?? '');
            $type     = strtolower(trim($row['type'] ?? ''));
            $scanTime = trim($row['scan_date_time'] ?? '');

            // Validate required fields
            if (!$empId || !$locName || !$type || !$scanTime) {
                $this->errors[] = "Row {$rowNum}: Missing required field (employee_id, location, type, or scan_date_time).";
                $this->skipped++;
                continue;
            }

            if (!in_array($type, ['in', 'out'])) {
                $this->errors[] = "Row {$rowNum}: Type must be 'in' or 'out', got '{$type}'.";
                $this->skipped++;
                continue;
            }

            $employee = Employee::where('employee_id', $empId)->first();
            if (!$employee) {
                $this->errors[] = "Row {$rowNum}: Employee '{$empId}' not found.";
                $this->skipped++;
                continue;
            }

            // Exact match first, then case-insensitive, then partial match
            $location = Location::where('name', $locName)->first()
                ?? Location::whereRaw('LOWER(name) = ?', [strtolower($locName)])->first()
                ?? Location::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($locName) . '%'])->first();

            if (!$location) {
                $this->errors[] = "Row {$rowNum}: Location '{$locName}' not found. Check the Reference sheet in the template for valid names.";
                $this->skipped++;
                continue;
            }

            try {
                $scannedAt = Carbon::parse($scanTime);
            } catch (\Exception $e) {
                $this->errors[] = "Row {$rowNum}: Invalid date/time '{$scanTime}'.";
                $this->skipped++;
                continue;
            }

            // Calculate statuses and OT
            $workStartStr = $employee->work_start ?? '08:00:00';
            $workEndStr   = $employee->work_end   ?? '17:00:00';
            $workStart    = Carbon::parse($scannedAt->toDateString() . ' ' . $workStartStr);
            $workEnd      = Carbon::parse($scannedAt->toDateString() . ' ' . $workEndStr);
            $graceLimit   = $workStart->copy()->addMinutes(15);

            $checkInStatus  = null;
            $checkOutStatus = null;
            $otSeconds      = 0;

            if ($type === 'in') {
                $checkInStatus = $scannedAt->lessThanOrEqualTo($graceLimit) ? 'on_time' : 'late';
            } else {
                $checkOutStatus = $scannedAt->greaterThanOrEqualTo($workEnd) ? 'on_time' : 'early';
                if ($scannedAt->greaterThan($workEnd)) {
                    $otSeconds = max(0, $scannedAt->getTimestamp() - $workEnd->getTimestamp());
                }
            }

            Attendance::create([
                'employee_id'       => $employee->id,
                'location_id'       => $location->id,
                'type'              => $type,
                'check_in_status'   => $checkInStatus,
                'check_out_status'  => $checkOutStatus,
                'ot_seconds'        => $otSeconds,
                'scanned_lat'       => null,
                'scanned_lng'       => null,
                'distance_meters'   => null,
                'location_verified' => true,
                'scanned_at'        => $scannedAt,
                'ip_address'        => 'admin',
            ]);

            $this->imported++;
        }
    }
}
