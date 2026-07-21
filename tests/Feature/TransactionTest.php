<?php

namespace Tests\Feature;

use App\Models\KasAccount;
use App\Models\TransactionCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    private function makeKasAndCategory($masjid): array
    {
        $kas = KasAccount::create([
            'masjid_id' => $masjid->id,
            'name' => 'Kas Tunai',
            'type' => 'cash',
            'initial_balance' => 0,
            'is_active' => true,
        ]);

        $category = TransactionCategory::create([
            'masjid_id' => $masjid->id,
            'name' => 'Infaq Jumat',
            'type' => 'income',
            'is_system' => true,
        ]);

        return [$kas, $category];
    }

    public function test_bendahara_created_transaction_is_auto_approved(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'bendahara');
        [$kas, $category] = $this->makeKasAndCategory($masjid);

        $response = $this->actingAs($user)->post(route('admin.finance.transaksi.store'), [
            'kas_account_id' => $kas->id,
            'category_id' => $category->id,
            'type' => 'income',
            'amount' => 150000,
            'description' => 'Infaq Jumat minggu ini',
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('admin.finance.transaksi.index'));
        $this->assertDatabaseHas('transactions', [
            'masjid_id' => $masjid->id,
            'amount' => 150000,
            'status' => 'approved',
        ]);
    }

    public function test_non_approver_created_transaction_is_pending(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'sekretaris');
        [$kas, $category] = $this->makeKasAndCategory($masjid);

        $this->actingAs($user)->post(route('admin.finance.transaksi.store'), [
            'kas_account_id' => $kas->id,
            'category_id' => $category->id,
            'type' => 'income',
            'amount' => 75000,
            'description' => 'Donasi jamaah',
            'transaction_date' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('transactions', [
            'masjid_id' => $masjid->id,
            'amount' => 75000,
            'status' => 'pending',
        ]);
    }

    public function test_bendahara_can_approve_pending_transaction(): void
    {
        $masjid = $this->createMasjid();
        $approver = $this->createUser($masjid, 'bendahara');
        $creator = $this->createUser($masjid, 'sekretaris', ['phone' => '081211110000']);
        [$kas, $category] = $this->makeKasAndCategory($masjid);

        $this->actingAs($creator)->post(route('admin.finance.transaksi.store'), [
            'kas_account_id' => $kas->id,
            'category_id' => $category->id,
            'type' => 'income',
            'amount' => 50000,
            'description' => 'Donasi',
            'transaction_date' => now()->toDateString(),
        ]);

        $transaction = \App\Models\Transaction::first();

        $response = $this->actingAs($approver)->post(route('admin.finance.transaksi.approve', $transaction), [
            'status' => 'approved',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'status' => 'approved']);
    }

    public function test_bendahara_can_reject_pending_transaction(): void
    {
        $masjid = $this->createMasjid();
        $approver = $this->createUser($masjid, 'bendahara');
        [$kas, $category] = $this->makeKasAndCategory($masjid);

        $transaction = \App\Models\Transaction::create([
            'masjid_id' => $masjid->id,
            'kas_account_id' => $kas->id,
            'category_id' => $category->id,
            'user_id' => $approver->id,
            'reference_number' => 'TRX-TEST-001',
            'type' => 'expense',
            'amount' => 30000,
            'description' => 'Pengeluaran tidak wajar',
            'status' => 'pending',
            'transaction_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($approver)->post(route('admin.finance.transaksi.approve', $transaction), [
            'status' => 'rejected',
            'notes' => 'Tidak sesuai bukti',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'status' => 'rejected']);
    }

    public function test_finance_report_filters_by_period(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'bendahara');
        [$kas, $category] = $this->makeKasAndCategory($masjid);

        \App\Models\Transaction::create([
            'masjid_id' => $masjid->id,
            'kas_account_id' => $kas->id,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'reference_number' => 'TRX-IN-RANGE',
            'type' => 'income',
            'amount' => 100000,
            'description' => 'Dalam periode',
            'status' => 'approved',
            'transaction_date' => now()->toDateString(),
        ]);

        \App\Models\Transaction::create([
            'masjid_id' => $masjid->id,
            'kas_account_id' => $kas->id,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'reference_number' => 'TRX-OUT-RANGE',
            'type' => 'income',
            'amount' => 999000,
            'description' => 'Luar periode',
            'status' => 'approved',
            'transaction_date' => now()->subYears(2)->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('admin.finance.laporan', ['preset' => 'month']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('summary.income', 100000)
        );
    }
}
