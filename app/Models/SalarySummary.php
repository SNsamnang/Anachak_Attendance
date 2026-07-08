<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySummary extends Model
{
    protected $fillable = [
        'employee_id',
        'date_of_record',
        'dayoff',
        'dayoff_amount',
        'leave_day',
        'leave_day_amount',
        'late',
        'late_amount',
        'early_leave',
        'early_leave_amount',
        'ot',
        'ot_amount',
        'unused_free_days',
        'free_dayoff_bonus',
    ];

    protected $casts = [
        'date_of_record'     => 'date',
        'dayoff_amount'      => 'decimal:2',
        'leave_day_amount'   => 'decimal:2',
        'late'               => 'decimal:2',
        'late_amount'        => 'decimal:2',
        'early_leave'        => 'decimal:2',
        'early_leave_amount' => 'decimal:2',
        'ot'                 => 'decimal:2',
        'ot_amount'          => 'decimal:2',
        'unused_free_days'   => 'integer',
        'free_dayoff_bonus'  => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
