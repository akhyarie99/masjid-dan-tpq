<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, HasUuids, LogsActivity, Notifiable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logAll()->logExcept(['password', 'remember_token', 'last_login_at']);
    }

    protected $fillable = [
        'masjid_id',
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'fcm_token',
        'is_active',
        'last_login_at',
        'birth_date',
        'address',
        'gender',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'birth_date' => 'date',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    /**
     * Bukan asset()/APP_URL — sama seperti Masjid::logoUrl(), di dunia
     * multi-tenant tiap tenant diakses dari host yang beda-beda, jadi URL
     * storage harus ikut host request yang sedang berjalan.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->avatar) {
                return null;
            }

            $root = app()->runningInConsole() ? config('app.url') : request()->getSchemeAndHttpHost();

            return "{$root}/storage/{$this->avatar}";
        });
    }
}
