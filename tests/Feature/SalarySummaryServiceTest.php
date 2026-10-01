<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use App\Models\OtStatus;
use App\Services\SalarySummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalarySummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_on_time_check_in_is_not_counted_as_late_in_salary_summary(): void
    {
        $employee = Employee::create([
            'name' => 'Test Employee',
            'employee_id' => 'TEST-001',
            'qr_token' => 'test-employee-token',
            'work_start' => '08:00:00',
            'work_end' => '17:00:00',
            'salary' => 2600,
        ]);
        $location = Location::create([
            'name' => 'Test Location',
            'latitude' => 0,
            'longitude' => 0,
            'qr_token' => 'test-location-token',
        ]);

        Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $location->id,
            'type' => 'in',
            'check_in_status' => 'on_time',
            'scanned_at' => '2026-06-01 08:30:00',
        ]);

        $summary = app(SalarySummaryService::class)->generateDailySummary($employee, '2026-06-01');

        $this->assertSame('0.00', $summary->late);
        $this->assertSame('0.00', $summary->late_amount);
    }

    public function test_late_check_in_still_receives_existing_salary_deduction(): void
    {
        $employee = Employee::create([
            'name' => 'Test Employee',
            'employee_id' => 'TEST-002',
            'qr_token' => 'test-employee-token-2',
            'work_start' => '08:00:00',
            'work_end' => '17:00:00',
            'salary' => 2600,
        ]);
        $location = Location::create([
            'name' => 'Test Location',
            'latitude' => 0,
            'longitude' => 0,
            'qr_token' => 'test-location-token-2',
        ]);

        Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $location->id,
            'type' => 'in',
            'check_in_status' => 'late',
            'scanned_at' => '2026-06-01 08:30:00',
        ]);

        $summary = app(SalarySummaryService::class)->generateDailySummary($employee, '2026-06-01');

        $this->assertSame('0.50', $summary->late);
        $this->assertSame('5.00', $summary->late_amount);
    }

    public function test_overtime_requires_explicit_eligibility_and_counts_time_after_work_end(): void
    {
        $employee = Employee::create([
            'name' => 'Test Employee',
            'employee_id' => 'TEST-003',
            'qr_token' => 'test-employee-token-3',
            'work_start' => '08:00:00',
            'work_end' => '17:00:00',
            'salary' => 2600,
        ]);
        $location = Location::create([
            'name' => 'Test Location',
            'latitude' => 0,
            'longitude' => 0,
            'qr_token' => 'test-location-token-3',
        ]);

        Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $location->id,
            'type' => 'out',
            'scanned_at' => '2026-06-01 18:30:00',
        ]);

        $service = app(SalarySummaryService::class);
        $summary = $service->generateDailySummary($employee, '2026-06-01');

        $this->assertSame('0.00', $summary->ot);
        $this->assertSame('0.00', $summary->ot_amount);

        OtStatus::create(['employee_id' => $employee->id, 'eligible' => true]);
        $employee->unsetRelation('otStatus');
        $summary = $service->generateDailySummary($employee, '2026-06-01');

        $this->assertSame('1.50', $summary->ot);
        $this->assertSame('18.75', $summary->ot_amount);
    }

    public function test_checkout_at_work_end_does_not_create_overtime(): void
    {
        $employee = Employee::create([
            'name' => 'Test Employee',
            'employee_id' => 'TEST-004',
            'qr_token' => 'test-employee-token-4',
            'work_start' => '08:00:00',
            'work_end' => '17:00:00',
            'salary' => 2600,
        ]);
        $location = Location::create([
            'name' => 'Test Location',
            'latitude' => 0,
            'longitude' => 0,
            'qr_token' => 'test-location-token-4',
        ]);

        OtStatus::create(['employee_id' => $employee->id, 'eligible' => true]);
        Attendance::create([
            'employee_id' => $employee->id,
            'location_id' => $location->id,
            'type' => 'out',
            'scanned_at' => '2026-06-01 17:00:00',
        ]);

        $summary = app(SalarySummaryService::class)->generateDailySummary($employee, '2026-06-01');

        $this->assertSame('0.00', $summary->ot);
        $this->assertSame('0.00', $summary->ot_amount);
    }

    public function test_second_session_overtime_uses_its_end_time_and_includes_seconds(): void
    {
        $employee = Employee::create([
            'name' => 'Test Employee',
            'employee_id' => 'TEST-005',
            'qr_token' => 'test-employee-token-5',
            'work_start' => '08:00:00',
            'work_end' => '18:00:00',
            'sessions' => 2,
            'session2_start' => '13:00:00',
            'session2_end' => '19:00:00',
            'salary' => 2600,
        ]);
        $location = Location::create([
            'name' => 'Test Location',
            'latitude' => 0,
            'longitude' => 0,
            'qr_token' => 'test-location-token-5',
        ]);

        OtStatus::create(['employee_id' => $employee->id, 'eligible' => true]);
        foreach (
            [
                ['in', '2026-06-01 08:00:00'],
                ['out', '2026-06-01 12:00:00'],
                ['in', '2026-06-01 13:00:00'],
                ['out', '2026-06-01 19:44:09'],
            ] as [$type, $scannedAt]
        ) {
            Attendance::create([
                'employee_id' => $employee->id,
                'location_id' => $location->id,
                'type' => $type,
                'check_in_status' => $type === 'in' ? 'on_time' : null,
                'scanned_at' => $scannedAt,
            ]);
        }

        $summary = app(SalarySummaryService::class)->generateDailySummary($employee, '2026-06-01');

        $this->assertSame('0.74', $summary->ot);
        $this->assertSame('9.25', $summary->ot_amount);
    }
}
