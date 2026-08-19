<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffFaceProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'masjid_id',
        'mobile_descriptor',
        'photo_path',
        'enrolled_at',
    ];

    protected function casts(): array
    {
        return [
            'mobile_descriptor' => 'array',
            'enrolled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
