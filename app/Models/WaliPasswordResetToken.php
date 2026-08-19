<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WaliPasswordResetToken extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'wali_password_reset_tokens';

    protected $primaryKey = 'wali_account_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'wali_account_id',
        'token',
        'created_at',
    ];

    private const EXPIRY_MINUTES = 15;

    /**
     * Request baru menimpa token lama (satu link aktif per akun) — token
     * disimpan ter-hash, hanya versi plain yang dikirim lewat WA/email.
     */
    public static function createFor(WaliAccount $account): string
    {
        $plain = Str::random(64);

        static::updateOrCreate(
            ['wali_account_id' => $account->id],
            ['token' => Hash::make($plain), 'created_at' => now()],
        );

        return $plain;
    }

    public static function verify(WaliAccount $account, string $plainToken): bool
    {
        $record = static::find($account->id);

        if (! $record || $record->created_at->addMinutes(self::EXPIRY_MINUTES)->isPast()) {
            return false;
        }

        return Hash::check($plainToken, $record->token);
    }

    public static function invalidateFor(WaliAccount $account): void
    {
        static::where('wali_account_id', $account->id)->delete();
    }
}
