<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QurbanRegistration extends Model
{
    use HasUuids;

    protected $fillable = [
        'masjid_id',
        'shohibul_name',
        'phone',
        'animal_type',
        'share_count',
        'animal_name',
        'year',
        'amount',
        'payment_status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }
}
