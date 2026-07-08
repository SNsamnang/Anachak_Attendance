<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\SalarySummary;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class SalarySummaryService
{
    /**
     * Generate salary summary for an employee for a specific date.
     *
     * @param Employee $employee
     * @param Carbon|string $date
     */
    public function generateDailySummary(Employee $employee, $date)
    {
        // Skip if employee has no salary set
        if (!$employee->salary) {
            return null;
        }

        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        $dateRecord = $date->format('Y-m-d');

        $summary = SalarySummary::firstOrNew([
            'employee_id'    => $employee->id,
            'date_of_record' => $dateRecord,
        ]);

        $checkIn = Attendance::where('employee_id', $employee->id)
            ->whereDate('scanned_at', $dateRecord)
            ->where('type', 'in')
            ->orderBy('scanned_at', 'asc')
            ->first();

        $checkOut = Attendance::where('employee_id', $employee->id)
            ->whereDate('scanned_at', $dateRecord)
            ->where('type', 'out')
            ->orderBy('scanned_at', 'desc')
            ->first();

        // Reset values before recalculating
        $summary->dayoff              = 0;
        $summary->dayoff_amount       = 0;
        $summary->leave_day           = 0;
        $summary->leave_day_amount    = 0;
        $summary->late                = 0;
        $summary->late_amount         = 0;
        $summary->early_leave         = 0;
        $summary->early_leave_amount  = 0;
        $summary->ot                  = 0;
        $summary->ot_amount           = 0;

        // Constants
        $salaryPerDay  = (float) $employee->salary / 26;  // working days per month
        $salaryPerHour = $salaryPerDay / 8;               // 8 hours per day

        // ── Day off (no check-in at all) ────────────────────────
        if (!$checkIn) {
            $hasLeave = LeaveRequest::where('employee_id', $employee->id)
                ->where('leave_date', $dateRecord)
                ->where('status', 'approved')
                ->exists();

            if ($hasLeave) {
                // Approved leave request → separate leave_day column, not counted as unauthorized dayoff
                $summary->leave_day        = 1;
                $summary->leave_day_amount = round($salaryPerDay, 2);
            } else {
                // No leave request → unauthorized absence
                $summary->dayoff        = 1;
                $summary->dayoff_amount = round($salaryPerDay, 2);
            }
        } else {
            // ── Late calculation ────────────────────────────────
            try {
                $workStartStr = $employee->work_start ?: '08:00:00';
                $workStart    = Carbon::parse($dateRecord . ' ' . $workStartStr);
                $checkInTime  = $checkIn->scanned_at;

                if ($checkInTime->greaterThan($workStart)) {
                    $lateMinutes = (int) $workStart->diffInMinutes($checkInTime);

                    if ($lateMinutes >= 15 && $lateMinutes < 60) {
                        // Flat fee: $3 if they have an approved leave request that day, else $5
                        $hasLeave = LeaveRequest::where('employee_id', $employee->id)
                            ->where('leave_date', $dateRecord)
                            ->where('status', 'approved')
                            ->exists();

                        $summary->late        = round($lateMinutes / 60, 2);
                        $summary->late_amount = $hasLeave ? 3.00 : 5.00;

                    } elseif ($lateMinutes >= 60) {
                        // Hourly deduction: salary / 26 days / 8 hours × rounded-up late hours
                        $lateHours = (int) ceil($lateMinutes / 60);
                        $summary->late        = $lateHours;
                        $summary->late_amount = round($salaryPerHour * $lateHours, 2);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('SalarySummaryService late calc error', [
                    'employee_id' => $employee->id,
                    'date'        => $dateRecord,
                    'message'     => $e->getMessage(),
                ]);
            }
        }

        // ── Early leave calculation ─────────────────────────────────
        if ($checkOut) {
            try {
                $workEndStr   = $employee->work_end ?: '17:00:00';
                $workEnd      = Carbon::parse($dateRecord . ' ' . $workEndStr);
                $checkOutTime = $checkOut->scanned_at;

                if ($checkOutTime->lessThan($workEnd)) {
                    $earlyMinutes = (int) $checkOutTime->diffInMinutes($workEnd, true);

                    // Any early leave rounds UP to the next full hour (no grace):
                    // < 1h early → deduct 1h, < 2h early → deduct 2h, etc.
                    if ($earlyMinutes > 0) {
                        $earlyHours = (int) ceil($earlyMinutes / 60);
                        $summary->early_leave        = $earlyHours;
                        $summary->early_leave_amount = round($salaryPerHour * $earlyHours, 2);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('SalarySummaryService early leave calc error', [
                    'employee_id' => $employee->id,
                    'date'        => $dateRecord,
                    'message'     => $e->getMessage(),
                ]);
            }
        }

        // ── OT calculation (only if employee is OT-eligible) ───────
        $otEligible = optional($employee->otStatus)->eligible ?? true;

        if ($otEligible && $checkOut) {
            try {
                $workEndStr = $employee->work_end ?: '17:00:00';
                $workEnd    = Carbon::parse($dateRecord . ' ' . $workEndStr);
                $checkOutTime = $checkOut->scanned_at;

                if ($checkOutTime->greaterThan($workEnd)) {
                    $otMinutes = $workEnd->diffInMinutes($checkOutTime);

                    if ($otMinutes > 0) {
                        $otHours = round($otMinutes / 60, 2);
                        $summary->ot        = $otHours;
                        $summary->ot_amount = round($salaryPerHour * $otHours, 2);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('SalarySummaryService OT calc error', [
                    'employee_id' => $employee->id,
                    'date'        => $dateRecord,
                    'message'     => $e->getMessage(),
                ]);
            }
        }

        $summary->save();

        return $summary;
    }

    /**
     * Generate summary for all employees for a specific date.
     */
    public function generateDailySummaryForAll($date)
    {
        if (!Schema::hasTable('salary_summaries')) {
            return;
        }

        foreach (Employee::all() as $employee) {
            $this->generateDailySummary($employee, $date);
        }
    }

    /**
     * Regenerate summary for an employee when they check out.
     */
    public function updateSummaryOnCheckOut(Attendance $attendance)
    {
        if (!Schema::hasTable('salary_summaries')) {
            return null;
        }

        $employee = $attendance->employee;
        $date     = $attendance->scanned_at->toDateString();

        return $this->generateDailySummary($employee, $date);
    }
}
