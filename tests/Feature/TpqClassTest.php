<?php

namespace Tests\Feature;

use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqClassTeacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class TpqClassTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    public function test_admin_can_assign_teachers_to_a_class_with_active_academic_year(): void
    {
        $masjid = $this->createMasjid();
        $admin = $this->createUser($masjid, 'admin');
        $ustadz = $this->createUser($masjid, 'ustadz', ['phone' => '081234511111']);

        $year = TpqAcademicYear::create([
            'masjid_id' => $masjid->id,
            'name' => '2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.tpq.kelas.store'), [
            'name' => 'Iqra 1',
            'order' => 0,
            'capacity' => 20,
            'teacher_ids' => [$ustadz->id],
        ]);

        $response->assertRedirect();
        $class = TpqClass::first();
        $this->assertDatabaseHas('tpq_class_teachers', [
            'class_id' => $class->id,
            'academic_year_id' => $year->id,
            'teacher_id' => $ustadz->id,
        ]);
    }

    public function test_assigning_teacher_without_active_academic_year_fails_validation(): void
    {
        $masjid = $this->createMasjid();
        $admin = $this->createUser($masjid, 'admin');
        $ustadz = $this->createUser($masjid, 'ustadz', ['phone' => '081234511112']);

        $response = $this->actingAs($admin)->post(route('admin.tpq.kelas.store'), [
            'name' => 'Iqra 2',
            'order' => 0,
            'capacity' => 20,
            'teacher_ids' => [$ustadz->id],
        ]);

        $response->assertSessionHasErrors('teacher_ids');
        $this->assertDatabaseMissing('tpq_class_teachers', ['teacher_id' => $ustadz->id]);
    }

    public function test_updating_class_removes_unselected_teachers(): void
    {
        $masjid = $this->createMasjid();
        $admin = $this->createUser($masjid, 'admin');
        $ustadzA = $this->createUser($masjid, 'ustadz', ['phone' => '081234511113']);
        $ustadzB = $this->createUser($masjid, 'ustadz', ['phone' => '081234511114']);

        $year = TpqAcademicYear::create([
            'masjid_id' => $masjid->id,
            'name' => '2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
        ]);

        $class = TpqClass::create(['masjid_id' => $masjid->id, 'name' => 'Iqra 3', 'capacity' => 20]);
        TpqClassTeacher::create(['class_id' => $class->id, 'academic_year_id' => $year->id, 'teacher_id' => $ustadzA->id]);

        $response = $this->actingAs($admin)->put(route('admin.tpq.kelas.update', $class), [
            'name' => 'Iqra 3',
            'order' => 0,
            'capacity' => 20,
            'teacher_ids' => [$ustadzB->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('tpq_class_teachers', ['class_id' => $class->id, 'teacher_id' => $ustadzA->id]);
        $this->assertDatabaseHas('tpq_class_teachers', ['class_id' => $class->id, 'teacher_id' => $ustadzB->id]);
    }
}
