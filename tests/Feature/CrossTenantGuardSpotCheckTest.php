<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\JamaahProfile;
use App\Models\KasAccount;
use App\Models\Majelis;
use App\Models\MajelisMember;
use App\Models\TpqSppBill;
use App\Models\TpqStudent;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

/**
 * Spot-check sementara: buktikan guard authorizeSameMasjid benar-benar menutup
 * akses lintas tenant lewat request HTTP nyata (router + middleware + session),
 * bukan cuma ada di kode.
 */
class CrossTenantGuardSpotCheckTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    public function test_transaction_edit_update_destroy_approve_are_blocked_across_tenants(): void
    {
        // Tenant B dibuat duluan; createMasjid() memaksa root URL ke subdomain
        // masjid terakhir, jadi masjid A harus dibuat belakangan supaya request
        // benar-benar datang dari host tenant A.
        $masjidB = $this->createMasjid(['name' => 'Masjid B']);
        $kasB = KasAccount::create([
            'masjid_id' => $masjidB->id, 'name' => 'Kas B', 'type' => 'cash',
            'initial_balance' => 0, 'is_active' => true,
        ]);
        $catB = TransactionCategory::create([
            'masjid_id' => $masjidB->id, 'name' => 'Infaq B', 'type' => 'income', 'is_system' => true,
        ]);
        $trxB = Transaction::create([
            'masjid_id' => $masjidB->id, 'kas_account_id' => $kasB->id, 'category_id' => $catB->id,
            'reference_number' => 'TRX-B-1', 'type' => 'income', 'amount' => 500000,
            'description' => 'Rahasia tenant B', 'status' => 'pending',
            'transaction_date' => now()->toDateString(),
        ]);

        $masjidA = $this->createMasjid(['name' => 'Masjid A']);
        $userA = $this->createUser($masjidA, 'super_admin');

        $this->actingAs($userA)->get(route('admin.finance.transaksi.edit', $trxB))->assertNotFound();
        $this->actingAs($userA)->put(route('admin.finance.transaksi.update', $trxB), [
            'kas_account_id' => $kasB->id, 'category_id' => $catB->id, 'type' => 'income',
            'amount' => 1, 'description' => 'dibajak', 'transaction_date' => now()->toDateString(),
        ])->assertNotFound();
        $this->actingAs($userA)->post(route('admin.finance.transaksi.approve', $trxB), [
            'status' => 'approved',
        ])->assertNotFound();
        $this->actingAs($userA)->delete(route('admin.finance.transaksi.destroy', $trxB))->assertNotFound();

        // Tidak satu pun request di atas boleh mengubah data tenant B.
        $this->assertDatabaseHas('transactions', [
            'id' => $trxB->id, 'amount' => 500000, 'status' => 'pending',
            'description' => 'Rahasia tenant B',
        ]);
    }

    public function test_asset_and_user_are_blocked_across_tenants_but_own_records_still_work(): void
    {
        $masjidB = $this->createMasjid(['name' => 'Masjid B']);
        $catB = AssetCategory::create(['masjid_id' => $masjidB->id, 'name' => 'Elektronik']);
        $assetB = Asset::create([
            'masjid_id' => $masjidB->id, 'category_id' => $catB->id, 'asset_code' => 'ELE/2026/001',
            'name' => 'Sound System B', 'location' => 'Ruang Utama', 'condition' => 'baik', 'status' => 'aktif',
        ]);
        $userB = $this->createUser($masjidB, 'super_admin');

        $masjidA = $this->createMasjid(['name' => 'Masjid A']);
        $userA = $this->createUser($masjidA, 'super_admin');
        $catA = AssetCategory::create(['masjid_id' => $masjidA->id, 'name' => 'Elektronik']);
        $assetA = Asset::create([
            'masjid_id' => $masjidA->id, 'category_id' => $catA->id, 'asset_code' => 'ELE/2026/002',
            'name' => 'Sound System A', 'location' => 'Ruang Utama', 'condition' => 'baik', 'status' => 'aktif',
        ]);

        // Lintas tenant: ditolak.
        $this->actingAs($userA)->get(route('admin.asset.inventaris.edit', $assetB))->assertNotFound();
        $this->actingAs($userA)->get(route('admin.asset.inventaris.qr', $assetB))->assertNotFound();
        $this->actingAs($userA)->delete(route('admin.asset.inventaris.destroy', $assetB))->assertNotFound();
        $this->actingAs($userA)->get(route('admin.settings.pengguna.edit', $userB))->assertNotFound();
        $this->actingAs($userA)->delete(route('admin.settings.pengguna.destroy', $userB))->assertNotFound();

        $this->assertDatabaseHas('assets', ['id' => $assetB->id, 'name' => 'Sound System B']);
        $this->assertDatabaseHas('users', ['id' => $userB->id]);

        // Kontrol positif: aset milik tenant sendiri tetap bisa diakses, jadi
        // guard-nya memang memfilter pemilik, bukan memblokir semua.
        $this->actingAs($userA)->get(route('admin.asset.inventaris.edit', $assetA))->assertOk();
    }

    public function test_parent_relation_guard_blocks_cross_tenant_spp_bill(): void
    {
        // TpqSppBill tidak punya kolom masjid_id — kepemilikan lewat student.
        $masjidB = $this->createMasjid(['name' => 'Masjid B']);
        $studentB = TpqStudent::create([
            'masjid_id' => $masjidB->id, 'nis' => '260001', 'name' => 'Santri B',
            'gender' => 'L', 'guardian_phone' => '081200000001', 'status' => 'aktif',
            'entry_date' => now()->toDateString(),
        ]);
        $billB = TpqSppBill::create([
            'student_id' => $studentB->id, 'year' => now()->year, 'month' => now()->month,
            'amount' => 100000, 'paid_amount' => 0, 'status' => 'unpaid',
        ]);

        $masjidA = $this->createMasjid(['name' => 'Masjid A']);
        $userA = $this->createUser($masjidA, 'super_admin');

        $this->actingAs($userA)->post(route('admin.tpq.spp.pay', $billB), [
            'amount' => 100000, 'paid_date' => now()->toDateString(),
        ])->assertNotFound();

        $this->assertDatabaseHas('tpq_spp_bills', [
            'id' => $billB->id, 'paid_amount' => 0, 'status' => 'unpaid',
        ]);
        $this->assertDatabaseCount('tpq_spp_payments', 0);
    }

    public function test_jamaah_and_nested_majelis_member_guards(): void
    {
        $masjidB = $this->createMasjid(['name' => 'Masjid B']);
        $jamaahB = JamaahProfile::create([
            'masjid_id' => $masjidB->id, 'name' => 'Jamaah B', 'phone' => '081300000001', 'status' => 'aktif',
        ]);
        $majelisB = Majelis::create(['masjid_id' => $masjidB->id, 'name' => 'Majelis B', 'is_active' => true]);
        $memberB = MajelisMember::create([
            'majelis_id' => $majelisB->id, 'name' => 'Anggota B', 'joined_date' => now()->toDateString(),
        ]);

        $masjidA = $this->createMasjid(['name' => 'Masjid A']);
        $userA = $this->createUser($masjidA, 'super_admin');
        $majelisA = Majelis::create(['masjid_id' => $masjidA->id, 'name' => 'Majelis A', 'is_active' => true]);

        $this->actingAs($userA)->get(route('admin.jamaah.edit', $jamaahB))->assertNotFound();
        $this->actingAs($userA)->get(route('admin.jamaah.card', $jamaahB))->assertNotFound();
        $this->actingAs($userA)->get(route('admin.study.majelis.show', $majelisB))->assertNotFound();

        // Binding bersarang: majelis milik tenant A, tapi anggota milik tenant B.
        // Tanpa cross-check anggota-vs-majelis ini akan lolos.
        $this->actingAs($userA)->delete(route('admin.study.majelis.anggota.destroy', [
            'majelis' => $majelisA, 'anggota' => $memberB,
        ]))->assertNotFound();

        $this->assertDatabaseHas('jamaah_profiles', ['id' => $jamaahB->id]);
        $this->assertDatabaseHas('majelis_members', ['id' => $memberB->id, 'name' => 'Anggota B']);
    }
}
