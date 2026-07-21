<?php

namespace App\Jobs;

use App\Models\Donation;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDonationReceipt implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Donation $donation) {}

    public function handle(WhatsAppService $whatsAppService): void
    {
        if (empty($this->donation->donor_phone)) {
            return;
        }

        $masjid = $this->donation->masjid;
        $amount = number_format((float) $this->donation->amount, 0, ',', '.');

        $message = <<<TEXT
        Assalamu'alaikum {$this->donation->donor_name},

        Jazakumullahu khairan atas donasi Anda sebesar Rp{$amount} untuk {$masjid->name}.
        Semoga menjadi amal jariyah yang berkah.

        Barakallahu fiikum.
        TEXT;

        $whatsAppService->send($this->donation->donor_phone, $message);

        $this->donation->update(['receipt_sent' => true]);
    }
}
