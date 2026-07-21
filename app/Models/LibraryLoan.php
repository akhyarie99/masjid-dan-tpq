<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryLoan extends Model
{
    use HasUuids;

    protected $fillable = [
        'book_id',
        'borrower_name',
        'borrower_phone',
        'loan_date',
        'return_date_planned',
        'return_date_actual',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'loan_date' => 'date',
            'return_date_planned' => 'date',
            'return_date_actual' => 'date',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(LibraryBook::class, 'book_id');
    }
}
