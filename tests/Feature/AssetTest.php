<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    public function test_admin_can_create_asset_with_auto_generated_code(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'admin');
        $category = AssetCategory::create(['masjid_id' => $masjid->id, 'name' => 'Elektronik']);

        $response = $this->actingAs($user)->post(route('admin.asset.inventaris.store'), [
            'category_id' => $category->id,
            'name' => 'Sound System',
            'location' => 'Aula Utama',
            'condition' => 'baik',
            'status' => 'aktif',
        ]);

        $response->assertRedirect(route('admin.asset.inventaris.index'));

        $asset = Asset::first();
        $this->assertNotNull($asset);
        $this->assertStringStartsWith('ELE/'.now()->year.'/', $asset->asset_code);
    }

    public function test_qr_code_page_renders_for_asset(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'admin');
        $category = AssetCategory::create(['masjid_id' => $masjid->id, 'name' => 'Elektronik']);
        $asset = Asset::create([
            'masjid_id' => $masjid->id,
            'category_id' => $category->id,
            'name' => 'Proyektor',
            'asset_code' => 'ELE/2026/001',
            'location' => 'Aula',
            'condition' => 'baik',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($user)->get(route('admin.asset.inventaris.qr', $asset));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Asset/QrLabel'));
    }

    public function test_loan_approval_marks_asset_as_dipinjam_and_return_restores_it(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'admin');
        $category = AssetCategory::create(['masjid_id' => $masjid->id, 'name' => 'Perlengkapan']);
        $asset = Asset::create([
            'masjid_id' => $masjid->id,
            'category_id' => $category->id,
            'name' => 'Meja Lipat',
            'asset_code' => 'PER/2026/001',
            'location' => 'Gudang',
            'condition' => 'baik',
            'status' => 'aktif',
        ]);

        $this->actingAs($user)->post(route('admin.asset.peminjaman.store'), [
            'asset_id' => $asset->id,
            'borrower_name' => 'Budi',
            'borrower_phone' => '081234567890',
            'purpose' => 'Acara pengajian',
            'loan_date' => now()->toDateString(),
            'return_date_planned' => now()->addDays(3)->toDateString(),
            'condition_out' => 'baik',
        ]);

        $loan = \App\Models\AssetLoan::first();

        $this->actingAs($user)->post(route('admin.asset.peminjaman.approve', $loan));
        $this->assertSame('dipinjam', $asset->fresh()->status);
        $this->assertSame('approved', $loan->fresh()->status);

        $this->actingAs($user)->post(route('admin.asset.peminjaman.return', $loan), [
            'condition_in' => 'baik',
        ]);

        $this->assertSame('aktif', $asset->fresh()->status);
        $this->assertSame('returned', $loan->fresh()->status);
    }

    public function test_maintenance_reminder_command_sends_notification_for_due_assets(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'admin', ['phone' => '081211110000']);
        $category = AssetCategory::create(['masjid_id' => $masjid->id, 'name' => 'AC']);

        Asset::create([
            'masjid_id' => $masjid->id,
            'category_id' => $category->id,
            'name' => 'AC Ruang Utama',
            'asset_code' => 'AC/2026/001',
            'location' => 'Ruang Utama',
            'condition' => 'baik',
            'status' => 'aktif',
            'next_maintenance_date' => now()->addDays(3)->toDateString(),
        ]);

        $this->artisan('assets:maintenance-reminders')->assertSuccessful();
    }
}
