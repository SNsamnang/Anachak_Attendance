<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OtStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtStatusController extends Controller
{
    private function checkSalaryAccess(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && !$user->is_super_admin && $user->company && !$user->company->salary_enabled) {
            abort(403, 'OT Eligibility is not available for your company.');
        }
    }

    // List all employees with their OT eligibility
    public function index()
    {
        $this->checkSalaryAccess();
        $cid = $this->authCompanyId();
        $employees = Employee::when($cid, fn($q) => $q->where('company_id', $cid))
            ->orderBy('name')->get()->map(function ($emp) {
            $emp->ot_eligible = optional($emp->otStatus)->eligible ?? true;
            return $emp;
        });

        return view('ot-status.index', compact('employees'));
    }

    // Toggle OT eligibility for an employee
    public function toggle(Employee $employee)
    {
        $this->checkSalaryAccess();
        $otStatus = OtStatus::firstOrCreate(
            ['employee_id' => $employee->id],
            ['eligible' => true]
        );

        $otStatus->update(['eligible' => !$otStatus->eligible]);

        return redirect()->route('ot-status.index')
            ->with('success', "{$employee->name} OT eligibility updated to "
                . ($otStatus->eligible ? 'Eligible' : 'Not Eligible') . ".");
    }
}
