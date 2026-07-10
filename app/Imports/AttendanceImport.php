<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class AttendanceImport implements ToCollection, WithHeadingRow
{
    public array $errors  = [];
    public int   $imported = 0;
    public int   $skipped  = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $i => $row) {
            $rowNum = $i + 2; // +2 because row 1 is heading

            $empId   = trim($row['employee_id'] ?? '');
            $locName = trim($row['location']    ?? '');
            $type    = strtolower(trim($row['type'] ?? ''));
            $rawTime = $row['scan_date_time'] ?? '';

            // Validate required fields
            if (!$empId || !$locName || !$type || ($rawTime === '' || $rawTime === null)) {
                $this->errors[] = "Row {$rowNum}: Missing required field (employee_id, location, type, or scan_date_time).";
                $this->skipped++;
                continue;
            }

            if (!in_array($type, ['in', 'out'])) {
                $this->errors[] = "Row {$rowNum}: Type must be 'in' or 'out', got '{$type}'.";
                $this->skipped++;
                continue;
            }

            $employee = Employee::with('company')->where('employee_id', $empId)->first();
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
                // Excel stores dates as float serials; PhpSpreadsheet may return
                // a float, a DateTimeInterface, or a formatted string.
                if ($rawTime instanceof \DateTimeInterface) {
                    $scannedAt = Carbon::instance($rawTime);
                } elseif (is_numeric($rawTime)) {
                    $scannedAt = Carbon::instance(ExcelDate::excelToDateTimeObject((float) $rawTime));
                } else {
                    $scannedAt = Carbon::parse(trim((string) $rawTime));
                }
            } catch (\Exception $e) {
                $this->errors[] = "Row {$rowNum}: Invalid date/time '{$rawTime}'.";
                $this->skipped++;
                continue;
            }

            // Determine session number from prior records on the same day
            $priorCount = Attendance::where('employee_id', $employee->id)
                ->whereDate('scanned_at', $scannedAt->toDateString())
                ->where('scanned_at', '<', $scannedAt)
                ->count();
            $sessionNum = intdiv($priorCount, 2) + 1;

            // Pick session times
            if ($sessionNum === 2 && ($employee->sessions ?? 1) === 2 && $employee->session2_start) {
                $workStartStr = $employee->session2_start;
                $workEndStr   = $employee->session2_end ?? '17:00:00';
            } else {
                $workStartStr = $employee->work_start ?? '08:00:00';
                $workEndStr   = $employee->work_end   ?? '17:00:00';
            }

            $graceMinutes = $employee->company?->grace_minutes ?? 15;
            $dayStart     = $scannedAt->copy()->startOfDay();
            $workStart    = $dayStart->copy()->setTimeFromTimeString($workStartStr);
            $workEnd      = $dayStart->copy()->setTimeFromTimeString($workEndStr);
            $graceLimit   = $workStart->copy()->addMinutes($graceMinutes);

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
