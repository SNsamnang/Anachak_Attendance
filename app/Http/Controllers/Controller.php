<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    protected function authCompanyId(): ?int
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (!$user || $user->is_super_admin) return null;
        return $user->company_id;
    }
}
