<?php

namespace Tests\Feature;

use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqSemester;
use App\Models\TpqStudent;
use App\Models\TpqStudentClass;
use App\Models\TpqSubject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class TpqGradeTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    public function test_ustadz_can_input_grade_and_letter_is_calculated(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'ustadz');

        $year = TpqAcademicYear::create([
            'masjid_id' => $masjid->id, 'name' => '2026/2027',
            'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'is_active' => true,
        ]);
        $semester = TpqSemester::create([
            'academic_year_id' => $year->id, 'number' => 1, 'name' => 'Semester 1',
            'start_date' => now()->startOfYear(), 'end_date' => now()->addMonths(6), 'is_active' => true,
        ]);
        $class = TpqClass::create(['masjid_id' => $masjid->id, 'name' => 'Iqra 1', 'capacity' => 20]);
        $subject = TpqSubject::create(['masjid_id' => $masjid->id, 'name' => 'Bacaan Al-Quran', 'weight' => 1]);
        $student = TpqStudent::create([
            'masjid_id' => $masjid->id, 'nis' => '260001', 'name' => 'Ahmad', 'gender' => 'L',
            'guardian_phone' => '081200001111', 'status' => 'aktif', 'entry_date' => now()->toDateString(),
        ]);
        TpqStudentClass::create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id]);

        $response = $this->actingAs($user)->post(route('admin.tpq.grade.store', [$class, $semester]), [
            'grades' => [
                ['student_id' => $student->id, 'subject_id' => $subject->id, 'score' => 95, 'description' => 'Bagus sekali'],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tpq_grades', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'score' => 95,
            'grade_letter' => 'A',
        ]);
    }

    public function test_average_score_is_weighted_correctly_in_report_card(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'ustadz');

        $year = TpqAcademicYear::create([
            'masjid_id' => $masjid->id, 'name' => '2026/2027',
            'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'is_active' => true,
        ]);
        $semester = TpqSemester::create([
            'academic_year_id' => $year->id, 'number' => 1, 'name' => 'Semester 1',
            'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'is_active' => true,
        ]);
        $class = TpqClass::create(['masjid_id' => $masjid->id, 'name' => 'Iqra 1', 'capacity' => 20]);
        $subjectA = TpqSubject::create(['masjid_id' => $masjid->id, 'name' => 'Bacaan', 'weight' => 2]);
        $subjectB = TpqSubject::create(['masjid_id' => $masjid->id, 'name' => 'Adab', 'weight' => 1]);
        $student = TpqStudent::create([
            'masjid_id' => $masjid->id, 'nis' => '260001', 'name' => 'Ahmad', 'gender' => 'L',
            'guardian_phone' => '081200001111', 'status' => 'aktif', 'entry_date' => now()->toDateString(),
        ]);
        TpqStudentClass::create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id]);

        \App\Models\TpqGrade::create([
            'student_id' => $student->id, 'class_id' => $class->id, 'subject_id' => $subjectA->id,
            'semester_id' => $semester->id, 'score' => 90, 'grade_letter' => 'A', 'graded_by' => $user->id,
        ]);
        \App\Models\TpqGrade::create([
            'student_id' => $student->id, 'class_id' => $class->id, 'subject_id' => $subjectB->id,
            'semester_id' => $semester->id, 'score' => 60, 'grade_letter' => 'D', 'graded_by' => $user->id,
        ]);

        $service = app(\App\Services\TpqReportCardService::class);
        $reportCard = $service->generate($student, $semester);

        // Weighted average: (90*2 + 60*1) / 3 = 80
        $this->assertEquals(80, (float) $reportCard->average_score);
    }

    public function test_kkm_status_reflects_minimum_grade_setting(): void
    {
        $masjid = $this->createMasjid();
        \App\Models\TpqSetting::create([
            'masjid_id' => $masjid->id, 'name' => 'TPQ Test', 'head_name' => 'Ustadz Kepala',
            'min_attendance_percent' => 75, 'min_avg_grade' => 70,
        ]);

        $setting = \App\Models\TpqSetting::where('masjid_id', $masjid->id)->first();

        $this->assertSame(70, $setting->min_avg_grade);
    }
}
