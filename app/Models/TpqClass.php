<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TpqClass extends Model
{
    use HasUuids;

    protected $table = 'tpq_classes';

    protected $fillable = [
        'masjid_id',
        'name',
        'order',
        'capacity',
        'room',
        'schedule',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'schedule' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(TpqClassTeacher::class, 'class_id');
    }

    public function studentClasses(): HasMany
    {
        return $this->hasMany(TpqStudentClass::class, 'class_id');
    }
}
