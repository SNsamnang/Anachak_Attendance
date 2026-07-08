<?php
// app/Http/Controllers/EmployeeController.php
namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Device;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class EmployeeController extends Controller
{
    public function index()
    {
        $cid = $this->authCompanyId();
        $employees = Employee::withCount('attendances')
            ->when($cid, fn($q) => $q->where('company_id', $cid))
            ->orderBy('name')->get();
        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        $companies = auth()->user()->is_super_admin ? Company::orderBy('name')->get() : collect();
        return view('employees.create', compact('companies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'department' => 'nullable|string|max:100',
            'phone'      => 'nullable|string|max:20',
            'work_start' => 'required|date_format:H:i',
            'work_end'   => 'required|date_format:H:i|after:work_start',
            'salary'     => 'nullable|numeric|min:0',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $companyId  = auth()->user()->is_super_admin
            ? $request->company_id
            : auth()->user()->company_id;

        $employeeId = $this->generateEmployeeId($companyId);

        Employee::create(array_merge(
            $request->only('name', 'department', 'phone', 'work_start', 'work_end', 'salary'),
            ['company_id' => $companyId, 'employee_id' => $employeeId, 'password' => '123456']
        ));

        return redirect()->route('employees.index')
            ->with('success', "{$request->name} added (ID: {$employeeId}).");
    }

    private function generateEmployeeId(?int $companyId): string
    {
        $prefix = 'EMP';
        if ($companyId) {
            $company = Company::find($companyId);
            if ($company?->code) {
                $prefix = strtoupper(trim($company->code));
            }
        }

        $max = Employee::where('employee_id', 'like', $prefix . '%')
            ->pluck('employee_id')
            ->map(fn($id) => is_numeric($n = substr($id, strlen($prefix))) ? (int) $n : 0)
            ->max() ?? 0;

        return $prefix . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'department' => 'nullable|string|max:100',
            'phone'      => 'nullable|string|max:20',
            'work_start' => 'required|date_format:H:i',
            'work_end'   => 'required|date_format:H:i|after:work_start',
            'salary'     => 'nullable|numeric|min:0',
        ]);

        $employee->update($request->only(
            'name',
            'department',
            'phone',
            'work_start',
            'work_end',
            'salary'
        ));
        return redirect()->route('employees.index')->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Deleted.');
    }

    private function qrFormat(): string
    {
        return extension_loaded('imagick') ? 'png' : 'svg';
    }

    private function qrMimeType(string $format): string
    {
        return $format === 'svg' ? 'image/svg+xml' : 'image/png';
    }

    private function qrFilename(string $basename, string $format): string
    {
        return $basename . '-qr.' . ($format === 'svg' ? 'svg' : 'png');
    }

    // Show personal QR code (employee shows this to scanner, or scans location QR)
    public function showQr(Employee $employee)
    {
        $format   = $this->qrFormat();
        $checkInUrl = route('employees.check-in-page', $employee->qr_token);
        $qrCode   = QrCode::format($format)->size(300)->errorCorrection('H')
            ->generate($checkInUrl);
        $qrBase64 = base64_encode((string) $qrCode);
        $qrMimeType = $this->qrMimeType($format);

        return view('employees.qr', compact('employee', 'qrBase64', 'qrMimeType'));
    }

    public function downloadQr(Employee $employee)
    {
        $format  = $this->qrFormat();
        $checkInUrl = route('employees.check-in-page', $employee->qr_token);
        $qrCode  = QrCode::format($format)->size(500)->errorCorrection('H')
            ->generate($checkInUrl);
        $filename = $this->qrFilename($employee->employee_id, $format);

        return response((string) $qrCode)
            ->header('Content-Type', $this->qrMimeType($format))
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    // Employee scanned their personal QR — show location selection page
    public function employeeCheckInPage(string $token)
    {
        $employee = Employee::where('qr_token', $token)->firstOrFail();
        $locations = Location::where('is_active', true)->orderBy('name')->get();

        return view('employees.check-in', compact('employee', 'locations', 'token'));
    }

    // Public endpoint: register a device for an employee (employee provides employee_id + password)
    public function registerDevice(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'qr_token'    => 'required|string',   // employee's own QR token = identity proof
            'name'        => 'nullable|string|max:100',
            'fingerprint' => 'nullable|string|max:500',
        ]);

        $employee = Employee::where('qr_token', $request->qr_token)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Invalid QR code.'], 403);
        }

        $fingerprint = $request->fingerprint;

        // Check if employee already has an approved device
        $approved = Device::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->first();

        if ($approved) {
            // ✅ SAME FINGERPRINT — return existing token silently
            if ($fingerprint && $approved->fingerprint === $fingerprint) {
                return response()->json([
                    'success' => true,
                    'status'  => 'approved',
                    'token'   => $approved->token,
                    'message' => 'Device recognized.',
                ]);
            }

            // ❌ DIFFERENT FINGERPRINT — check if already has pending
            $pending = Device::where('employee_id', $employee->id)
                ->where('status', 'pending')
                ->first();

            if ($pending) {
                return response()->json([
                    'success' => false,
                    'status'  => 'pending',
                    'message' => 'Device change request already submitted. Waiting for admin approval.',
                ]);
            }

            // Create pending request
            $token = bin2hex(random_bytes(24));
            Device::create([
                'employee_id'  => $employee->id,
                'name'         => $request->name ?? 'New Device',
                'token'        => $token,
                'status'       => Device::STATUS_PENDING,
                'fingerprint'  => $fingerprint,
                'requested_at' => now(),
            ]);

            app(\App\Services\TelegramService::class)->send(
                "📱 <b>DEVICE CHANGE REQUEST</b>\n"
                    . "👤 <b>{$employee->name}</b> ({$employee->employee_id})\n"
                    . "🏢 {$employee->department}\n"
                    . "📲 New device: " . ($request->name ?? 'Unknown') . "\n"
                    . "🕐 " . now()->format('H:i · D d M Y') . "\n\n"
                    . "⚙️ Approve at: " . url('/devices/requests')
            );

            return response()->json([
                'success' => false,
                'status'  => 'pending',
                'message' => 'New device detected. Change request submitted — waiting for admin approval.',
            ]);
        }

        // No device yet — auto-approve first registration
        $token = bin2hex(random_bytes(24));
        Device::create([
            'employee_id'  => $employee->id,
            'name'         => $request->name ?? 'My Device',
            'token'        => $token,
            'status'       => Device::STATUS_APPROVED,
            'fingerprint'  => $fingerprint,
            'requested_at' => now(),
            'approved_at'  => now(),
        ]);

        return response()->json([
            'success' => true,
            'status'  => 'approved',
            'token'   => $token,
            'message' => 'Device registered!',
        ]);
    }
    // Admin: generate device token for employee (admin-only route)
    public function generateDeviceToken(Request $request, Employee $employee)
    {
        $token = bin2hex(random_bytes(24));
        $device = Device::create(['employee_id' => $employee->id, 'name' => 'Admin generated', 'token' => $token]);
        return redirect()->back()->with('success', 'Device token generated.')->with('device_token', $token);
    }

    // Admin: revoke a device
    public function revokeDeviceToken(Employee $employee, Device $device)
    {
        if ($device->employee_id !== $employee->id) {
            return redirect()->back()->with('error', 'Device mismatch.');
        }
        $device->delete();
        return redirect()->back()->with('success', 'Device revoked.');
    }

    // Show form to set portal password for an employee (admin)
    public function setPasswordForm(Employee $employee)
    {
        return view('employees.set_password', compact('employee'));
    }

    // Handle set password POST
    public function setPassword(Request $request, Employee $employee)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $employee->password = $request->password; // mutator will hash
        $employee->save();

        return redirect()->route('employees.index')->with('success', 'Password set.');
    }
}
