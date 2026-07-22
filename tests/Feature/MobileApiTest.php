<?php

namespace Tests\Feature;

use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqClassTeacher;
use App\Models\TpqStudent;
use App\Models\TpqStudentClass;
use App\Models\TpqSubject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    private function makeClassWithStudent($masjid, $teacher = null): array
    {
        $year = TpqAcademicYear::create([
            'masjid_id' => $masjid->id,
            'name' => '2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
        ]);

        $class = TpqClass::create(['masjid_id' => $masjid->id, 'name' => 'Iqra 1', 'capacity' => 20]);

        if ($teacher) {
            TpqClassTeacher::create(['class_id' => $class->id, 'academic_year_id' => $year->id, 'teacher_id' => $teacher->id]);
        }

        $student = TpqStudent::create([
            'masjid_id' => $masjid->id,
            'nis' => '260001',
            'name' => 'Ahmad',
            'gender' => 'L',
            'guardian_name' => 'Bapak Ahmad',
            'guardian_phone' => '081200001111',
            'guardian_whatsapp' => '081200001111',
            'status' => 'aktif',
            'entry_date' => now()->toDateString(),
        ]);

        TpqStudentClass::create(['student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id]);

        return [$class, $student, $year];
    }

    public function test_ustadz_can_login_with_phone_and_receives_token(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'ustadz', ['phone' => '081234500001']);

        $response = $this->postJson(route('mobile.login'), [
            'phone' => '081234500001',
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token', 'user' => ['id', 'name', 'role', 'masjid' => ['id', 'name']]]);
        $this->assertSame('ustadz', $response->json('user.role'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $masjid = $this->createMasjid();
        $this->createUser($masjid, 'ustadz', ['phone' => '081234500002']);

        $response = $this->postJson(route('mobile.login'), [
            'phone' => '081234500002',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_rejects_role_without_mobile_access(): void
    {
        $masjid = $this->createMasjid();
        $this->createUser($masjid, 'bendahara', ['phone' => '081234500003']);

        $response = $this->postJson(route('mobile.login'), [
            'phone' => '081234500003',
            'password' => 'password',
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson(route('mobile.dashboard'));

        $response->assertStatus(401);
    }

    public function test_ustadz_only_sees_assigned_classes(): void
    {
        $masjid = $this->createMasjid();
        $teacher = $this->createUser($masjid, 'ustadz', ['phone' => '081234500004']);
        [$myClass] = $this->makeClassWithStudent($masjid, $teacher);

        // Kelas lain yang tidak diampu ustadz ini.
        TpqClass::create(['masjid_id' => $masjid->id, 'name' => 'Iqra 2', 'capacity' => 20]);

        $response = $this->actingAs($teacher)->getJson(route('mobile.presensi.kelas'));

        $response->assertOk();
        $response->assertJsonCount(1, 'classes');
        $this->assertSame($myClass->id, $response->json('classes.0.id'));
    }

    public function test_presensi_submit_within_radius_saves_attendance_and_notifies_admin(): void
    {
        $masjid = $this->createMasjid(['latitude' => -7.4894, 'longitude' => 109.0044]);
        $admin = $this->createUser($masjid, 'admin', ['phone' => '081234500005']);
        $teacher = $this->createUser($masjid, 'ustadz', ['phone' => '081234500006']);
        [$class, $student] = $this->makeClassWithStudent($masjid, $teacher);

        $response = $this->actingAs($teacher)->postJson(route('mobile.presensi.submit', $class), [
            'date' => now()->toDateString(),
            'latitude' => -7.4895,
            'longitude' => 109.0045,
            'accuracy' => 10,
            'attendances' => [
                ['student_id' => $student->id, 'status' => 'hadir'],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tpq_attendances', [
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'hadir',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
        ]);
    }

    public function test_presensi_submit_too_far_from_masjid_is_rejected(): void
    {
        $masjid = $this->createMasjid(['latitude' => -7.4894, 'longitude' => 109.0044]);
        $teacher = $this->createUser($masjid, 'ustadz', ['phone' => '081234500007']);
        [$class, $student] = $this->makeClassWithStudent($masjid, $teacher);

        $response = $this->actingAs($teacher)->postJson(route('mobile.presensi.submit', $class), [
            'date' => now()->toDateString(),
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'accuracy' => 10,
            'attendances' => [
                ['student_id' => $student->id, 'status' => 'hadir'],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('tpq_attendances', ['student_id' => $student->id]);
    }

    public function test_presensi_submit_alfa_dispatches_whatsapp_alert_to_guardian(): void
    {
        config(['services.fonnte.token' => 'fake-token']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $masjid = $this->createMasjid(['latitude' => -7.4894, 'longitude' => 109.0044]);
        $teacher = $this->createUser($masjid, 'ustadz', ['phone' => '081234500008']);
        [$class, $student] = $this->makeClassWithStudent($masjid, $teacher);

        $this->actingAs($teacher)->postJson(route('mobile.presensi.submit', $class), [
            'date' => now()->toDateString(),
            'latitude' => -7.4894,
            'longitude' => 109.0044,
            'accuracy' => 10,
            'attendances' => [
                ['student_id' => $student->id, 'status' => 'alfa'],
            ],
        ])->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.fonnte.com')
            && $request['target'] === $student->guardian_whatsapp);
    }

    public function test_ustadz_cannot_submit_attendance_for_unassigned_class(): void
    {
        $masjid = $this->createMasjid(['latitude' => -7.4894, 'longitude' => 109.0044]);
        $teacher = $this->createUser($masjid, 'ustadz', ['phone' => '081234500009']);
        [$class, $student] = $this->makeClassWithStudent($masjid, null);

        $response = $this->actingAs($teacher)->postJson(route('mobile.presensi.submit', $class), [
            'date' => now()->toDateString(),
            'latitude' => -7.4894,
            'longitude' => 109.0044,
            'accuracy' => 10,
            'attendances' => [
                ['student_id' => $student->id, 'status' => 'hadir'],
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_capaian_input_nilai_saves_grade(): void
    {
        $masjid = $this->createMasjid();
        $teacher = $this->createUser($masjid, 'ustadz', ['phone' => '081234500010']);
        [$class, $student, $year] = $this->makeClassWithStudent($masjid, $teacher);
        $subject = TpqSubject::create(['masjid_id' => $masjid->id, 'name' => 'Tahsin']);
        $semester = \App\Models\TpqSemester::create([
            'academic_year_id' => $year->id,
            'number' => 1,
            'name' => 'Ganjil',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($teacher)->postJson(route('mobile.capaian.nilai', $student), [
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'score' => 88,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('tpq_grades', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'score' => 88,
            'grade_letter' => 'B',
        ]);
    }

    public function test_spp_kelas_endpoint_returns_bill_status_for_current_month(): void
    {
        $masjid = $this->createMasjid();
        $teacher = $this->createUser($masjid, 'ustadz', ['phone' => '081234500011']);
        [$class, $student] = $this->makeClassWithStudent($masjid, $teacher);

        \App\Models\TpqSppBill::create([
            'student_id' => $student->id,
            'year' => now()->year,
            'month' => now()->month,
            'amount' => 50000,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($teacher)->getJson(route('mobile.spp.kelas', $class));

        $response->assertOk();
        $response->assertJsonPath('students.0.bill.status', 'unpaid');
    }
}
