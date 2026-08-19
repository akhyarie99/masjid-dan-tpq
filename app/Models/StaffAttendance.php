<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'masjid_id',
        'date',
        'clock_in',
        'clock_in_lat',
        'clock_in_lng',
        'clock_in_photo',
        'clock_in_location_id',
        'clock_in_is_mock_location',
        'clock_in_gps_accuracy',
        'clock_in_device_info',
        'clock_in_liveness_verified',
        'clock_out',
        'clock_out_lat',
        'clock_out_lng',
        'clock_out_photo',
        'clock_out_location_id',
        'clock_out_is_mock_location',
        'clock_out_gps_accuracy',
        'clock_out_device_info',
        'clock_out_liveness_verified',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'clock_in_is_mock_location' => 'boolean',
            'clock_out_is_mock_location' => 'boolean',
            'clock_in_liveness_verified' => 'boolean',
            'clock_out_liveness_verified' => 'boolean',
            'clock_in_device_info' => 'array',
            'clock_out_device_info' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clockInLocation(): BelongsTo
    {
        return $this->belongsTo(MasjidLocation::class, 'clock_in_location_id');
    }

    public function clockOutLocation(): BelongsTo
    {
        return $this->belongsTo(MasjidLocation::class, 'clock_out_location_id');
    }
}
