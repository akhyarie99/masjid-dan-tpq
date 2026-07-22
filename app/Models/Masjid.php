<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Masjid extends Model
{
    use HasUuids;

    protected $appends = ['logo_url'];

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
        'bank_accounts',
        'logo',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'bank_accounts' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(fn () => $this->logo ? asset('storage/'.$this->logo) : null);
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
