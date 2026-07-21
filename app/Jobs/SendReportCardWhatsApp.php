<?php

namespace App\Jobs;

use App\Models\TpqReportCard;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendReportCardWhatsApp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public TpqReportCard $reportCard) {}

    public function handle(WhatsAppService $whatsAppService): void
    {
        $this->reportCard->loadMissing(['student', 'semester.academicYear']);
        $student = $this->reportCard->student;

        if (empty($student->guardian_whatsapp) || empty($this->reportCard->pdf_path)) {
            return;
        }

        $academicYear = $this->reportCard->semester->academicYear->name;
        $attendancePercent = ($this->reportCard->present_count + $this->reportCard->sick_count + $this->reportCard->permission_count + $this->reportCard->absent_count) > 0
            ? round($this->reportCard->present_count / ($this->reportCard->present_count + $this->reportCard->sick_count + $this->reportCard->permission_count + $this->reportCard->absent_count) * 100)
            : 100;

        $message = "Assalamu'alaikum Bapak/Ibu Wali Murid *{$student->father_name}*,\n\n"
            ."Raport semester {$this->reportCard->semester->number} tahun ajaran {$academicYear} "
            ."untuk ananda *{$student->name}* sudah tersedia.\n\n"
            ."📊 Nilai Rata-rata: *{$this->reportCard->average_score}*\n"
            ."✅ Kehadiran: *{$this->reportCard->present_count} hari* ({$attendancePercent}%)\n"
            .($this->reportCard->promotion_status ? "📈 Status: *".strtoupper($this->reportCard->promotion_status)."*\n\n" : "\n")
            ."Silakan hubungi TPQ untuk mengambil salinan cetak raport.\n\n"
            ."Jazakallahu khairan 🙏";

        $whatsAppService->sendDocument($student->guardian_whatsapp, $message, "public/{$this->reportCard->pdf_path}");

        $this->reportCard->update(['is_distributed' => true, 'distributed_at' => now()]);
    }
}
