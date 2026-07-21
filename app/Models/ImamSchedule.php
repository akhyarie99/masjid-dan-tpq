<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImamSchedule extends Model
{
    use HasUuids;

    protected $fillable = [
        'masjid_id',
        'imam_id',
        'substitute_imam_id',
        'date',
        'prayer',
        'is_khatib',
        'khutbah_theme',
        'reminder_sent',
        'is_substituted',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_khatib' => 'boolean',
            'reminder_sent' => 'boolean',
            'is_substituted' => 'boolean',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    public function imam(): BelongsTo
    {
        return $this->belongsTo(Imam::class);
    }

    public function substituteImam(): BelongsTo
    {
        return $this->belongsTo(Imam::class, 'substitute_imam_id');
    }
}
