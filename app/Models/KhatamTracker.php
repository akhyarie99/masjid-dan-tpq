<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KhatamTracker extends Model
{
    use HasUuids;

    protected $fillable = [
        'masjid_id',
        'participant_name',
        'phone',
        'year',
        'completed_juz',
        'is_completed',
        'completed_date',
    ];

    protected function casts(): array
    {
        return [
            'completed_juz' => 'array',
            'is_completed' => 'boolean',
            'completed_date' => 'date',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }
}
