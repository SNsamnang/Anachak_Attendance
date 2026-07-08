<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');
        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'slug'        => 'required|string|max:100|unique:roles,slug|alpha_dash',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create(['name' => $request->name, 'slug' => $request->slug]);
        $role->permissions()->sync($request->input('permissions', []));

        return redirect()->route('roles.index')->with('success', "Role {$role->name} created.");
    }

    public function edit(Role $role)
    {
        $permissions    = Permission::orderBy('group')->orderBy('name')->get()->groupBy('group');
        $rolePerms      = $role->permissions->pluck('id')->toArray();
        return view('roles.edit', compact('role', 'permissions', 'rolePerms'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'permissions'   => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update(['name' => $request->name]);
        $role->permissions()->sync($request->input('permissions', []));

        return redirect()->route('roles.index')->with('success', "Role {$role->name} updated.");
    }

    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return back()->with('error', "Cannot delete role — {$role->users_count} user(s) assigned.");
        }
        $name = $role->name;
        $role->delete();
        return redirect()->route('roles.index')->with('success', "Role {$name} deleted.");
    }
}
