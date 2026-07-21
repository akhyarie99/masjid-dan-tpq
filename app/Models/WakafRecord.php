<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WakafRecord extends Model
{
    use HasUuids;

    protected $fillable = [
        'masjid_id',
        'wakif_name',
        'wakif_phone',
        'type',
        'description',
        'estimated_value',
        'certificate_number',
        'status',
        'donated_date',
    ];

    protected function casts(): array
    {
        return [
            'estimated_value' => 'decimal:2',
            'donated_date' => 'date',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }
}
