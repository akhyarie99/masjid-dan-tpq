<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpqStudentClass extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_year_id',
        'is_promoted',
    ];

    protected function casts(): array
    {
        return [
            'is_promoted' => 'boolean',
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

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(TpqAcademicYear::class, 'academic_year_id');
    }
}
