<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\SalarySummaryController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\EmployeePortalController;
use App\Http\Controllers\GetqrController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Route;

// ── Admin Auth (public) ───────────────────────────────────────
Route::get('/admin/login',   [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login',  [AdminAuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// ── Admin routes (protected) ──────────────────────────────────
Route::middleware('admin.auth')->group(function () {

    // Dashboard
    Route::middleware('admin.can:dashboard')->group(function () {
        Route::get('/',                 [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/live',   [DashboardController::class, 'live'])->name('dashboard.live');
        Route::get('/dashboard/export', [DashboardController::class, 'exportCsv'])->name('dashboard.export');
    });

    // Employees
    Route::middleware('admin.can:employees')->group(function () {
        Route::resource('employees', EmployeeController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::get('/employees/{employee}/qr',                      [EmployeeController::class, 'showQr'])->name('employees.qr');
        Route::get('/employees/{employee}/download-qr',             [EmployeeController::class, 'downloadQr'])->name('employees.download-qr');
        Route::get('/employees/{employee}/set-password',            [EmployeeController::class, 'setPasswordForm'])->name('employees.set-password');
        Route::post('/employees/{employee}/set-password',           [EmployeeController::class, 'setPassword'])->name('employees.set-password.post');
        Route::post('/employees/{employee}/devices/generate',       [EmployeeController::class, 'generateDeviceToken'])->name('employees.devices.generate');
        Route::delete('/employees/{employee}/devices/{device}',     [EmployeeController::class, 'revokeDeviceToken'])->name('employees.devices.revoke');
    });

    // OT Status
    Route::middleware('admin.can:ot-status')->group(function () {
        Route::get('/ot-status', [\App\Http\Controllers\OtStatusController::class, 'index'])->name('ot-status.index');
        Route::post('/ot-status/{employee}/toggle', [\App\Http\Controllers\OtStatusController::class, 'toggle'])->name('ot-status.toggle');
    });

    // Locations
    Route::middleware('admin.can:locations')->group(function () {
        Route::resource('locations', LocationController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::get('/locations/{location}/qr',          [LocationController::class, 'showQr'])->name('locations.qr');
        Route::get('/locations/{location}/download-qr', [LocationController::class, 'downloadQr'])->name('locations.download-qr');
    });

    // Attendance log
    Route::middleware('admin.can:attendance')->group(function () {
        Route::get('/attendance',                   [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/create',            [AttendanceController::class, 'create'])->name('attendance.create');
        Route::post('/attendance',                  [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('/attendance/{attendance}/edit', [AttendanceController::class, 'edit'])->name('attendance.edit');
        Route::put('/attendance/{attendance}',      [AttendanceController::class, 'update'])->name('attendance.update');
        Route::delete('/attendance/{attendance}',   [AttendanceController::class, 'destroy'])->name('attendance.destroy');
        Route::get('/attendance/export/excel',      [AttendanceController::class, 'exportExcel'])->name('attendance.export.excel');
        Route::get('/attendance/import/template',   [AttendanceController::class, 'importTemplate'])->name('attendance.import.template');
        Route::post('/attendance/import',           [AttendanceController::class, 'import'])->name('attendance.import');
    });

    // Salary summaries
    Route::middleware('admin.can:salary-summaries')->group(function () {
        Route::get('/salary-summaries',                         [SalarySummaryController::class, 'index'])->name('salary-summaries.index');
        Route::get('/salary-summaries/export-all/excel',        [SalarySummaryController::class, 'exportAllExcel'])->name('salary-summaries.export-all.excel');
        Route::get('/salary-summaries/export-all/pdf',          [SalarySummaryController::class, 'exportAllPdf'])->name('salary-summaries.export-all.pdf');
        Route::get('/salary-summaries/{employee}',              [SalarySummaryController::class, 'show'])->name('salary-summaries.show');
        Route::post('/salary-summaries/generate',               [SalarySummaryController::class, 'generate'])->name('salary-summaries.generate');
        Route::get('/salary-summaries/{employee}/export/excel',  [SalarySummaryController::class, 'exportExcel'])->name('salary-summaries.export.excel');
        Route::get('/salary-summaries/{employee}/export/pdf',    [SalarySummaryController::class, 'exportPdf'])->name('salary-summaries.export.pdf');
        Route::delete('/salary-summaries/{employee}',            [SalarySummaryController::class, 'destroy'])->name('salary-summaries.destroy');
    });

    // Leave requests (admin)
    Route::middleware('admin.can:leave-requests')->group(function () {
        Route::get('/leave-requests',                          [LeaveRequestController::class, 'index'])->name('leave-requests.index');
        Route::post('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
        Route::post('/leave-requests/{leaveRequest}/reject',  [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
    });

    // Device management
    Route::middleware('admin.can:devices')->group(function () {
        Route::get('/devices/requests',           [DeviceController::class, 'index'])->name('devices.index');
        Route::post('/devices/{device}/approve',  [DeviceController::class, 'approve'])->name('devices.approve');
        Route::post('/devices/{device}/reject',   [DeviceController::class, 'reject'])->name('devices.reject');
        Route::delete('/devices/{device}/revoke', [DeviceController::class, 'revoke'])->name('devices.revoke');
    });

    // Users
    Route::middleware('admin.can:users')->group(function () {
        Route::resource('users', UserController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });

    // Roles & Permissions
    Route::middleware('admin.can:roles')->group(function () {
        Route::resource('roles', RoleController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });

    // Companies (super admin only)
    Route::middleware('admin.can:companies')->group(function () {
        Route::resource('companies', CompanyController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    });
});

// ── Public routes (no auth) ───────────────────────────────────

// Location QR scan
Route::get('/scan/{token}',        [LocationController::class, 'scanPage'])->name('locations.scan-page');
Route::post('/attendance/process', [AttendanceController::class, 'process'])->name('attendance.process');

// Employee personal QR check-in
Route::get('/employees/check-in/{token}',   [EmployeeController::class, 'employeeCheckInPage'])->name('employees.check-in-page');
Route::post('/attendance/employee-process', [AttendanceController::class, 'employeeProcess'])->name('attendance.employee-process');

// Device registration (no auth — called on check-in page load)
Route::post('/devices/register', [EmployeeController::class, 'registerDevice'])->name('devices.register');

// Get QR codes (public — no login needed)
Route::get('/get-qr', [GetqrController::class, 'index'])->name('get-qr.index');

// ── Employee Portal ───────────────────────────────────────────
Route::prefix('portal')->name('portal.')->group(function () {

    // Public portal routes
    Route::get('/login',   [EmployeePortalController::class, 'showLogin'])->name('login');
    Route::post('/login',  [EmployeePortalController::class, 'login'])->name('login.post');
    Route::post('/logout', [EmployeePortalController::class, 'logout'])->name('logout');

    // Protected portal routes
    Route::middleware('employee.auth')->group(function () {
        Route::get('/dashboard',            [EmployeePortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/checkin',              fn() => redirect()->route('portal.dashboard'))->name('checkin.page');
        Route::post('/checkin',             [AttendanceController::class, 'portalProcess'])->name('checkin');
        Route::post('/device-status',       [EmployeePortalController::class, 'deviceStatus'])->name('device-status');
        Route::get('/leave-request',          [EmployeePortalController::class, 'leaveRequestPage'])->name('leave-request');
        Route::post('/leave-request',         [EmployeePortalController::class, 'submitLeaveRequest'])->name('leave-request.store');
    });
});
