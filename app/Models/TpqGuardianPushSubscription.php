<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TpqGuardianPushSubscription extends Model
{
    use HasUuids;

    protected $table = 'tpq_guardian_push_subscriptions';

    protected $fillable = [
        'wali_account_id',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
    ];

    public function waliAccount(): BelongsTo
    {
        return $this->belongsTo(WaliAccount::class);
    }
}
