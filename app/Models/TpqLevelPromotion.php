<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat kenaikan jenjang mengaji santri (Iqro jilid ke jilid, sampai
 * Al-Qur'an) — dicatat tiap kali TpqStudent::current_method/current_jilid
 * berubah lewat aksi "Naik Jilid" yang disengaja.
 */
class TpqLevelPromotion extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id',
        'from_method',
        'from_jilid',
        'to_method',
        'to_jilid',
        'promoted_by',
        'note',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(TpqStudent::class, 'student_id');
    }

    public function promoter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'promoted_by');
    }
}
