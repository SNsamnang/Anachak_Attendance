<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;

class GetQrController extends Controller
{
    public function index(?string $code = null)
    {
        /** @var \App\Models\User|null $authUser */
        $authUser = Auth::user();
        $isSuperAdmin = $authUser?->is_super_admin;

        // ── /get-qr (all companies view) ──────────────────────────
        // Only super admin may see the "all" view.
        if (!$code) {
            if (!$isSuperAdmin) {
                // Company admin → redirect to their own company page
                $myCode = $authUser?->company?->code;
                if ($myCode) {
                    return redirect()->route('get-qr.show', $myCode);
                }
                // Not logged in or no company → back to login
                return redirect()->route('admin.login')
                    ->withErrors(['email' => 'Please log in to view QR codes.']);
            }

            // Super admin: show all
            $company   = null;
            $employees = Employee::with('company')->orderBy('name')->get();
            $companies = Company::orderBy('name')->get();

            return view('get-qr.index', compact('employees', 'company', 'companies'));
        }

        // ── /get-qr/{code} (company-specific view) ────────────────
        $company = Company::where('code', $code)->firstOrFail();

        // Logged-in company admin may only view their own company
        if ($authUser && !$isSuperAdmin) {
            $myCode = $authUser->company?->code;
            if ($myCode && $code !== $myCode) {
                return redirect()->route('get-qr.show', $myCode);
            }
        }

        $employees = Employee::where('company_id', $company->id)->orderBy('name')->get();

        // Switcher: super admin sees all companies; everyone else sees only this one
        $companies = $isSuperAdmin
            ? Company::orderBy('name')->get()
            : collect([$company]);

        return view('get-qr.index', compact('employees', 'company', 'companies'));
    }
}
