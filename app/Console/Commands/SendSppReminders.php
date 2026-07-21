<?php

namespace App\Console\Commands;

use App\Models\TpqSppBill;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendSppReminders extends Command
{
    protected $signature = 'tpq:spp-reminders';

    protected $description = 'Kirim reminder WA untuk tagihan SPP yang sudah lewat 7 hari dan belum terbayar';

    public function handle(WhatsAppService $whatsAppService): int
    {
        $bills = TpqSppBill::with('student')
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('reminder_sent', false)
            ->where('created_at', '<=', now()->subDays(7))
            ->get();

        $sent = 0;

        foreach ($bills as $bill) {
            if (empty($bill->student->guardian_whatsapp)) {
                continue;
            }

            $monthName = Carbon::create($bill->year, $bill->month, 1)->translatedFormat('F Y');
            $sisa = $bill->amount - $bill->paid_amount;

            $message = "Assalamu'alaikum Bapak/Ibu Wali {$bill->student->name},\n\n"
                ."SPP TPQ bulan {$monthName} sebesar Rp".number_format($sisa, 0, ',', '.')." belum terbayar.\n\n"
                ."Jazakumullahu khairan.";

            if ($whatsAppService->send($bill->student->guardian_whatsapp, $message)) {
                $bill->update(['reminder_sent' => true]);
                $sent++;
            }
        }

        $this->info("Reminder SPP terkirim ke {$sent} wali murid.");

        return self::SUCCESS;
    }
}
