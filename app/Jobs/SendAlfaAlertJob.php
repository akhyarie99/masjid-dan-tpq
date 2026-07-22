<?php

namespace App\Jobs;

use App\Models\TpqStudent;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SendAlfaAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $studentId, public string $date) {}

    public function handle(WhatsAppService $whatsAppService): void
    {
        $student = TpqStudent::find($this->studentId);
        $phone = $student?->guardian_whatsapp ?: $student?->guardian_phone;

        if (! $student || ! $phone) {
            return;
        }

        $date = Carbon::parse($this->date)->translatedFormat('l, d F Y');

        $message = "Assalamu'alaikum Bapak/Ibu Wali Murid *{$student->guardian_name}*,\n\n"
            ."Kami informasikan bahwa ananda *{$student->name}* tidak hadir (alfa) di TPQ pada {$date}.\n\n"
            .'Mohon konfirmasi ke ustadz/ustadzah apabila ada kendala. Jazakallahu khairan 🙏';

        $whatsAppService->send($phone, $message);
    }
}
