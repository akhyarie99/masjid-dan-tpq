<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialProgramRecipient extends Model
{
    use HasUuids;

    protected $fillable = [
        'program_id',
        'jamaah_id',
        'name',
        'phone',
        'address',
        'aid_type',
        'amount',
        'distributed_at',
        'receipt_signature',
        'distributed_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'distributed_at' => 'datetime',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(SocialProgram::class, 'program_id');
    }

    public function jamaah(): BelongsTo
    {
        return $this->belongsTo(JamaahProfile::class, 'jamaah_id');
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }
}
