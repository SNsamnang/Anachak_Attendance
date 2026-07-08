<?php
// app/Models/Attendance.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'location_id',
        'type',
        'check_in_status',    // ← must be here
        'check_out_status',   // ← must be here
        'ot_seconds',         // ← must be here
        'scanned_lat',
        'scanned_lng',
        'distance_meters',
        'location_verified',
        'scanned_at',
        'ip_address',
    ];
    protected $casts = [
        'scanned_at'        => 'datetime',
        'location_verified' => 'boolean',
        'distance_meters'   => 'float',
        'ot_seconds'        => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    // Format OT seconds as HH:MM:SS
    public function otFormatted(): string
    {
        if (!$this->ot_seconds || $this->ot_seconds <= 0) return '—';

        $h = floor($this->ot_seconds / 3600);
        $m = floor(($this->ot_seconds % 3600) / 60);
        $s = $this->ot_seconds % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    // Badge color for check_in_status
    public function checkInBadge(): string
    {
        return match ($this->check_in_status) {
            'on_time' => '<span class="badge bg-success">On Time</span>',
            'late'    => '<span class="badge bg-danger">Late</span>',
            default   => '—',
        };
    }

    // Badge color for check_out_status
    public function checkOutBadge(): string
    {
        return match ($this->check_out_status) {
            'on_time' => '<span class="badge bg-success">On Time</span>',
            'early'   => '<span class="badge bg-warning text-dark">Early</span>',
            default   => '—',
        };
    }
}
