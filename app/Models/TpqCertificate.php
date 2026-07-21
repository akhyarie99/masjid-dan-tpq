<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpqCertificate extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id',
        'type',
        'certificate_number',
        'issued_date',
        'achievement',
        'pdf_path',
        'issued_by',
    ];

    protected function casts(): array
    {
        return [
            'issued_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(TpqStudent::class, 'student_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
