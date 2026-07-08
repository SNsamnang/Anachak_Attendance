<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtStatus extends Model
{
    protected $fillable = [
        'employee_id',
        'eligible',
    ];

    protected $casts = [
        'eligible' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
