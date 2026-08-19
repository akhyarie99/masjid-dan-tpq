<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpqDailyProgress extends Model
{
    use HasUuids;

    protected $table = 'tpq_daily_progress';

    protected $fillable = [
        'student_id',
        'class_id',
        'date',
        'method',
        'jilid',
        'halaman',
        'surah',
        'ayat_awal',
        'ayat_akhir',
        'keterangan',
        'catatan',
        'recorded_by',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'notified_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(TpqStudent::class, 'student_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(TpqClass::class, 'class_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function summary(): string
    {
        return $this->method === 'iqro'
            ? "Iqro Jilid {$this->jilid} halaman {$this->halaman}"
            : "Al-Qur'an {$this->surah} ayat {$this->ayat_awal}-{$this->ayat_akhir}";
    }
}
