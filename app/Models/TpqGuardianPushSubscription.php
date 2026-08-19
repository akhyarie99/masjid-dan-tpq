<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TpqGuardianPushSubscription extends Model
{
    use HasUuids;

    protected $table = 'tpq_guardian_push_subscriptions';

    protected $fillable = [
        'guardian_phone',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
    ];
}
