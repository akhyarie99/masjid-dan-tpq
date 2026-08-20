<?php

namespace Tests\Feature;

use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqStudent;
use App\Models\TpqStudentClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class TpqAttendanceTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    private function makeClassWithStudent($masjid): array
    {
        $year = TpqAcademicYear::create([
            'masjid_id' => $masjid->id,
            'name' => '2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
        ]);

        $class = TpqClass::create(['masjid_id' => $masjid->id, 'name' => 'Iqra 1', 'capacity' => 20]);

        $student = TpqStudent::create([
            'masjid_id' => $masjid->id,
            'nis' => '260001',
            'name' => 'Ahmad',
            'gender' => 'L',
            'guardian_phone' => '081200001111',
            'status' => 'aktif',
            'entry_date' => now()->toDateString(),
        ]);

        TpqStudentClass::create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id]);

        return [$class, $student, $year];
    }

    public function test_admin_can_input_attendance(): void
    {
        // Sengaja pakai 'admin', bukan 'ustadz' — sejak pembatasan role ustadz
        // (hanya TPQ > Harian), absensi kelas reguler jadi kewenangan admin/tpq.manage,
        // ustadz mencatat progres mengaji lewat Harian (tpq.daily-progress.*), bukan di sini.
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'admin');
        [$class, $student] = $this->makeClassWithStudent($masjid);

        $response = $this->actingAs($user)->post(route('admin.tpq.attendance.store', $class), [
            'date' => now()->toDateString(),
            'attendances' => [
                ['student_id' => $student->id, 'status' => 'hadir', 'notes' => null],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tpq_attendances', [
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'hadir',
        ]);
    }

    public function test_recap_calculates_present_count_and_percentage(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'ustadz');
        [$class, $student] = $this->makeClassWithStudent($masjid);

        $month = now()->month;
        $year = now()->year;

        foreach ([1, 2, 3] as $day) {
            \App\Models\TpqAttendance::create([
                'student_id' => $student->id,
                'class_id' => $class->id,
                'date' => now()->startOfMonth()->addDays($day - 1)->toDateString(),
                'status' => 'hadir',
                'recorded_by' => $user->id,
            ]);
        }

        \App\Models\TpqAttendance::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'date' => now()->startOfMonth()->addDays(3)->toDateString(),
            'status' => 'alfa',
            'recorded_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('admin.tpq.attendance.recap', ['class' => $class->id, 'month' => $month, 'year' => $year]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('recap.0.present_count', 3)
        );
    }

    public function test_alfa_status_is_recorded_for_absent_student(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'admin');
        [$class, $student] = $this->makeClassWithStudent($masjid);

        $this->actingAs($user)->post(route('admin.tpq.attendance.store', $class), [
            'date' => now()->toDateString(),
            'attendances' => [
                ['student_id' => $student->id, 'status' => 'alfa', 'notes' => 'Tanpa keterangan'],
            ],
        ]);

        $this->assertDatabaseHas('tpq_attendances', [
            'student_id' => $student->id,
            'status' => 'alfa',
        ]);
    }
}
