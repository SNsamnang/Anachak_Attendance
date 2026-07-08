<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'employee_id',
        'name',
        'token',
        'status',
        'fingerprint',
        'requested_at',
        'approved_at',
        'rejected_reason',
        'last_used_at',
    ];

    protected $casts = [
        'last_used_at'  => 'datetime',
        'requested_at'  => 'datetime',
        'approved_at'   => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'approved' => '<span class="badge bg-success">Approved</span>',
            'pending'  => '<span class="badge bg-warning text-dark">Pending</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>',
            default    => '<span class="badge bg-secondary">Unknown</span>',
        };
    }
}
