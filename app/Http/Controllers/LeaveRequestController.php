<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveRequest::with('employee')
            ->orderByRaw("FIELD(status,'pending','approved','rejected')")
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $cid = $this->authCompanyId();
        if ($cid) {
            $query->whereHas('employee', fn($q) => $q->where('company_id', $cid));
        }

        $requests     = $query->paginate(25)->withQueryString();
        $employees    = Employee::when($cid, fn($q) => $q->where('company_id', $cid))->orderBy('name')->get();
        $pendingCount = LeaveRequest::when($cid, fn($q) => $q->whereHas('employee', fn($e) => $e->where('company_id', $cid)))
            ->where('status', 'pending')->count();

        return view('leave-requests.index', compact('requests', 'employees', 'pendingCount'));
    }

    public function approve(LeaveRequest $leaveRequest, Request $request)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Already processed.');
        }

        $leaveRequest->update([
            'status'     => 'approved',
            'admin_note' => $request->admin_note,
        ]);

        [$typeLabel, $period] = $this->leaveInfo($leaveRequest);
        app(TelegramService::class)
            ->forCompany($leaveRequest->employee->company)
            ->notifyLeaveApproved(
                $leaveRequest->employee->name,
                $leaveRequest->employee->employee_id,
                $typeLabel, $period, $request->admin_note
            );

        return back()->with('success', "Leave approved for {$leaveRequest->employee->name}.");
    }

    public function reject(LeaveRequest $leaveRequest, Request $request)
    {
        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Already processed.');
        }

        $request->validate(['admin_note' => 'required|string|max:500']);

        $leaveRequest->update([
            'status'     => 'rejected',
            'admin_note' => $request->admin_note,
        ]);

        [$typeLabel, $period] = $this->leaveInfo($leaveRequest);
        app(TelegramService::class)
            ->forCompany($leaveRequest->employee->company)
            ->notifyLeaveRejected(
                $leaveRequest->employee->name,
                $leaveRequest->employee->employee_id,
                $typeLabel, $period, $request->admin_note
            );

        return back()->with('success', "Leave rejected for {$leaveRequest->employee->name}.");
    }

    private function leaveInfo(LeaveRequest $leave): array
    {
        if ($leave->type === 'day_off') {
            $period = $leave->leave_date->format('d M Y');
            if ($leave->leave_date_end && $leave->leave_date_end->ne($leave->leave_date)) {
                $period .= ' – ' . $leave->leave_date_end->format('d M Y');
            }
            return ['📅 Day Off', $period];
        }

        $period = $leave->leave_date->format('d M Y') . ' '
            . substr($leave->start_time, 0, 5) . '–' . substr($leave->end_time, 0, 5);
        return ['🕐 Time Off', $period];
    }
}
