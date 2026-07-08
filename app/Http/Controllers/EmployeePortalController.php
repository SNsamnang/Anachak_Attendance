<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Location;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmployeePortalController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('employee')->check()) {
            return redirect()->route('portal.dashboard');
        }
        return view('portal.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string',
            'password'    => 'required|string',
        ]);

        /** @var Employee|null $employee */
        $employee = Employee::where('employee_id', $request->employee_id)->first();

        if (!$employee || !$employee->password) {
            return back()->withErrors([
                'employee_id' => 'Employee ID not found or no password set. Contact admin.',
            ])->withInput();
        }

        if (!Hash::check($request->password, $employee->password)) {
            return back()->withErrors([
                'password' => 'Incorrect password.',
            ])->withInput();
        }

        Auth::guard('employee')->login($employee, $request->boolean('remember'));

        return redirect()->route('portal.dashboard');
    }

    public function logout()
    {
        Auth::guard('employee')->logout();
        return redirect()->route('portal.login');
    }

    public function deviceStatus(Request $request): JsonResponse
    {
        /** @var Employee $employee */
        $employee    = Auth::guard('employee')->user();
        $fingerprint = $request->input('fingerprint');

        $approved = Device::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->first();

        if ($approved) {
            if (!$fingerprint || $approved->fingerprint === $fingerprint) {
                return response()->json(['status' => 'approved']);
            }
            $pending = Device::where('employee_id', $employee->id)
                ->where('status', 'pending')->first();
            return response()->json(['status' => $pending ? 'pending' : 'approved']);
        }

        $pending = Device::where('employee_id', $employee->id)
            ->where('status', 'pending')->first();

        if ($pending) {
            return response()->json(['status' => 'pending']);
        }

        return response()->json(['status' => 'no_device']);
    }

    public function dashboard(Request $request)
    {
        /** @var Employee $employee */
        $employee = Auth::guard('employee')->user();
        $today    = now()->toDateString();

        $totalDays = Attendance::where('employee_id', $employee->id)
            ->where('type', 'in')
            ->where('location_verified', true)
            ->select(DB::raw('DATE(scanned_at) as date'))
            ->distinct()
            ->count();

        $lateDays = Attendance::where('employee_id', $employee->id)
            ->where('type', 'in')
            ->where('check_in_status', 'late')
            ->count();

        $onTimeDays = Attendance::where('employee_id', $employee->id)
            ->where('type', 'in')
            ->where('check_in_status', 'on_time')
            ->count();

        $totalOtSeconds = Attendance::where('employee_id', $employee->id)
            ->where('type', 'out')
            ->sum('ot_seconds');

        $todayRecords = Attendance::with('location')
            ->where('employee_id', $employee->id)
            ->whereDate('scanned_at', $today)
            ->orderBy('scanned_at')
            ->get();

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to',   now()->toDateString());

        $history = Attendance::with('location')
            ->where('employee_id', $employee->id)
            ->whereDate('scanned_at', '>=', $dateFrom)
            ->whereDate('scanned_at', '<=', $dateTo)
            ->orderByDesc('scanned_at')
            ->get()
            ->groupBy(fn($a) => $a->scanned_at->toDateString());

        $monthlyOt = Attendance::where('employee_id', $employee->id)
            ->where('type', 'out')
            ->whereDate('scanned_at', '>=', $dateFrom)
            ->whereDate('scanned_at', '<=', $dateTo)
            ->sum('ot_seconds');

        $locations = Location::where('is_active', true)->orderBy('name')->get();

        return view('portal.dashboard', compact(
            'employee', 'totalDays', 'lateDays', 'onTimeDays',
            'totalOtSeconds', 'todayRecords', 'history',
            'monthlyOt', 'dateFrom', 'dateTo', 'locations'
        ));
    }

    public function leaveRequestPage()
    {
        /** @var Employee $employee */
        $employee   = Auth::guard('employee')->user();
        $myRequests = LeaveRequest::where('employee_id', $employee->id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return view('portal.leave-request', compact('employee', 'myRequests'));
    }

    public function submitLeaveRequest(Request $request)
    {
        /** @var Employee $employee */
        $employee = Auth::guard('employee')->user();

        $type = $request->input('type', 'day_off');

        if ($type === 'day_off') {
            $request->validate([
                'type'           => 'required|in:day_off,time_off',
                'leave_date'     => 'required|date',
                'leave_date_end' => 'nullable|date|after_or_equal:leave_date',
                'reason'         => 'required|string|max:500',
            ]);
            $leave = LeaveRequest::create([
                'employee_id'    => $employee->id,
                'type'           => 'day_off',
                'leave_date'     => $request->leave_date,
                'leave_date_end' => $request->leave_date_end ?: null,
                'reason'         => $request->reason,
                'status'         => 'pending',
            ]);
        } else {
            $request->validate([
                'type'       => 'required|in:day_off,time_off',
                'leave_date' => 'required|date',
                'start_time' => 'required|date_format:H:i',
                'end_time'   => 'required|date_format:H:i|after:start_time',
                'reason'     => 'required|string|max:500',
            ]);
            $leave = LeaveRequest::create([
                'employee_id' => $employee->id,
                'type'        => 'time_off',
                'leave_date'  => $request->leave_date,
                'start_time'  => $request->start_time . ':00',
                'end_time'    => $request->end_time . ':00',
                'reason'      => $request->reason,
                'status'      => 'pending',
            ]);
        }

        $this->notifyTelegram($employee, $leave);

        return redirect()->route('portal.leave-request')
            ->with('success', 'Leave request submitted. Admin will review it shortly.');
    }

    private function notifyTelegram(Employee $employee, LeaveRequest $leave): void
    {
        if ($leave->type === 'day_off') {
            $period = $leave->leave_date->format('d M Y');
            if ($leave->leave_date_end && $leave->leave_date_end->ne($leave->leave_date)) {
                $period .= ' – ' . $leave->leave_date_end->format('d M Y');
            }
            $typeLabel = '📅 Day Off';
        } else {
            $period    = $leave->leave_date->format('d M Y') . ' '
                       . substr($leave->start_time, 0, 5) . '–' . substr($leave->end_time, 0, 5);
            $typeLabel = '🕐 Time Off';
        }

        app(TelegramService::class)
            ->forCompany($employee->company)
            ->notifyLeaveSubmitted(
                $employee->name, $employee->employee_id,
                $typeLabel, $period, $leave->reason
            );
    }

    public static function formatOt(int $seconds): string
    {
        if ($seconds <= 0) return '—';
        return sprintf(
            '%02d:%02d:%02d',
            floor($seconds / 3600),
            floor(($seconds % 3600) / 60),
            $seconds % 60
        );
    }
}
