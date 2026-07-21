<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JamaahProfile extends Model
{
    use HasUuids, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logAll();
    }

    protected $fillable = [
        'masjid_id',
        'user_id',
        'name',
        'nik',
        'birth_date',
        'phone',
        'address',
        'rt',
        'rw',
        'photo',
        'status',
        'tags',
        'receive_notification',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'tags' => 'array',
            'receive_notification' => 'boolean',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
