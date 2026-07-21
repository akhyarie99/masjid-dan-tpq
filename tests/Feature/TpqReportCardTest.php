<?php

namespace Tests\Feature;

use App\Jobs\SendReportCardWhatsApp;
use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqReportCard;
use App\Models\TpqSemester;
use App\Models\TpqSetting;
use App\Models\TpqStudent;
use App\Models\TpqStudentClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class TpqReportCardTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    private function makeSemesterWithStudent($masjid): array
    {
        TpqSetting::create([
            'masjid_id' => $masjid->id, 'name' => 'TPQ Test', 'head_name' => 'Ustadz Kepala',
            'min_attendance_percent' => 75, 'min_avg_grade' => 70,
        ]);

        $year = TpqAcademicYear::create([
            'masjid_id' => $masjid->id, 'name' => '2026/2027',
            'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'is_active' => true,
        ]);
        $semester = TpqSemester::create([
            'academic_year_id' => $year->id, 'number' => 2, 'name' => 'Semester 2',
            'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'is_active' => true,
        ]);
        $class = TpqClass::create(['masjid_id' => $masjid->id, 'name' => 'Iqra 1', 'capacity' => 20]);
        $student = TpqStudent::create([
            'masjid_id' => $masjid->id, 'nis' => '260001', 'name' => 'Ahmad', 'gender' => 'L',
            'guardian_phone' => '081200001111', 'guardian_whatsapp' => '081200001111',
            'father_name' => 'Bapak Ahmad', 'status' => 'aktif', 'entry_date' => now()->toDateString(),
        ]);
        TpqStudentClass::create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id]);

        return [$semester, $student, $class];
    }

    public function test_generate_report_card_creates_record_and_pdf(): void
    {
        Storage::fake('public');

        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'admin');
        [$semester, $student] = $this->makeSemesterWithStudent($masjid);

        $response = $this->actingAs($user)->post(route('admin.tpq.report.generate', [$semester, $student]));

        $response->assertRedirect();

        $reportCard = TpqReportCard::where('student_id', $student->id)->where('semester_id', $semester->id)->first();
        $this->assertNotNull($reportCard);
        $this->assertNotNull($reportCard->pdf_path);
        Storage::disk('public')->assertExists($reportCard->pdf_path);
    }

    public function test_promotion_status_tinggal_when_average_grade_below_kkm(): void
    {
        Storage::fake('public');

        $masjid = $this->createMasjid();
        [$semester, $student] = $this->makeSemesterWithStudent($masjid);

        // Semua hadir agar kehadiran 100% >= 75%, tanpa nilai (rata-rata 0 < KKM 70 -> tinggal)
        $recorder = $this->createUser($masjid, 'ustadz', ['phone' => '081200009999']);

        for ($i = 0; $i < 5; $i++) {
            \App\Models\TpqAttendance::create([
                'student_id' => $student->id,
                'class_id' => TpqClass::first()->id,
                'date' => now()->startOfYear()->addDays($i)->toDateString(),
                'status' => 'hadir',
                'recorded_by' => $recorder->id,
            ]);
        }

        $service = app(\App\Services\TpqReportCardService::class);
        $reportCard = $service->generate($student, $semester);

        $this->assertSame('tinggal', $reportCard->promotion_status);
    }

    public function test_send_whatsapp_dispatches_job(): void
    {
        Queue::fake();
        Storage::fake('public');

        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'admin');
        [$semester, $student] = $this->makeSemesterWithStudent($masjid);

        $service = app(\App\Services\TpqReportCardService::class);
        $reportCard = $service->generate($student, $semester);

        $response = $this->actingAs($user)->post(route('admin.tpq.report.send-wa', $reportCard));

        $response->assertRedirect();
        Queue::assertPushed(SendReportCardWhatsApp::class, fn ($job) => $job->reportCard->id === $reportCard->id);
    }
}
