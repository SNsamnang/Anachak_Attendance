<?php
// app/Models/Location.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Location extends Model
{
    protected $fillable = [
        'name', 'address', 'latitude', 'longitude',
        'radius_meters', 'qr_token', 'is_active', 'company_id',
    ];

    protected $casts = [
        'latitude'      => 'float',
        'longitude'     => 'float',
        'radius_meters' => 'integer',
        'is_active'     => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($l) {
            $l->qr_token = (string) Str::uuid();
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

    /**
     * Calculate distance in meters between two GPS coordinates
     * using the Haversine formula.
     */
    public function distanceTo(float $lat, float $lng): float
    {
        $earthRadius = 6371000; // metres

        $latFrom = deg2rad($this->latitude);
        $latTo   = deg2rad($lat);
        $lonFrom = deg2rad($this->longitude);
        $lonTo   = deg2rad($lng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) ** 2
           + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        return 2 * $earthRadius * asin(sqrt($a));
    }

    /**
     * Returns true if the given GPS point is within this location's radius.
     */
    public function isWithinRadius(float $lat, float $lng): bool
    {
        return $this->distanceTo($lat, $lng) <= $this->radius_meters;
    }
}