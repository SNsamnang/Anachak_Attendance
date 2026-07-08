<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['role', 'company'])->orderBy('name')->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles     = Role::orderBy('name')->get();
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        return view('users.create', compact('roles', 'companies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:6|confirmed',
            'role_id'    => 'required|exists:roles,id',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'password'       => $request->password,
            'role_id'        => $request->role_id,
            'company_id'     => $request->company_id ?: null,
            'is_super_admin' => false,
        ]);

        return redirect()->route('users.index')->with('success', "User {$request->name} created.");
    }

    public function edit(User $user)
    {
        $roles     = Role::orderBy('name')->get();
        $companies = Company::where('is_active', true)->orderBy('name')->get();
        return view('users.edit', compact('user', 'roles', 'companies'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email,' . $user->id,
            'password'   => 'nullable|string|min:6|confirmed',
            'role_id'    => 'required|exists:roles,id',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $data = [
            'name'       => $request->name,
            'email'      => $request->email,
            'role_id'    => $request->role_id,
            'company_id' => $request->company_id ?: null,
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', "User {$user->name} updated.");
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        $name = $user->name;
        $user->delete();
        return redirect()->route('users.index')->with('success', "User {$name} deleted.");
    }
}
