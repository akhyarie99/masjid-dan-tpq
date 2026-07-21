<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Imam extends Model
{
    use HasUuids;

    protected $fillable = [
        'masjid_id',
        'name',
        'phone',
        'photo',
        'type',
        'bio',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ImamSchedule::class);
    }
}
