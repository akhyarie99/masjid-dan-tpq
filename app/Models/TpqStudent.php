<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TpqStudent extends Model
{
    use HasUuids, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnlyDirty()->logAll()->logExcept(['guardian_password']);
    }

    protected static function booted(): void
    {
        // Sinkron otomatis di SATU tempat (bukan diulang di setiap controller/import
        // yang membuat/mengubah santri) supaya akun wali tidak pernah ketinggalan
        // saat guardian_phone diisi/diganti, dari jalur manapun.
        static::saved(function (self $student) {
            if ($student->wasRecentlyCreated || $student->wasChanged('guardian_phone')) {
                WaliAccount::syncForStudent($student);
            }
        });
    }

    protected $fillable = [
        'masjid_id',
        'nis',
        'name',
        'nik',
        'birth_place',
        'birth_date',
        'gender',
        'address',
        'photo',
        'father_name',
        'mother_name',
        'guardian_name',
        'guardian_phone',
        'guardian_whatsapp',
        'notify_whatsapp',
        'notify_webpush',
        'guardian_password',
        'parent_occupation',
        'status',
        'entry_date',
        'exit_date',
        'notes',
    ];

    protected $hidden = [
        'guardian_password',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'entry_date' => 'date',
            'exit_date' => 'date',
            'guardian_password' => 'hashed',
            'notify_whatsapp' => 'boolean',
            'notify_webpush' => 'boolean',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    public function studentClasses(): HasMany
    {
        return $this->hasMany(TpqStudentClass::class, 'student_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(TpqAttendance::class, 'student_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(TpqGrade::class, 'student_id');
    }

    public function hafalanProgress(): HasMany
    {
        return $this->hasMany(TpqHafalanProgress::class, 'student_id');
    }

    public function dailyProgress(): HasMany
    {
        return $this->hasMany(TpqDailyProgress::class, 'student_id');
    }

    public function waliAccounts(): BelongsToMany
    {
        return $this->belongsToMany(WaliAccount::class, 'wali_account_student', 'student_id', 'wali_account_id');
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(TpqReportCard::class, 'student_id');
    }

    public function sppBills(): HasMany
    {
        return $this->hasMany(TpqSppBill::class, 'student_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(TpqCertificate::class, 'student_id');
    }
}
