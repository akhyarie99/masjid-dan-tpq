<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpqSppPayment extends Model
{
    use HasUuids;

    protected $fillable = [
        'bill_id',
        'received_by',
        'amount',
        'paid_date',
        'payment_method',
        'receipt_number',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_date' => 'date',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(TpqSppBill::class, 'bill_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
