<?php

namespace Tests\Feature;

use App\Models\Imam;
use App\Models\ImamSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class ImamScheduleTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    public function test_admin_can_assign_imam_to_a_prayer_schedule(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'admin');
        $imam = Imam::create([
            'masjid_id' => $masjid->id,
            'name' => 'Ust. Fulan',
            'phone' => '081234567890',
            'type' => 'tetap',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('admin.prayer.imam-schedule.store'), [
            'date' => now()->toDateString(),
            'prayer' => 'dhuhr',
            'imam_id' => $imam->id,
            'is_khatib' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('imam_schedules', [
            'masjid_id' => $masjid->id,
            'imam_id' => $imam->id,
            'prayer' => 'dhuhr',
            'is_substituted' => false,
        ]);
    }

    public function test_storing_same_date_and_prayer_twice_updates_instead_of_duplicating(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'admin');
        $imamA = Imam::create(['masjid_id' => $masjid->id, 'name' => 'Ust. A', 'type' => 'tetap', 'is_active' => true]);
        $imamB = Imam::create(['masjid_id' => $masjid->id, 'name' => 'Ust. B', 'type' => 'tetap', 'is_active' => true]);
        $date = now()->toDateString();

        $this->actingAs($user)->post(route('admin.prayer.imam-schedule.store'), [
            'date' => $date,
            'prayer' => 'fajr',
            'imam_id' => $imamA->id,
        ]);

        $this->actingAs($user)->post(route('admin.prayer.imam-schedule.store'), [
            'date' => $date,
            'prayer' => 'fajr',
            'imam_id' => $imamB->id,
        ]);

        $this->assertSame(1, ImamSchedule::where('masjid_id', $masjid->id)->count());
        $this->assertDatabaseHas('imam_schedules', ['masjid_id' => $masjid->id, 'imam_id' => $imamB->id]);
    }

    public function test_admin_can_set_a_substitute_imam(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'admin');
        $imam = Imam::create(['masjid_id' => $masjid->id, 'name' => 'Ust. Tetap', 'type' => 'tetap', 'is_active' => true]);
        $substitute = Imam::create(['masjid_id' => $masjid->id, 'name' => 'Ust. Pengganti', 'type' => 'tetap', 'is_active' => true]);

        $schedule = ImamSchedule::create([
            'masjid_id' => $masjid->id,
            'imam_id' => $imam->id,
            'date' => now()->toDateString(),
            'prayer' => 'isha',
        ]);

        $response = $this->actingAs($user)->post(route('admin.prayer.imam-schedule.substitute', $schedule), [
            'substitute_imam_id' => $substitute->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('imam_schedules', [
            'id' => $schedule->id,
            'substitute_imam_id' => $substitute->id,
            'is_substituted' => true,
        ]);
    }

    public function test_notify_all_sends_whatsapp_reminder_to_tomorrows_imam(): void
    {
        config(['services.fonnte.token' => 'fake-token']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'admin');
        $imam = Imam::create([
            'masjid_id' => $masjid->id,
            'name' => 'Ust. Besok',
            'phone' => '081298765432',
            'type' => 'tetap',
            'is_active' => true,
        ]);

        ImamSchedule::create([
            'masjid_id' => $masjid->id,
            'imam_id' => $imam->id,
            'date' => now()->addDay()->toDateString(),
            'prayer' => 'maghrib',
        ]);

        $response = $this->actingAs($user)->post(route('admin.prayer.imam-schedule.notify'));

        $response->assertRedirect();
        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.fonnte.com'));
    }
}
