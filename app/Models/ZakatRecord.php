<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZakatRecord extends Model
{
    use HasUuids;

    protected $fillable = [
        'masjid_id',
        'type',
        'payer_name',
        'payer_phone',
        'dependents',
        'amount_per_person',
        'total_amount',
        'payment_type',
        'rice_kg',
        'year',
        'ramadhan',
    ];

    protected function casts(): array
    {
        return [
            'amount_per_person' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'rice_kg' => 'decimal:2',
            'ramadhan' => 'boolean',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }
}
