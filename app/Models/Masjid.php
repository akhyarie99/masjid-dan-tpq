<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Masjid extends Model
{
    use HasUuids;

    protected $appends = ['logo_url', 'background_url'];

    protected $fillable = [
        'name',
        'slug',
        'address',
        'latitude',
        'longitude',
        'phone',
        'email',
        'website',
        'instagram',
        'youtube',
        'vision',
        'mission',
        'prayer_method',
        'iqomah_offset_minutes',
        'bank_accounts',
        'logo',
        'background_image',
        'is_active',
        'custom_domain',
        'custom_domain_verified_at',
        'custom_domain_verification_token',
        'subscription_status',
        'theme_color',
        'monthly_fee',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'bank_accounts' => 'array',
            'is_active' => 'boolean',
            'iqomah_offset_minutes' => 'integer',
            'custom_domain_verified_at' => 'datetime',
            'monthly_fee' => 'decimal:2',
        ];
    }

    /**
     * Tarif bulanan yang benar-benar berlaku untuk tenant ini — tarif khusus
     * kalau diisi, kalau tidak jatuh balik ke tarif default platform.
     */
    public function effectiveMonthlyFee(): float
    {
        return (float) ($this->monthly_fee ?? PlatformSetting::get('default_monthly_fee', '0'));
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TenantPayment::class);
    }

    /**
     * Bukan asset()/APP_URL — di dunia multi-tenant tiap tenant diakses dari host
     * yang beda-beda (subdomain atau custom domain sendiri), jadi URL storage
     * harus ikut host request yang sedang berjalan, bukan satu APP_URL global
     * yang cuma benar untuk domain pusat.
     */
    /**
     * slug dipakai sebagai segmen subdomain ("{slug}.central-domain"), dan
     * hostname itu case-insensitive — ResolveTenant middleware selalu
     * lowercase host sebelum lookup, jadi slug juga wajib selalu lowercase
     * di database supaya keduanya tetap cocok.
     */
    protected function slug(): Attribute
    {
        return Attribute::set(fn (string $value) => strtolower($value));
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->logo ? $this->storageUrl($this->logo) : null);
    }

    protected function backgroundUrl(): Attribute
    {
        return Attribute::get(fn () => $this->background_image ? $this->storageUrl($this->background_image) : null);
    }

    private function storageUrl(string $path): string
    {
        $root = app()->runningInConsole() ? config('app.url') : request()->getSchemeAndHttpHost();

        return "{$root}/storage/{$path}";
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function prayerSchedules(): HasMany
    {
        return $this->hasMany(PrayerSchedule::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function kasAccounts(): HasMany
    {
        return $this->hasMany(KasAccount::class);
    }
}
