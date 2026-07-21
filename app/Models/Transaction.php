<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Transaction extends Model
{
    use HasUuids, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logAll();
    }

    protected $fillable = [
        'masjid_id',
        'kas_account_id',
        'category_id',
        'user_id',
        'approved_by',
        'reference_number',
        'type',
        'amount',
        'description',
        'proof_file',
        'status',
        'transaction_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    public function kasAccount(): BelongsTo
    {
        return $this->belongsTo(KasAccount::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function createFromDonation(Donation $donation): self
    {
        $kasAccount = KasAccount::where('masjid_id', $donation->masjid_id)->where('is_active', true)->first();

        $category = TransactionCategory::firstOrCreate([
            'masjid_id' => $donation->masjid_id,
            'name' => 'Donasi',
            'type' => 'income',
        ], ['is_system' => true]);

        return self::create([
            'masjid_id' => $donation->masjid_id,
            'kas_account_id' => $kasAccount?->id,
            'category_id' => $category->id,
            'user_id' => null,
            'approved_by' => null,
            'reference_number' => 'DNS-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'type' => 'income',
            'amount' => $donation->amount,
            'description' => 'Donasi digital — '.($donation->purpose ?? 'Umum').' dari '.($donation->donor_name ?? 'Hamba Allah'),
            'status' => 'approved',
            'transaction_date' => $donation->paid_at?->toDateString() ?? now()->toDateString(),
        ]);
    }
}
