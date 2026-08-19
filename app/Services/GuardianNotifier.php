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
     */
    public function notify(TpqStudent $student, string $message, string $title = 'SiMasjid TPQ', string $url = '/wali/dashboard'): void
    {
        if ($student->notify_whatsapp && $student->guardian_whatsapp) {
            SendWhatsAppNotification::dispatch($student->guardian_whatsapp, $message);
        }

        if ($student->notify_webpush && $student->guardian_phone) {
            SendGuardianWebPush::dispatch($student->guardian_phone, $title, $message, $url);
        }
    }
}
