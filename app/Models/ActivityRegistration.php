<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityRegistration extends Model
{
    use HasUuids;

    protected $fillable = [
        'activity_id',
        'name',
        'phone',
        'email',
        'is_attended',
        'attended_at',
        'reminder_sent',
    ];

    protected function casts(): array
    {
        return [
            'is_attended' => 'boolean',
            'attended_at' => 'datetime',
            'reminder_sent' => 'boolean',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
