<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpqSetting extends Model
{
    use HasUuids;

    protected $table = 'tpq_settings';

    protected $fillable = [
        'masjid_id',
        'name',
        'sk_number',
        'head_name',
        'head_nip',
        'head_signature',
        'logo',
        'address',
        'grade_scale',
        'min_attendance_percent',
        'min_avg_grade',
    ];

    protected function casts(): array
    {
        return [
            'grade_scale' => 'array',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }
}
