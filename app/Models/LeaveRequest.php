<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'type',
        'leave_date',
        'leave_date_end',
        'start_time',
        'end_time',
        'reason',
        'status',
        'admin_note',
    ];

    protected $casts = [
        'leave_date'     => 'date',
        'leave_date_end' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function typeLabel(): string
    {
        return $this->type === 'day_off' ? 'Day Off' : 'Time Off';
    }

    public function durationLabel(): string
    {
        if ($this->type === 'time_off') {
            $s = \Carbon\Carbon::parse($this->start_time)->format('H:i');
            $e = \Carbon\Carbon::parse($this->end_time)->format('H:i');
            return "{$s} – {$e}";
        }
        if ($this->leave_date_end && $this->leave_date_end->ne($this->leave_date)) {
            return $this->leave_date->format('d M') . ' – ' . $this->leave_date_end->format('d M Y');
        }
        return $this->leave_date->format('d M Y');
    }

    public function statusBadge(): string
    {
        return match($this->status) {
            'pending'  => '<span class="badge" style="background:#fef9c3;color:#854d0e">Pending</span>',
            'approved' => '<span class="badge" style="background:#dcfce7;color:#166534">Approved</span>',
            'rejected' => '<span class="badge" style="background:#fee2e2;color:#991b1b">Rejected</span>',
            default    => '',
        };
    }
}
