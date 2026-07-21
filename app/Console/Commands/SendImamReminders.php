<?php

namespace App\Console\Commands;

use App\Models\ImamSchedule;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendImamReminders extends Command
{
    protected $signature = 'imam:send-reminders';

    protected $description = 'Kirim reminder WA H-1 ke imam yang bertugas besok';

    public function handle(WhatsAppService $whatsAppService): int
    {
        $tomorrow = Carbon::tomorrow();

        $schedules = ImamSchedule::with(['imam:id,name,phone', 'substituteImam:id,name,phone', 'masjid:id,name'])
            ->whereDate('date', $tomorrow->toDateString())
            ->where('reminder_sent', false)
            ->get();

        $labels = [
            'fajr' => 'Subuh', 'dhuhr' => 'Dzuhur', 'asr' => 'Ashar', 'maghrib' => 'Maghrib',
            'isha' => 'Isya', 'jumuah' => 'Jumat', 'tarawih' => 'Tarawih',
        ];

        $sent = 0;

        foreach ($schedules as $schedule) {
            $imam = $schedule->is_substituted ? $schedule->substituteImam : $schedule->imam;

            if (! $imam || ! $imam->phone) {
                continue;
            }

            $message = <<<TEXT
            Assalamu'alaikum Ust. {$imam->name},

            Mengingatkan jadwal imam besok:
            📅 {$tomorrow->translatedFormat('l, d F Y')}
            🕌 Shalat: {$labels[$schedule->prayer]}
            🏛️ {$schedule->masjid->name}

            Jazakallahu khairan.
            TEXT;

            if ($whatsAppService->send($imam->phone, $message)) {
                $schedule->update(['reminder_sent' => true]);
                $sent++;
            }
        }

        $this->info("Reminder terkirim ke {$sent} imam.");

        return self::SUCCESS;
    }
}
