<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\BuildingProject;
use App\Models\PrayerSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class PublicPortalTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    public function test_guest_can_view_home_portal_with_todays_schedule_and_announcements(): void
    {
        $masjid = $this->createMasjid();

        PrayerSchedule::create([
            'masjid_id' => $masjid->id,
            'date' => now()->toDateString(),
            'fajr' => '04:30',
            'sunrise' => '05:45',
            'dhuhr' => '12:00',
            'asr' => '15:15',
            'maghrib' => '18:00',
            'isha' => '19:15',
        ]);

        $user = $this->createUser($masjid, 'admin');

        Announcement::create([
            'masjid_id' => $masjid->id,
            'user_id' => $user->id,
            'title' => 'Pengumuman Publik',
            'content' => 'Isi pengumuman',
            'type' => 'umum',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/Portal')
            ->where('masjid.id', $masjid->id)
            ->has('todaySchedule')
            ->where('todaySchedule.dhuhr', '12:00')
            ->has('announcements', 1)
        );
    }

    public function test_guest_can_view_home_portal_when_no_schedule_exists_yet(): void
    {
        $this->createMasjid();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/Portal')
            ->where('todaySchedule', null)
        );
    }

    public function test_guest_can_view_financial_report_summary(): void
    {
        $this->createMasjid();

        $response = $this->get(route('public.finance'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Public/FinancialReport'));
    }

    public function test_guest_can_view_imam_schedule_for_current_month(): void
    {
        $this->createMasjid();

        $response = $this->get(route('public.imam'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Public/ImamSchedule'));
    }

    public function test_guest_can_view_building_project_progress(): void
    {
        $masjid = $this->createMasjid();

        $project = BuildingProject::create([
            'masjid_id' => $masjid->id,
            'name' => 'Renovasi Aula',
            'description' => 'Perluasan aula utama',
            'target_amount' => 100000000,
            'collected_amount' => 25000000,
            'physical_progress_percent' => 10,
            'start_date' => now()->toDateString(),
            'status' => 'ongoing',
        ]);

        $response = $this->get(route('public.wakaf-proyek', $project));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/BuildingProject')
            ->where('project.funding_percent', 25)
        );
    }

    public function test_guest_cannot_access_admin_dashboard_from_public_portal(): void
    {
        $this->createMasjid();

        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }
}
