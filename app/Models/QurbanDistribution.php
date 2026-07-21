<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QurbanDistribution extends Model
{
    use HasUuids;

    protected $fillable = [
        'masjid_id',
        'jamaah_id',
        'recipient_name',
        'address',
        'weight_kg',
        'package_count',
        'year',
        'distributed_at',
        'distributed_by',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'distributed_at' => 'datetime',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    public function jamaah(): BelongsTo
    {
        return $this->belongsTo(JamaahProfile::class, 'jamaah_id');
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }
}
