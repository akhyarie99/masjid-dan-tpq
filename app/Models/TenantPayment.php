<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantPayment extends Model
{
    use HasUuids;

    protected $fillable = [
        'masjid_id',
        'amount',
        'period_month',
        'period_year',
        'paid_at',
        'note',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
            'period_month' => 'integer',
            'period_year' => 'integer',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(PlatformAdmin::class, 'recorded_by');
    }
}
