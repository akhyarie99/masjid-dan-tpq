<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TpqSppBill extends Model
{
    use HasUuids;

    protected $appends = ['proof_file_url'];

    protected $fillable = [
        'student_id',
        'year',
        'month',
        'amount',
        'status',
        'paid_amount',
        'is_scholarship',
        'reminder_sent',
        'proof_file',
        'proof_status',
        'proof_rejection_reason',
        'proof_submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'is_scholarship' => 'boolean',
            'reminder_sent' => 'boolean',
            'proof_submitted_at' => 'datetime',
        ];
    }

    protected function proofFileUrl(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->proof_file) {
                return null;
            }
            $root = app()->runningInConsole() ? config('app.url') : request()->getSchemeAndHttpHost();

            return "{$root}/storage/{$this->proof_file}";
        });
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
