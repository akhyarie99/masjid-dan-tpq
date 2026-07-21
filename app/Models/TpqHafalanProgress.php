<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpqHafalanProgress extends Model
{
    use HasUuids;

    protected $table = 'tpq_hafalan_progress';

    protected $fillable = [
        'student_id',
        'surah_number',
        'surah_name',
        'total_ayah',
        'memorized_ayah',
        'status',
        'memorized_date',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'memorized_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(TpqStudent::class, 'student_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
