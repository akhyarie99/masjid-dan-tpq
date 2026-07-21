<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpqGrade extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id',
        'class_id',
        'subject_id',
        'semester_id',
        'score',
        'grade_letter',
        'description',
        'graded_by',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
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

    public function subject(): BelongsTo
    {
        return $this->belongsTo(TpqSubject::class, 'subject_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(TpqSemester::class, 'semester_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
