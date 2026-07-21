<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuildingProject extends Model
{
    use HasUuids;

    protected $fillable = [
        'masjid_id',
        'name',
        'description',
        'target_amount',
        'collected_amount',
        'physical_progress_percent',
        'start_date',
        'target_end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'collected_amount' => 'decimal:2',
            'start_date' => 'date',
            'target_end_date' => 'date',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(BuildingProjectUpdate::class, 'project_id')->latest('created_at');
    }

    public function fundingPercent(): float
    {
        if ((float) $this->target_amount <= 0) {
            return 0;
        }

        return round(((float) $this->collected_amount / (float) $this->target_amount) * 100, 1);
    }
}
