<?php

namespace App\Services;

use App\Jobs\SendGuardianWebPush;
use App\Jobs\SendWhatsAppNotification;
use App\Models\TpqStudent;

class GuardianNotifier
{
    /**
     * Kirim notifikasi ke wali murid lewat channel yang mereka aktifkan (WA dan/atau
     * web push). $title dipakai untuk push saja — WA selalu berupa pesan teks tunggal.
     *
     * Push diarahkan ke halaman santri yang bersangkutan (bukan dashboard umum) supaya
     * wali dengan lebih dari satu anak langsung melihat progres anak yang dimaksud saat
     * notifikasi di-klik, tidak perlu cari-cari sendiri di antara anak-anaknya.
     */
    public function notify(TpqStudent $student, string $message, string $title = 'SiMasjid TPQ', ?string $url = null): void
    {
        $url ??= "/wali/santri/{$student->id}";

        if ($student->notify_whatsapp && $student->guardian_whatsapp) {
            SendWhatsAppNotification::dispatch($student->guardian_whatsapp, $message);
        }

        if ($student->notify_webpush) {
            foreach ($student->waliAccounts as $waliAccount) {
                SendGuardianWebPush::dispatch($waliAccount->id, $title, $message, $url);
            }
        }
    }
}
