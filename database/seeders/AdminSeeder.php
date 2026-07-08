<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // ── Permissions ────────────────────────────────────────────
        $defs = [
            ['name' => 'View Dashboard',         'slug' => 'dashboard',        'group' => 'Dashboard'],
            ['name' => 'Manage Employees',        'slug' => 'employees',        'group' => 'Employees'],
            ['name' => 'Manage Attendance',       'slug' => 'attendance',       'group' => 'Attendance'],
            ['name' => 'Manage Locations',        'slug' => 'locations',        'group' => 'Locations'],
            ['name' => 'Manage OT Eligibility',   'slug' => 'ot-status',        'group' => 'Attendance'],
            ['name' => 'Manage Leave Requests',   'slug' => 'leave-requests',   'group' => 'Leave'],
            ['name' => 'Manage Salary Summaries', 'slug' => 'salary-summaries', 'group' => 'Salary'],
            ['name' => 'Manage Devices',          'slug' => 'devices',          'group' => 'Devices'],
            ['name' => 'Manage Users',            'slug' => 'users',            'group' => 'Admin'],
            ['name' => 'Manage Roles',            'slug' => 'roles',            'group' => 'Admin'],
            ['name' => 'Manage Companies',        'slug' => 'companies',        'group' => 'Admin'],
        ];

        foreach ($defs as $def) {
            Permission::firstOrCreate(['slug' => $def['slug']], $def);
        }

        $all = Permission::pluck('id');

        // ── Roles ──────────────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $superAdmin->permissions()->sync($all);

        $manager = Role::firstOrCreate(['slug' => 'manager'], ['name' => 'Manager']);
        $manager->permissions()->sync(
            Permission::whereIn('slug', ['dashboard','employees','attendance','ot-status','leave-requests','salary-summaries','devices'])->pluck('id')
        );

        $hr = Role::firstOrCreate(['slug' => 'hr'], ['name' => 'HR']);
        $hr->permissions()->sync(
            Permission::whereIn('slug', ['dashboard','employees','attendance','leave-requests'])->pluck('id')
        );

        $viewer = Role::firstOrCreate(['slug' => 'viewer'], ['name' => 'Viewer']);
        $viewer->permissions()->sync(
            Permission::whereIn('slug', ['dashboard','attendance'])->pluck('id')
        );

        // ── Super admin user (from .env or defaults) ───────────────
        $email    = config('app.admin_email',    'admin@example.com');
        $password = config('app.admin_password', 'password');
        $name     = 'Super Admin';

        if (!User::where('email', $email)->exists()) {
            User::create([
                'name'           => $name,
                'email'          => $email,
                'password'       => $password,
                'role_id'        => $superAdmin->id,
                'is_super_admin' => true,
            ]);
        }
    }
}
