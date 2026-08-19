<?php

namespace App\Console\Commands;

use App\Models\TpqSppBill;
use App\Services\GuardianNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendSppReminders extends Command
{
    protected $signature = 'tpq:spp-reminders';

    protected $description = 'Kirim reminder WA/push untuk tagihan SPP yang sudah lewat 7 hari dan belum terbayar';

    public function handle(GuardianNotifier $notifier): int
    {
        $bills = TpqSppBill::with('student')
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('reminder_sent', false)
            ->where('created_at', '<=', now()->subDays(7))
            ->get();

        $sent = 0;

        foreach ($bills as $bill) {
            if (! $bill->student || (! $bill->student->notify_whatsapp && ! $bill->student->notify_webpush)) {
                continue;
            }

            $monthName = Carbon::create($bill->year, $bill->month, 1)->translatedFormat('F Y');
            $sisa = $bill->amount - $bill->paid_amount;

            $message = "Assalamu'alaikum Bapak/Ibu Wali {$bill->student->name},\n\n"
                ."SPP TPQ bulan {$monthName} sebesar Rp".number_format($sisa, 0, ',', '.')." belum terbayar.\n\n"
                ."Jazakumullahu khairan.";

            $notifier->notify($bill->student, $message, 'Reminder SPP', '/wali/dashboard');
            $bill->update(['reminder_sent' => true]);
            $sent++;
        }

        $this->info("Reminder SPP terkirim ke {$sent} wali murid.");

        return self::SUCCESS;
    }
}
