<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::withCount(['employees', 'users'])->orderBy('name')->get();
        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        return view('companies.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:100',
            'code'    => 'nullable|string|max:20|unique:companies,code',
            'address' => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:30',
        ]);

        $company = Company::create([
            'name'                      => $request->name,
            'code'                      => $request->code ?: null,
            'address'                   => $request->address,
            'phone'                     => $request->phone,
            'is_active'                 => $request->boolean('is_active', true),
            'salary_enabled'            => $request->boolean('salary_enabled'),
            'grace_minutes'             => max(0, (int) $request->input('grace_minutes', 15)),
            'telegram_bot_token'        => $request->telegram_bot_token ?: null,
            'telegram_chat_id'          => $request->telegram_chat_id ?: null,
            'telegram_topic_attendance' => $request->telegram_topic_attendance ?: null,
            'telegram_topic_leave'      => $request->telegram_topic_leave ?: null,
            'telegram_topic_device'     => $request->telegram_topic_device ?: null,
        ]);

        return redirect()->route('companies.index')->with('success', "Company \"{$company->name}\" created.");
    }

    public function edit(Company $company)
    {
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $request->validate([
            'name'    => 'required|string|max:100',
            'code'    => 'nullable|string|max:20|unique:companies,code,' . $company->id,
            'address' => 'nullable|string|max:255',
            'phone'   => 'nullable|string|max:30',
        ]);

        $company->update([
            'name'                      => $request->name,
            'code'                      => $request->code ?: null,
            'address'                   => $request->address,
            'phone'                     => $request->phone,
            'is_active'                 => $request->boolean('is_active', true),
            'salary_enabled'            => $request->boolean('salary_enabled'),
            'grace_minutes'             => max(0, (int) $request->input('grace_minutes', 15)),
            'telegram_bot_token'        => $request->telegram_bot_token ?: null,
            'telegram_chat_id'          => $request->telegram_chat_id ?: null,
            'telegram_topic_attendance' => $request->telegram_topic_attendance ?: null,
            'telegram_topic_leave'      => $request->telegram_topic_leave ?: null,
            'telegram_topic_device'     => $request->telegram_topic_device ?: null,
        ]);

        return redirect()->route('companies.index')->with('success', "Company \"{$company->name}\" updated.");
    }

    public function destroy(Company $company)
    {
        if ($company->employees()->exists()) {
            return back()->with('error', "Cannot delete: company has assigned employees.");
        }
        if ($company->users()->exists()) {
            return back()->with('error', "Cannot delete: company has assigned users.");
        }

        $name = $company->name;
        $company->delete();
        return redirect()->route('companies.index')->with('success', "Company \"{$name}\" deleted.");
    }
}
