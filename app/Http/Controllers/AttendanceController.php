<?php
// app/Http/Controllers/AttendanceController.php
namespace App\Http\Controllers;

use App\Exports\AttendanceExport;
use App\Exports\AttendanceImportTemplate;
use App\Imports\AttendanceImport;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Attendance;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function __construct(
        protected TelegramService $telegram
    ) {}

    // ── Helper — format seconds as HH:MM:SS ───────────────────
    private function formatOt(int $seconds): string
    {
        if ($seconds <= 0) return '—';
        return sprintf(
            '%02d:%02d:%02d',
            floor($seconds / 3600),
            floor(($seconds % 3600) / 60),
            $seconds % 60
        );
    }

    // ── Shared: calculate check-in/out status & OT ────────────
    private function calcStatus(Employee $employee, string $type, \Carbon\Carbon $now): array
    {
        $workStartStr = $employee->work_start ?? '08:00:00';
        $workEndStr   = $employee->work_end   ?? '17:00:00';

        // Parse work start time
        $workStartParts = explode(':', $workStartStr);
        $workStart = \Carbon\Carbon::today()
            ->setHour((int)$workStartParts[0])
            ->setMinute((int)($workStartParts[1] ?? 0))
            ->setSecond((int)($workStartParts[2] ?? 0));

        // Parse work end time
        $workEndParts = explode(':', $workEndStr);
        $workEnd = \Carbon\Carbon::today()
            ->setHour((int)$workEndParts[0])
            ->setMinute((int)($workEndParts[1] ?? 0))
            ->setSecond((int)($workEndParts[2] ?? 0));

        $graceLimit   = $workStart->copy()->addMinutes(15);

        $checkInStatus  = null;
        $checkOutStatus = null;
        $otSeconds      = 0;

        if ($type === 'in') {
            $checkInStatus = $now->lessThanOrEqualTo($graceLimit) ? 'on_time' : 'late';
        } else {
            $checkOutStatus = $now->greaterThanOrEqualTo($workEnd) ? 'on_time' : 'early';
            if ($now->greaterThan($workEnd)) {
                $otSeconds = max(0, $now->getTimestamp() - $workEnd->getTimestamp());
            }
        }

        return compact('checkInStatus', 'checkOutStatus', 'otSeconds', 'workStartStr', 'workEndStr');
    }

    // ── Shared: send Telegram + return success JSON ───────────
    private function successResponse(
        Employee $employee,
        Location $location,
        string   $type,
        float    $distance,
        bool     $verified,
        string   $timeStr,
        ?string  $checkInStatus,
        ?string  $checkOutStatus,
        int      $otSeconds,
        string   $workStartStr,
        string   $workEndStr
    ) {
        $dept    = $employee->department ?? 'N/A';
        $address = $location->address ?? $location->name;
        $ot      = $this->formatOt($otSeconds);

        if ($verified) {
            if ($type === 'in') {
                $this->telegram->notifyCheckIn(
                    $employee->name,
                    $employee->employee_id,
                    $dept,
                    $location->name,
                    $address,
                    $distance,
                    $timeStr,
                    $checkInStatus,
                    \Carbon\Carbon::createFromTimeString($workStartStr)->format('H:i')
                );
            } else {
                $this->telegram->notifyCheckOut(
                    $employee->name,
                    $employee->employee_id,
                    $dept,
                    $location->name,
                    $address,
                    $distance,
                    $timeStr,
                    $checkOutStatus,
                    $ot,
                    \Carbon\Carbon::createFromTimeString($workEndStr)->format('H:i')
                );
            }

            return response()->json([
                'success'          => true,
                'type'             => $type,
                'message'          => $type === 'in' ? 'Checked in successfully!' : 'Checked out successfully!',
                'name'             => $employee->name,
                'location'         => $location->name,
                'distance'         => $distance,
                'time'             => $timeStr,
                'check_in_status'  => $checkInStatus,
                'check_out_status' => $checkOutStatus,
                'ot'               => $ot,
            ]);
        } else {
            $this->telegram->notifyOutOfRange(
                $employee->name,
                $employee->employee_id,
                $location->name,
                $distance,
                $location->radius_meters,
                $timeStr
            );

            return response()->json([
                'success'  => false,
                'type'     => 'rejected',
                'message'  => "You are {$distance}m away. Must be within {$location->radius_meters}m.",
                'distance' => $distance,
                'radius'   => $location->radius_meters,
            ], 403);
        }
    }

    /**
     * Location QR scan — employee scans location QR with their device.
     * Requires device token.
     */
    public function process(Request $request)
    {
        $request->validate([
            'employee_id'    => 'required|string',
            'location_token' => 'required|string',
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
        ]);

        $employee = Employee::where('employee_id', $request->employee_id)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee ID not found.'], 404);
        }

        $location = Location::where('qr_token', $request->location_token)
            ->where('is_active', true)->first();
        if (!$location) {
            return response()->json(['success' => false, 'message' => 'Location not found or inactive.'], 404);
        }

        $now      = now();
        $distance = round($location->distanceTo($request->latitude, $request->longitude), 1);
        $verified = $location->isWithinRadius($request->latitude, $request->longitude);
        $last     = $employee->lastAttendanceToday();
        $type     = ($last && $last->type === 'in') ? 'out' : 'in';
        $timeStr  = $now->format('H:i · D d M Y');

        $status = $this->calcStatus($employee, $type, $now);

        Attendance::create([
            'employee_id'       => $employee->id,
            'location_id'       => $location->id,
            'type'              => $type,
            'check_in_status'   => $status['checkInStatus'],
            'check_out_status'  => $status['checkOutStatus'],
            'ot_seconds'        => $status['otSeconds'],
            'scanned_lat'       => $request->latitude,
            'scanned_lng'       => $request->longitude,
            'distance_meters'   => $distance,
            'location_verified' => $verified,
            'scanned_at'        => $now,
            'ip_address'        => $request->ip(),
        ]);

        $this->telegram->forCompany($employee->company);

        return $this->successResponse(
            $employee,
            $location,
            $type,
            $distance,
            $verified,
            $timeStr,
            $status['checkInStatus'],
            $status['checkOutStatus'],
            $status['otSeconds'],
            $status['workStartStr'],
            $status['workEndStr']
        );
    }

    /**
     * Employee personal QR check-in — employee scans their own QR.
     * Requires device token + fingerprint check.
     */
    public function employeeProcess(Request $request)
    {
        $request->validate([
            'employee_token' => 'required|string',
            'location_id'    => 'required|integer|exists:locations,id',
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            'device_token'   => 'required|string',
            'fingerprint'    => 'nullable|string',
        ]);

        /** @var Employee $employee */
        $employee = Employee::where('qr_token', $request->employee_token)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Invalid employee QR code.'], 404);
        }

        $location = Location::findOrFail($request->location_id);
        if (!$location->is_active) {
            return response()->json(['success' => false, 'message' => 'Location is inactive.'], 404);
        }

        // Device + fingerprint check
        $fingerprint    = $request->fingerprint;
        $approvedDevice = \App\Models\Device::where('employee_id', $employee->id)
            ->where('status', 'approved')->first();

        if (!$approvedDevice) {
            return response()->json([
                'success' => false,
                'status' => 'no_device',
                'message' => 'No registered device. Please register first.',
            ], 403);
        }

        if ($fingerprint && $approvedDevice->fingerprint !== $fingerprint) {
            $pending = \App\Models\Device::where('employee_id', $employee->id)
                ->where('status', 'pending')->first();
            return response()->json([
                'success' => false,
                'status' => 'pending',
                'message' => $pending
                    ? 'New device pending admin approval. Please use your registered device.'
                    : 'This is not your registered device. Please register this device first.',
            ], 403);
        }

        if ($approvedDevice->token !== $request->device_token) {
            return response()->json([
                'success' => false,
                'status' => 'wrong_device',
                'message' => 'Device token mismatch. Please re-register.',
            ], 403);
        }

        $now      = now();
        $distance = round($location->distanceTo($request->latitude, $request->longitude), 1);
        $verified = $location->isWithinRadius($request->latitude, $request->longitude);
        $last = $employee->lastAttendanceToday();
        $type = ($last && $last->type === 'in') ? 'out' : 'in';
        $timeStr  = $now->format('H:i · D d M Y');

        $approvedDevice->update(['last_used_at' => $now]);

        $status = $this->calcStatus($employee, $type, $now);

        Attendance::create([
            'employee_id'       => $employee->id,
            'location_id'       => $location->id,
            'type'              => $type,
            'check_in_status'   => $status['checkInStatus'],
            'check_out_status'  => $status['checkOutStatus'],
            'ot_seconds'        => $status['otSeconds'],
            'scanned_lat'       => $request->latitude,
            'scanned_lng'       => $request->longitude,
            'distance_meters'   => $distance,
            'location_verified' => $verified,
            'scanned_at'        => $now,
            'ip_address'        => $request->ip(),
        ]);

        $this->telegram->forCompany($employee->company);

        return $this->successResponse(
            $employee,
            $location,
            $type,
            $distance,
            $verified,
            $timeStr,
            $status['checkInStatus'],
            $status['checkOutStatus'],
            $status['otSeconds'],
            $status['workStartStr'],
            $status['workEndStr']
        );
    }

    /**
     * Portal check-in — employee authenticated via session, no device check.
     */
    public function portalProcess(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer|exists:locations,id',
            'latitude'    => 'required|numeric|between:-90,90',
            'longitude'   => 'required|numeric|between:-180,180',
            'fingerprint' => 'nullable|string',
        ]);

        /** @var \App\Models\Employee $employee */
        $employee = Auth::guard('employee')->user();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Not authenticated.'], 401);
        }

        $fingerprint    = $request->fingerprint;

        // ── Device check ──────────────────────────────────────────
        $approvedDevice = \App\Models\Device::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->first();

        if (!$approvedDevice) {
            // No device at all — auto register this browser since
            // employee already proved identity via portal login
            $token = bin2hex(random_bytes(24));
            $approvedDevice = \App\Models\Device::create([
                'employee_id'  => $employee->id,
                'name'         => 'Portal — ' . now()->format('d M Y'),
                'token'        => $token,
                'status'       => \App\Models\Device::STATUS_APPROVED,
                'fingerprint'  => $fingerprint,
                'requested_at' => now(),
                'approved_at'  => now(),
            ]);
        } elseif ($fingerprint && $approvedDevice->fingerprint !== $fingerprint) {
            // Different device fingerprint
            $pending = \App\Models\Device::where('employee_id', $employee->id)
                ->where('status', 'pending')->first();

            if ($pending) {
                return response()->json([
                    'success' => false,
                    'status'  => 'wrong_device',
                    'message' => 'New device pending admin approval. Please use your registered device.',
                ], 403);
            }

            // Create pending request for new device
            $token = bin2hex(random_bytes(24));
            \App\Models\Device::create([
                'employee_id'  => $employee->id,
                'name'         => 'Portal — ' . now()->format('d M Y'),
                'token'        => $token,
                'status'       => \App\Models\Device::STATUS_PENDING,
                'fingerprint'  => $fingerprint,
                'requested_at' => now(),
            ]);

            app(\App\Services\TelegramService::class)
                ->forCompany($employee->company)
                ->notifyDeviceRequest($employee->name, $employee->employee_id, 'Portal — ' . now()->format('d M Y'));

            return response()->json([
                'success' => false,
                'status'  => 'wrong_device',
                'message' => 'Different device detected. Change request submitted — waiting for admin approval.',
            ], 403);
        }

        $approvedDevice->update(['last_used_at' => now()]);

        // ── Location ──────────────────────────────────────────────
        $location = Location::findOrFail($request->location_id);
        if (!$location->is_active) {
            return response()->json(['success' => false, 'message' => 'Location is inactive.'], 404);
        }

        $now      = now();
        $distance = round($location->distanceTo($request->latitude, $request->longitude), 1);
        $verified = $location->isWithinRadius($request->latitude, $request->longitude);
        $last = $employee->lastAttendanceToday();
        $type = ($last && $last->type === 'in') ? 'out' : 'in';
        $timeStr  = $now->format('H:i · D d M Y');
        $status   = $this->calcStatus($employee, $type, $now);

        Attendance::create([
            'employee_id'       => $employee->id,
            'location_id'       => $location->id,
            'type'              => $type,
            'check_in_status'   => $status['checkInStatus'],
            'check_out_status'  => $status['checkOutStatus'],
            'ot_seconds'        => $status['otSeconds'],
            'scanned_lat'       => $request->latitude,
            'scanned_lng'       => $request->longitude,
            'distance_meters'   => $distance,
            'location_verified' => $verified,
            'scanned_at'        => $now,
            'ip_address'        => $request->ip(),
        ]);

        $this->telegram->forCompany($employee->company);

        return $this->successResponse(
            $employee,
            $location,
            $type,
            $distance,
            $verified,
            $timeStr,
            $status['checkInStatus'],
            $status['checkOutStatus'],
            $status['otSeconds'],
            $status['workStartStr'],
            $status['workEndStr']
        );
    }

    /**
     * Admin attendance log with filters.
     */
    public function index(Request $request)
    {
        $query = Attendance::with(['employee', 'location'])->orderByDesc('scanned_at');

        if ($request->filled('date')) {
            $query->whereDate('scanned_at', $request->date);
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            if ($request->filled('date_from')) {
                $query->whereDate('scanned_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('scanned_at', '<=', $request->date_to);
            }
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('verified')) {
            $query->where('location_verified', $request->verified === '1');
        }
        if ($request->filled('ci_status')) {
            $query->where('check_in_status', $request->ci_status);
        }

        $cid = $this->authCompanyId();
        if ($cid) {
            $query->whereHas('employee', fn($q) => $q->where('company_id', $cid));
        }

        $attendances = $query->paginate(30)->withQueryString();
        $employees   = Employee::when($cid, fn($q) => $q->where('company_id', $cid))->orderBy('name')->get();
        $locations   = Location::when($cid, fn($q) => $q->where('company_id', $cid))->orderBy('name')->get();

        return view('attendance.index', compact('attendances', 'employees', 'locations'));
    }

    public function create()
    {
        $cid = $this->authCompanyId();
        $employees = Employee::when($cid, fn($q) => $q->where('company_id', $cid))->orderBy('name')->get();
        $locations = Location::where('is_active', true)
            ->when($cid, fn($q) => $q->where('company_id', $cid))->orderBy('name')->get();
        return view('attendance.create', compact('employees', 'locations'));
    }

    // ── Admin: store new attendance ───────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'employee_id'  => 'required|exists:employees,id',
            'location_id'  => 'required|exists:locations,id',
            'type'         => 'required|in:in,out',
            'scanned_at'   => 'required|date',
        ]);

        $employee = Employee::find($request->employee_id);
        $scannedAt = \Carbon\Carbon::parse($request->scanned_at);

        // Auto-calculate check_in_status / check_out_status / ot_seconds
        $workStartStr = $employee->work_start ?? '08:00:00';
        $workEndStr   = $employee->work_end   ?? '17:00:00';

        $workStart  = \Carbon\Carbon::parse($scannedAt->toDateString() . ' ' . $workStartStr);
        $workEnd    = \Carbon\Carbon::parse($scannedAt->toDateString() . ' ' . $workEndStr);
        $graceLimit = $workStart->copy()->addMinutes(15);

        $checkInStatus  = null;
        $checkOutStatus = null;
        $otSeconds      = 0;

        if ($request->type === 'in') {
            $checkInStatus = $scannedAt->lessThanOrEqualTo($graceLimit) ? 'on_time' : 'late';
        } else {
            $checkOutStatus = $scannedAt->greaterThanOrEqualTo($workEnd) ? 'on_time' : 'early';
            if ($scannedAt->greaterThan($workEnd)) {
                $otSeconds = max(0, $scannedAt->getTimestamp() - $workEnd->getTimestamp());
            }
        }

        Attendance::create([
            'employee_id'       => $request->employee_id,
            'location_id'       => $request->location_id,
            'type'              => $request->type,
            'check_in_status'   => $checkInStatus,
            'check_out_status'  => $checkOutStatus,
            'ot_seconds'        => $otSeconds,
            'scanned_lat'       => null,
            'scanned_lng'       => null,
            'distance_meters'   => null,
            'location_verified' => true,   // admin input = trusted
            'scanned_at'        => $scannedAt,
            'ip_address'        => 'admin',
        ]);

        return redirect()->route('attendance.index')
            ->with('success', "Attendance record added for {$employee->name}.");
    }

    // ── Admin: show edit form ─────────────────────────────────────
    public function edit(Attendance $attendance)
    {
        $cid = $this->authCompanyId();
        $employees = Employee::when($cid, fn($q) => $q->where('company_id', $cid))->orderBy('name')->get();
        $locations = Location::where('is_active', true)
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->orderBy('name')->get();
        return view('attendance.edit', compact('attendance', 'employees', 'locations'));
    }

    // ── Admin: update attendance ──────────────────────────────────
    public function update(Request $request, Attendance $attendance)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'location_id' => 'required|exists:locations,id',
            'type'        => 'required|in:in,out',
            'scanned_at'  => 'required|date',
        ]);

        $employee  = Employee::find($request->employee_id);
        $scannedAt = \Carbon\Carbon::parse($request->scanned_at);

        // Recalculate status & OT
        $workStartStr = $employee->work_start ?? '08:00:00';
        $workEndStr   = $employee->work_end   ?? '17:00:00';

        $workStart  = \Carbon\Carbon::parse($scannedAt->toDateString() . ' ' . $workStartStr);
        $workEnd    = \Carbon\Carbon::parse($scannedAt->toDateString() . ' ' . $workEndStr);
        $graceLimit = $workStart->copy()->addMinutes(15);

        $checkInStatus  = null;
        $checkOutStatus = null;
        $otSeconds      = 0;

        if ($request->type === 'in') {
            $checkInStatus = $scannedAt->lessThanOrEqualTo($graceLimit) ? 'on_time' : 'late';
        } else {
            $checkOutStatus = $scannedAt->greaterThanOrEqualTo($workEnd) ? 'on_time' : 'early';
            if ($scannedAt->greaterThan($workEnd)) {
                $otSeconds = max(0, $scannedAt->getTimestamp() - $workEnd->getTimestamp());
            }
        }

        $attendance->update([
            'employee_id'       => $request->employee_id,
            'location_id'       => $request->location_id,
            'type'              => $request->type,
            'check_in_status'   => $checkInStatus,
            'check_out_status'  => $checkOutStatus,
            'ot_seconds'        => $otSeconds,
            'location_verified' => true,
            'scanned_at'        => $scannedAt,
            'ip_address'        => $attendance->ip_address === 'admin'
                ? 'admin'
                : $attendance->ip_address,
        ]);

        return redirect()->route('attendance.index')
            ->with('success', "Attendance record updated for {$employee->name}.");
    }

    // ── Admin: delete attendance ──────────────────────────────────
    public function destroy(Attendance $attendance)
    {
        $name = $attendance->employee->name;
        $attendance->delete();

        return redirect()->route('attendance.index')
            ->with('success', "Attendance record deleted for {$name}.");
    }

    // ── Export attendance to Excel (respects current filters) ─────
    public function exportExcel(Request $request)
    {
        $filters  = $request->only(['date', 'date_from', 'date_to', 'employee_id', 'location_id', 'type']);
        $from     = $filters['date_from'] ?? now()->toDateString();
        $to       = $filters['date_to']   ?? now()->toDateString();
        $filename = "attendance-{$from}-{$to}.xlsx";

        return Excel::download(new AttendanceExport($filters), $filename);
    }

    // ── Download blank import template (with Reference sheet) ────
    public function importTemplate()
    {
        return Excel::download(new AttendanceImportTemplate(), 'attendance-import-template.xlsx');
    }

    // ── Import attendance from Excel ──────────────────────────────
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $import = new AttendanceImport();
        Excel::import($import, $request->file('file'));

        $msg = "Imported {$import->imported} record(s) successfully.";
        if ($import->skipped > 0) {
            $msg .= " {$import->skipped} row(s) skipped.";
        }

        return redirect()->route('attendance.index')
            ->with('success', $msg)
            ->with('import_errors', $import->errors);
    }
}
