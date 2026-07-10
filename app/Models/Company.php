<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name', 'code', 'address', 'phone', 'is_active', 'salary_enabled', 'grace_minutes',
        'telegram_bot_token', 'telegram_chat_id',
        'telegram_topic_attendance', 'telegram_topic_leave', 'telegram_topic_device',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'salary_enabled' => 'boolean',
        'grace_minutes'  => 'integer',
    ];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
