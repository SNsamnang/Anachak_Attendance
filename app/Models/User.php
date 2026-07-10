<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $email
 * @property bool        $is_super_admin
 * @property int|null    $role_id
 * @property int|null    $company_id
 */
class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role_id', 'is_super_admin', 'company_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'       => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->is_super_admin) return true;
        if (!$this->relationLoaded('role') || !$this->role) return false;

        return $this->role->permissions->contains('slug', $slug);
    }
}
