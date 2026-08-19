<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaliAccount extends Model
{
    use HasUuids;

    protected $fillable = [
        'phone',
        'password',
        'name',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(TpqStudent::class, 'wali_account_student', 'wali_account_id', 'student_id');
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(TpqGuardianPushSubscription::class);
    }

    /**
     * Satu akun per nomor HP unik — kalau nomor itu sudah punya akun (mis. anak
     * pertama sudah didaftarkan), anak baru cukup disambungkan, TIDAK mereset
     * password keluarga yang sudah ada. Password akun baru default ke NIS santri,
     * sama seperti konvensi guardian_password bawaan (lihat TpqStudentController::store).
     */
    public static function syncForStudent(TpqStudent $student): void
    {
        if (! $student->guardian_phone) {
            return;
        }

        $account = static::firstOrCreate(
            ['phone' => $student->guardian_phone],
            ['password' => $student->guardian_password ?: $student->nis],
        );

        $student->waliAccounts()->syncWithoutDetaching([$account->id]);

        // Nomor HP wali diganti admin (mis. koreksi data) — putuskan tautan ke akun
        // lama supaya wali sebelumnya tidak lagi bisa lihat data santri ini.
        $student->waliAccounts()
            ->where('phone', '!=', $student->guardian_phone)
            ->get()
            ->each(fn (self $old) => $student->waliAccounts()->detach($old->id));
    }
}
