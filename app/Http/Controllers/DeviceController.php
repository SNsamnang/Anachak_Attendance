<?php
// app/Http/Controllers/DeviceController.php
namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\Employee;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    // Admin: list all device requests (pending first)
    public function index()
    {
        $cid = $this->authCompanyId();

        $pending  = Device::with('employee')
            ->where('status', 'pending')
            ->when($cid, fn($q) => $q->whereHas('employee', fn($e) => $e->where('company_id', $cid)))
            ->orderByDesc('requested_at')
            ->get();

        $approved = Device::with('employee')
            ->where('status', 'approved')
            ->when($cid, fn($q) => $q->whereHas('employee', fn($e) => $e->where('company_id', $cid)))
            ->orderByDesc('approved_at')
            ->get();

        $rejected = Device::with('employee')
            ->where('status', 'rejected')
            ->when($cid, fn($q) => $q->whereHas('employee', fn($e) => $e->where('company_id', $cid)))
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();

        return view('devices.index', compact('pending', 'approved', 'rejected'));
    }

    // Admin: approve a device request
    public function approve(Device $device)
    {
        // Revoke old approved device for this employee
        Device::where('employee_id', $device->employee_id)
            ->where('status', 'approved')
            ->where('id', '!=', $device->id)
            ->update(['status' => 'rejected', 'rejected_reason' => 'Replaced by new device']);

        $device->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);

        $emp = $device->employee;
        app(\App\Services\TelegramService::class)
            ->forCompany($emp->company)
            ->notifyDeviceApproved($emp->name, $emp->employee_id, $device->name);

        return redirect()->route('devices.index')
            ->with('success', "Device approved for {$emp->name}.");
    }

    // Admin: reject a device request
    public function reject(Request $request, Device $device)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $device->update([
            'status'           => 'rejected',
            'rejected_reason'  => $request->reason ?? 'Rejected by admin',
        ]);

        $emp = $device->employee;
        app(\App\Services\TelegramService::class)
            ->forCompany($emp->company)
            ->notifyDeviceRejected($emp->name, $emp->employee_id, $device->name, $request->reason ?? 'No reason given');

        return redirect()->route('devices.index')
            ->with('success', "Device request rejected for {$emp->name}.");
    }

    // Admin: revoke an approved device
    public function revoke(Device $device)
    {
        $emp = $device->employee;
        $device->delete();

        return redirect()->route('devices.index')
            ->with('success', "Device revoked for {$emp->name}.");
    }
}