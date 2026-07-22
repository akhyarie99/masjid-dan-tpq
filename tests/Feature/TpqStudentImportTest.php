<?php

namespace Tests\Feature;

use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqStudent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class TpqStudentImportTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    private function csvFile(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('santri.csv', $content);
    }

    public function test_admin_can_import_students_from_csv(): void
    {
        $masjid = $this->createMasjid();
        $admin = $this->createUser($masjid, 'admin');

        $csv = "nis,name,gender,guardian_phone\n"
            ."260101,Ahmad Fauzi,L,081200001111\n"
            ."260102,Siti Aminah,P,081200002222\n";

        $response = $this->actingAs($admin)->post(route('admin.tpq.santri.import'), [
            'file' => $this->csvFile($csv),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tpq_students', ['masjid_id' => $masjid->id, 'nis' => '260101', 'name' => 'Ahmad Fauzi', 'gender' => 'L']);
        $this->assertDatabaseHas('tpq_students', ['masjid_id' => $masjid->id, 'nis' => '260102', 'name' => 'Siti Aminah', 'gender' => 'P']);
        $this->assertSame(2, TpqStudent::where('masjid_id', $masjid->id)->count());
    }

    public function test_row_missing_required_field_is_skipped(): void
    {
        $masjid = $this->createMasjid();
        $admin = $this->createUser($masjid, 'admin');

        $csv = "nis,name,gender,guardian_phone\n"
            ."260201,Lengkap,L,081200003333\n"
            ."260202,,L,081200004444\n"; // name kosong -> dilewati

        $this->actingAs($admin)->post(route('admin.tpq.santri.import'), [
            'file' => $this->csvFile($csv),
        ]);

        $this->assertSame(1, TpqStudent::where('masjid_id', $masjid->id)->count());
        $this->assertDatabaseMissing('tpq_students', ['nis' => '260202']);
    }

    public function test_nis_is_auto_generated_when_blank(): void
    {
        $masjid = $this->createMasjid();
        $admin = $this->createUser($masjid, 'admin');

        $csv = "name,gender,guardian_phone\nTanpa Nis,L,081200005555\n";

        $this->actingAs($admin)->post(route('admin.tpq.santri.import'), [
            'file' => $this->csvFile($csv),
        ]);

        $student = TpqStudent::where('masjid_id', $masjid->id)->first();
        $this->assertNotNull($student);
        $this->assertNotEmpty($student->nis);
        $this->assertTrue(Hash::check($student->nis, $student->guardian_password));
    }

    public function test_student_is_assigned_to_matching_class_when_active_year_exists(): void
    {
        $masjid = $this->createMasjid();
        $admin = $this->createUser($masjid, 'admin');

        TpqAcademicYear::create([
            'masjid_id' => $masjid->id,
            'name' => '2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
        ]);
        TpqClass::create(['masjid_id' => $masjid->id, 'name' => 'Iqra 1', 'capacity' => 20]);

        $csv = "name,gender,guardian_phone,kelas\nAda Kelas,L,081200006666,Iqra 1\n";

        $this->actingAs($admin)->post(route('admin.tpq.santri.import'), [
            'file' => $this->csvFile($csv),
        ]);

        $student = TpqStudent::where('name', 'Ada Kelas')->first();
        $this->assertNotNull($student);
        $this->assertDatabaseHas('tpq_student_classes', ['student_id' => $student->id]);
    }
}
