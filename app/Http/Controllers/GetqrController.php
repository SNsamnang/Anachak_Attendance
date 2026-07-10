<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;

class GetQrController extends Controller
{
    public function index(?string $code = null)
    {
        if ($code) {
            $company   = Company::where('code', $code)->firstOrFail();
            $employees = Employee::where('company_id', $company->id)->orderBy('name')->get();
        } else {
            $company   = null;
            $employees = Employee::with('company')->orderBy('name')->get();
        }

        $companies = Company::orderBy('name')->get();

        return view('get-qr.index', compact('employees', 'company', 'companies'));
    }
}
