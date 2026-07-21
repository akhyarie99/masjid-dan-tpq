<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetLoan extends Model
{
    use HasUuids;

    protected $fillable = [
        'asset_id',
        'requested_by',
        'approved_by',
        'borrower_name',
        'borrower_phone',
        'purpose',
        'loan_date',
        'return_date_planned',
        'return_date_actual',
        'condition_out',
        'condition_in',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'loan_date' => 'date',
            'return_date_planned' => 'date',
            'return_date_actual' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
