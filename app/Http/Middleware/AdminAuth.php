<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuth
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Eagerly load role.permissions and company so they don't N+1
        if ($user && !$user->relationLoaded('role')) {
            $user->load('role.permissions', 'company');
        }

        // Non-super-admin users must have a company assigned
        if ($user && !$user->is_super_admin && !$user->company_id) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Your account has no company assigned. Contact the super admin to assign one.']);
        }

        return $next($request);
    }
}
