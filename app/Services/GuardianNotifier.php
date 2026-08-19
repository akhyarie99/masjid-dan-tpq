<?php

namespace App\Services;

use App\Jobs\SendWhatsAppNotification;
use App\Models\TpqStudent;

class GuardianNotifier
{
    public function notify(TpqStudent $student, string $message): void
    {
        if ($student->notify_whatsapp && $student->guardian_whatsapp) {
            SendWhatsAppNotification::dispatch($student->guardian_whatsapp, $message);
        }
    }
}
