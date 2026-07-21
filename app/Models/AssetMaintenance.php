<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenance extends Model
{
    use HasUuids;

    protected $fillable = [
        'asset_id',
        'reported_by',
        'handled_by',
        'type',
        'description',
        'action_taken',
        'cost',
        'status',
        'scheduled_date',
        'completed_date',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'scheduled_date' => 'date',
            'completed_date' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
