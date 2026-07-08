<?php
// app/Http/Controllers/GetQrController.php
namespace App\Http\Controllers;

use App\Models\Employee;

class GetQrController extends Controller
{
    public function index()
    {
        $employees = Employee::orderBy('name')->get();
        return view('get-qr.index', compact('employees'));
    }
}
