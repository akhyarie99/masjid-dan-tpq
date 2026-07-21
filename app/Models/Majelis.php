<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Majelis extends Model
{
    use HasUuids;

    protected $table = 'majelis';

    protected $fillable = [
        'masjid_id',
        'name',
        'description',
        'leader_name',
        'leader_phone',
        'meeting_schedule',
        'location',
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

    public function members(): HasMany
    {
        return $this->hasMany(MajelisMember::class);
    }
}
