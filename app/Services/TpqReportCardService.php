<?php

namespace App\Services;

use App\Models\TpqClassTeacher;
use App\Models\TpqGrade;
use App\Models\TpqHafalanProgress;
use App\Models\TpqReportCard;
use App\Models\TpqSemester;
use App\Models\TpqSetting;
use App\Models\TpqStudent;
use App\Models\TpqStudentClass;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class TpqReportCardService
{
    public function generate(TpqStudent $student, TpqSemester $semester): TpqReportCard
    {
        $studentClass = TpqStudentClass::where('student_id', $student->id)
            ->where('academic_year_id', $semester->academic_year_id)
            ->with('class')
            ->firstOrFail();

        $class = $studentClass->class;
        $setting = TpqSetting::where('masjid_id', $student->masjid_id)->first();

        // 1-2. Nilai per mapel + rata-rata tertimbang
        $grades = TpqGrade::with('subject')
            ->where('student_id', $student->id)
            ->where('semester_id', $semester->id)
            ->get();

        $totalWeight = $grades->sum(fn ($g) => $g->subject->weight);
        $averageScore = $totalWeight > 0
            ? round($grades->sum(fn ($g) => $g->score * $g->subject->weight) / $totalWeight, 2)
            : 0;

        // 3. Rekap kehadiran
        $attendances = $student->attendances()
            ->whereBetween('date', [$semester->start_date, $semester->end_date])
            ->get();

        $presentCount = $attendances->where('status', 'hadir')->count();
        $sickCount = $attendances->where('status', 'sakit')->count();
        $permissionCount = $attendances->where('status', 'izin')->count();
        $absentCount = $attendances->where('status', 'alfa')->count();
        $totalDays = $attendances->count();
        $attendancePercent = $totalDays > 0 ? ($presentCount / $totalDays) * 100 : 100;

        // 4. Progress hafalan terbaru dalam semester ini
        $hafalanThisSemester = TpqHafalanProgress::where('student_id', $student->id)
            ->where('status', 'hafal')
            ->whereBetween('memorized_date', [$semester->start_date, $semester->end_date])
            ->get();

        // 5. Catatan wali kelas (dipertahankan jika sudah ada)
        $existing = TpqReportCard::where('student_id', $student->id)->where('semester_id', $semester->id)->first();

        // 6. Status kenaikan kelas
        $minAttendance = $setting?->min_attendance_percent ?? 75;
        $minAvgGrade = $setting?->min_avg_grade ?? 70;
        $isFinalSemester = $semester->number === 2;

        $promotionStatus = null;
        if ($isFinalSemester) {
            $promotionStatus = ($attendancePercent >= $minAttendance && $averageScore >= $minAvgGrade) ? 'naik' : 'tinggal';
        }

        // 7. Simpan
        $reportCard = TpqReportCard::updateOrCreate(
            ['student_id' => $student->id, 'semester_id' => $semester->id],
            [
                'class_id' => $class->id,
                'average_score' => $averageScore,
                'present_count' => $presentCount,
                'sick_count' => $sickCount,
                'permission_count' => $permissionCount,
                'absent_count' => $absentCount,
                'homeroom_notes' => $existing?->homeroom_notes,
                'head_notes' => $existing?->head_notes,
                'promotion_status' => $promotionStatus,
            ]
        );

        // 8-9. Generate & simpan PDF
        $pdfPath = $this->generatePdf($student, $semester, $class, $reportCard, $grades, $hafalanThisSemester, $setting);
        $reportCard->update(['pdf_path' => $pdfPath]);

        return $reportCard->fresh();
    }

    private function generatePdf(
        TpqStudent $student,
        TpqSemester $semester,
        \App\Models\TpqClass $class,
        TpqReportCard $reportCard,
        $grades,
        $hafalanThisSemester,
        ?TpqSetting $setting
    ): string {
        $homeroomTeacher = TpqClassTeacher::where('class_id', $class->id)
            ->where('academic_year_id', $semester->academic_year_id)
            ->where('is_homeroom', true)
            ->with('teacher:id,name')
            ->first()?->teacher;

        $hafalanSummary = $hafalanThisSemester->isEmpty()
            ? 'Belum ada surah yang dihafalkan pada semester ini.'
            : $hafalanThisSemester->pluck('surah_name')->implode(', ');

        $pdf = Pdf::loadView('pdf.report-card', [
            'student' => $student,
            'class' => $class,
            'semester' => $semester,
            'academicYear' => $semester->academicYear->name,
            'reportCard' => $reportCard,
            'grades' => $grades,
            'hafalanSummary' => $hafalanSummary,
            'homeroomTeacher' => $homeroomTeacher?->name ?? '-',
            'homeroomSignature' => null,
            'headSignature' => null,
            'tpqName' => $setting?->name ?? 'TPQ',
            'tpqHeadName' => $setting?->head_name ?? '-',
            'tpqLogo' => $setting?->logo,
            'masjidName' => $student->masjid->name,
            'masjidAddress' => $student->masjid->address,
            'masjidCity' => $student->masjid->name,
            'nextClass' => $class->name,
        ]);

        $path = "tpq/report-cards/{$semester->id}/{$student->id}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}
