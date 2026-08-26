<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Key-value generik untuk pengaturan platform-wide (bukan per-tenant) — dimulai
 * dari tarif langganan default, dipakai lagi kalau ada pengaturan global lain.
 */
class PlatformSetting extends Model
{
    use HasUuids;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
