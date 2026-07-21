<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpqClassTeacher extends Model
{
    use HasUuids;

    protected $fillable = [
        'class_id',
        'academic_year_id',
        'teacher_id',
        'is_homeroom',
    ];

    protected function casts(): array
    {
        return [
            'is_homeroom' => 'boolean',
        ];
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(TpqClass::class, 'class_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(TpqAcademicYear::class, 'academic_year_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
