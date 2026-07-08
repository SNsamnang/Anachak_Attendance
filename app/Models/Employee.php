<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;


class Employee extends Authenticatable
{
    protected $fillable = [
        'name',
        'employee_id',
        'department',
        'phone',
        'qr_token',
        'work_start',
        'work_end',
        'salary',
        'password',
        'company_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'work_start' => 'string',
        'work_end'   => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function ($e) {
            $e->qr_token = (string) Str::uuid();
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function lastAttendance()
    {
        return $this->hasOne(Attendance::class)->latestOfMany('scanned_at');
    }

    // Last attendance across ALL locations today (for toggle in/out logic)
    public function lastAttendanceToday()
    {
        return $this->attendances()
            ->whereDate('scanned_at', today())
            ->where('location_verified', true)
            ->latest('scanned_at')
            ->first();
    }

    public function workStartToday(): \Carbon\Carbon
    {
        $workStartStr = $this->work_start ?? '08:00:00';
        $parts = explode(':', $workStartStr);
        return \Carbon\Carbon::today()
            ->setHour((int)$parts[0])
            ->setMinute((int)($parts[1] ?? 0))
            ->setSecond((int)($parts[2] ?? 0));
    }

    public function workEndToday(): \Carbon\Carbon
    {
        $workEndStr = $this->work_end ?? '17:00:00';
        $parts = explode(':', $workEndStr);
        return \Carbon\Carbon::today()
            ->setHour((int)$parts[0])
            ->setMinute((int)($parts[1] ?? 0))
            ->setSecond((int)($parts[2] ?? 0));
    }

    public function workStartFormatted(): string
    {
        return \Carbon\Carbon::createFromTimeString($this->work_start ?? '08:00:00')->format('H:i');
    }

    public function workEndFormatted(): string
    {
        return \Carbon\Carbon::createFromTimeString($this->work_end ?? '17:00:00')->format('H:i');
    }

    // Admin sets password for employee
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }
    public function otStatus()
    {
        return $this->hasOne(OtStatus::class);
    }
}
