<?php

namespace App\Services;

use App\Models\TpqClass;
use App\Models\TpqStudent;
use App\Models\User;
use App\Notifications\MobilePushNotification;
use Illuminate\Support\Facades\Log;

/**
 * Kirim push notification via Firebase Cloud Messaging.
 *
 * No-op secara aman selama `services.firebase.credentials` belum diisi dan
 * `kreait/laravel-firebase` belum terpasang (lihat config/services.php). Setelah
 * kedua prasyarat itu ada, isi body sendViaFcm() untuk memanggil
 * app('firebase.messaging') — interface publik di service ini tidak perlu berubah.
 */
class FcmService
{
    public function isConfigured(): bool
    {
        return filled(config('services.firebase.credentials'))
            && class_exists(\Kreait\Firebase\Messaging\CloudMessage::class);
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        // Riwayat notifikasi selalu dicatat, terlepas dari status konfigurasi Firebase,
        // supaya daftar notifikasi di app mobile tetap terisi sebelum FCM diaktifkan.
        $user->notify(new MobilePushNotification($title, $body, $data));

        if ($user->fcm_token) {
            $this->sendViaFcm($user->fcm_token, $title, $body, $data);
        }
    }

    public function sendToRole(string $masjidId, string $role, string $title, string $body, array $data = []): void
    {
        User::role($role)->where('masjid_id', $masjidId)->each(
            fn (User $user) => $this->sendToUser($user, $title, $body, $data)
        );
    }

    public function notifyPresensiSubmitted(TpqClass $class, string $date, int $count, User $teacher): void
    {
        $this->sendToRole(
            $class->masjid_id,
            'admin',
            '✅ Presensi Masuk',
            "Ust. {$teacher->name} sudah input presensi kelas {$class->name} ({$count} santri) - {$date}",
            ['type' => 'presensi', 'class_id' => $class->id, 'date' => $date]
        );
    }

    public function notifyCapaianUpdated(TpqStudent $student, User $teacher, string $subject): void
    {
        $this->sendToRole(
            $student->masjid_id,
            'admin',
            '📊 Nilai Diperbarui',
            "Ust. {$teacher->name} memperbarui nilai {$subject} untuk {$student->name}",
            ['type' => 'capaian', 'student_id' => $student->id]
        );
    }

    public function remindSppToTeachers(string $masjidId, int $unpaidCount, int $month, int $year): void
    {
        if ($unpaidCount === 0) {
            return;
        }

        $this->sendToRole(
            $masjidId,
            'ustadz',
            '💳 Reminder SPP',
            "Ada {$unpaidCount} santri belum bayar SPP ".now()->setMonth($month)->format('F Y').'. Mohon sampaikan ke wali murid.',
            ['type' => 'spp_reminder', 'month' => $month, 'year' => $year]
        );
    }

    private function sendViaFcm(string $token, string $title, string $body, array $data): void
    {
        if (! $this->isConfigured()) {
            Log::debug('FCM belum dikonfigurasi, notifikasi dilewati.', compact('title', 'body'));

            return;
        }

        $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $token)
            ->withNotification(\Kreait\Firebase\Messaging\Notification::create($title, $body))
            ->withData($data);

        app('firebase.messaging')->send($message);
    }
}
