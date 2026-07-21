<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TpqSppBill extends Model
{
    use HasUuids;

    protected $fillable = [
        'student_id',
        'year',
        'month',
        'amount',
        'status',
        'paid_amount',
        'is_scholarship',
        'reminder_sent',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'is_scholarship' => 'boolean',
            'reminder_sent' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(TpqStudent::class, 'student_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TpqSppPayment::class, 'bill_id');
    }
}
