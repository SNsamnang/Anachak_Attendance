<?php

namespace App\Http\Controllers;

use App\Exports\SalarySummaryAllExport;
use App\Exports\SalarySummaryExport;
use App\Models\Employee;
use App\Models\SalarySummary;
use App\Services\SalarySummaryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class SalarySummaryController extends Controller
{
    public function __construct(protected SalarySummaryService $service) {}

    private function checkSalaryAccess(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if ($user && !$user->is_super_admin && $user->company && !$user->company->salary_enabled) {
            abort(403, 'Salary summaries are not enabled for your company.');
        }
    }

    public function index(Request $request)
    {
        $this->checkSalaryAccess();
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        $cid     = $this->authCompanyId();
        $empId   = $request->filled('employee_id') ? (int) $request->employee_id : null;
        $grouped = $this->buildGrouped($dateFrom, $dateTo, $empId, $cid);

        $employees = Employee::when($cid, fn($q) => $q->where('company_id', $cid))->orderBy('name')->get();

        return view('salary-summaries.index', [
            'grouped'   => $grouped,
            'employees' => $employees,
            'dateFrom'  => $dateFrom,
            'dateTo'    => $dateTo,
        ]);
    }

    public function exportAllExcel(Request $request)
    {
        $this->checkSalaryAccess();
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        $cid      = $this->authCompanyId();
        $grouped  = $this->buildGrouped($dateFrom, $dateTo, null, $cid);
        $filename = 'salary-summary-all-' . $dateFrom . '-' . $dateTo . '.xlsx';

        return Excel::download(new SalarySummaryAllExport($grouped, $dateFrom, $dateTo), $filename);
    }

    public function exportAllPdf(Request $request)
    {
        $this->checkSalaryAccess();
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        $cid     = $this->authCompanyId();
        $grouped = $this->buildGrouped($dateFrom, $dateTo, null, $cid);
        $filename = 'salary-summary-all-' . $dateFrom . '-' . $dateTo . '.pdf';

        $pdf = Pdf::loadView('salary-summaries.pdf-all', compact('grouped', 'dateFrom', 'dateTo'))
            ->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    private function buildGrouped(string $dateFrom, string $dateTo, ?int $employeeId, ?int $cid)
    {
        $query = SalarySummary::with('employee')
            ->whereDate('date_of_record', '>=', $dateFrom)
            ->whereDate('date_of_record', '<=', $dateTo)
            ->when($cid, fn($q) => $q->whereHas('employee', fn($e) => $e->where('company_id', $cid)))
            ->when($employeeId, fn($q) => $q->where('employee_id', $employeeId))
            ->orderBy('date_of_record');

        return $query->get()->groupBy('employee_id')->map(function ($rows) {
            return [
                'employee'                  => $rows->first()->employee,
                'rows'                      => $rows,
                'total_dayoff'              => $rows->sum('dayoff'),
                'total_dayoff_amount'       => $rows->sum('dayoff_amount'),
                'total_leave_day'           => $rows->sum('leave_day'),
                'total_leave_day_amount'    => $rows->sum('leave_day_amount'),
                'total_late'                => $rows->sum('late'),
                'total_late_amount'         => $rows->sum('late_amount'),
                'total_early_leave'         => $rows->sum('early_leave'),
                'total_early_leave_amount'  => $rows->sum('early_leave_amount'),
                'total_ot'                  => $rows->sum('ot'),
                'total_ot_amount'           => $rows->sum('ot_amount'),
                'total_unused_free_days'    => $rows->sum('unused_free_days'),
                'total_free_dayoff_bonus'   => $rows->sum('free_dayoff_bonus'),
            ];
        });
    }

    /**
     * Show detailed daily breakdown for one employee within a date range.
     */
    public function show(Request $request, Employee $employee)
    {
        $this->checkSalaryAccess();
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        $rows = SalarySummary::where('employee_id', $employee->id)
            ->whereDate('date_of_record', '>=', $dateFrom)
            ->whereDate('date_of_record', '<=', $dateTo)
            ->orderBy('date_of_record')
            ->get();

        return view('salary-summaries.show', [
            'employee' => $employee,
            'rows'     => $rows,
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ]);
    }

    public function destroy(Request $request, Employee $employee)
    {
        $this->checkSalaryAccess();

        $query = SalarySummary::where('employee_id', $employee->id);

        if ($request->filled('date_from')) {
            $query->whereDate('date_of_record', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date_of_record', '<=', $request->date_to);
        }

        $deleted = $query->delete();

        return redirect()->route('salary-summaries.index', $request->only('date_from', 'date_to'))
            ->with('success', "Deleted {$deleted} salary record(s) for {$employee->name}.");
    }

    public function exportExcel(Request $request, Employee $employee)
    {
        $this->checkSalaryAccess();
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());
        $filename = 'salary-' . $employee->employee_id . '-' . $dateFrom . '-' . $dateTo . '.xlsx';

        return Excel::download(new SalarySummaryExport($employee, $dateFrom, $dateTo), $filename);
    }

    public function exportPdf(Request $request, Employee $employee)
    {
        $this->checkSalaryAccess();
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        $rows = SalarySummary::where('employee_id', $employee->id)
            ->whereBetween('date_of_record', [$dateFrom, $dateTo])
            ->orderBy('date_of_record')
            ->get();

        $otEligible = optional($employee->otStatus)->eligible ?? true;
        $filename   = 'salary-' . $employee->employee_id . '-' . $dateFrom . '-' . $dateTo . '.pdf';

        $pdf = Pdf::loadView('salary-summaries.pdf', compact('employee', 'rows', 'dateFrom', 'dateTo', 'otEligible'))
            ->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    /**
     * Generate / update salary summary records from attendance data
     * for the given date range. Triggered by the "Update / Generate" button.
     */
    public function generate(Request $request)
    {
        $this->checkSalaryAccess();
        $request->validate([
            'date_from'   => 'required|date',
            'date_to'     => 'required|date|after_or_equal:date_from',
            'employee_id' => 'nullable|exists:employees,id',
            'free_dayoff' => 'nullable|integer|min:0|max:31',
        ]);

        $dateFrom   = Carbon::parse($request->date_from);
        $dateTo     = Carbon::parse($request->date_to);
        $freeDayoff = (int) $request->input('free_dayoff', 0);

        $cid = $this->authCompanyId();
        $employees = $request->filled('employee_id')
            ? Employee::where('id', $request->employee_id)->get()
            : Employee::when($cid, fn($q) => $q->where('company_id', $cid))->get();

        $created = 0;
        $skipped = 0;

        $employeeIds = $employees->pluck('id');

        // Delete old records for this period before regenerating
        SalarySummary::whereIn('employee_id', $employeeIds)
            ->whereBetween('date_of_record', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->delete();

        foreach ($employees as $employee) {
            if (!$employee->salary) {
                $skipped++;
                continue;
            }

            for ($date = $dateFrom->copy(); $date->lte($dateTo); $date->addDay()) {
                $this->service->generateDailySummary($employee, $date->copy());
                $created++;
            }

            // Apply free dayoff: zero out dayoff_amount for the first N absent days
            $actualDayoffs = SalarySummary::where('employee_id', $employee->id)
                ->whereBetween('date_of_record', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->where('dayoff', '>', 0)
                ->count();

            if ($freeDayoff > 0) {
                $freeIds = SalarySummary::where('employee_id', $employee->id)
                    ->whereBetween('date_of_record', [$dateFrom->toDateString(), $dateTo->toDateString()])
                    ->where('dayoff', '>', 0)
                    ->orderBy('date_of_record')
                    ->limit($freeDayoff)
                    ->pluck('id');

                SalarySummary::whereIn('id', $freeIds)->update(['dayoff_amount' => 0]);
            }

            // Unused free days → stored separately so they display as their own line
            $unusedFreeDays = max(0, $freeDayoff - $actualDayoffs);
            if ($unusedFreeDays > 0) {
                $salaryPerDay = (float) $employee->salary / 26;
                $bonus = round($salaryPerDay * $unusedFreeDays, 2);

                $lastRecord = SalarySummary::where('employee_id', $employee->id)
                    ->whereBetween('date_of_record', [$dateFrom->toDateString(), $dateTo->toDateString()])
                    ->orderBy('date_of_record', 'desc')
                    ->first();

                if ($lastRecord) {
                    $lastRecord->unused_free_days  = $unusedFreeDays;
                    $lastRecord->free_dayoff_bonus = $bonus;
                    $lastRecord->save();
                }
            }
        }

        $message = "Salary summary updated: {$created} record(s) processed";
        if ($freeDayoff > 0) {
            $message .= ", {$freeDayoff} free dayoff day(s) applied per employee";
        }
        if ($skipped > 0) {
            $message .= ", {$skipped} employee(s) skipped (no salary set)";
        }
        $message .= '.';

        return redirect()->route('salary-summaries.index', [
            'date_from' => $request->date_from,
            'date_to'   => $request->date_to,
        ])->with('success', $message);
    }
}
