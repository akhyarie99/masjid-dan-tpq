<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpqReportCard extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id',
        'class_id',
        'semester_id',
        'average_score',
        'grade_rank',
        'present_count',
        'sick_count',
        'permission_count',
        'absent_count',
        'homeroom_notes',
        'head_notes',
        'promotion_status',
        'pdf_path',
        'is_distributed',
        'distributed_at',
    ];

    protected function casts(): array
    {
        return [
            'average_score' => 'decimal:2',
            'is_distributed' => 'boolean',
            'distributed_at' => 'datetime',
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

    public function semester(): BelongsTo
    {
        return $this->belongsTo(TpqSemester::class, 'semester_id');
    }
}
