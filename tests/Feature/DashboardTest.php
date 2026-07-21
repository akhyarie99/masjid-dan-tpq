<?php

namespace Tests\Feature;

use App\Models\KasAccount;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    public function test_dashboard_shows_monthly_income_and_expense_totals(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'bendahara');

        $kas = KasAccount::create([
            'masjid_id' => $masjid->id,
            'name' => 'Kas Tunai',
            'type' => 'cash',
            'initial_balance' => 0,
            'is_active' => true,
        ]);
        $category = TransactionCategory::create([
            'masjid_id' => $masjid->id,
            'name' => 'Infaq',
            'type' => 'income',
            'is_system' => true,
        ]);

        Transaction::create([
            'masjid_id' => $masjid->id,
            'kas_account_id' => $kas->id,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'reference_number' => 'TRX-DASH-1',
            'type' => 'income',
            'amount' => 200000,
            'description' => 'Infaq bulan ini',
            'status' => 'approved',
            'transaction_date' => now()->toDateString(),
        ]);

        Transaction::create([
            'masjid_id' => $masjid->id,
            'kas_account_id' => $kas->id,
            'category_id' => $category->id,
            'user_id' => $user->id,
            'reference_number' => 'TRX-DASH-2',
            'type' => 'income',
            'amount' => 999999,
            'description' => 'Di luar 12 bulan terakhir',
            'status' => 'approved',
            'transaction_date' => now()->subYears(3)->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('stats.incomeThisMonth', 200000)
            ->has('chart.labels', 12)
            ->where('chart.income.11', 200000)
        );
    }

    public function test_dashboard_response_is_cached_per_masjid(): void
    {
        $masjid = $this->createMasjid();
        $user = $this->createUser($masjid, 'bendahara');

        $first = $this->actingAs($user)->get(route('admin.dashboard'));
        $first->assertOk();

        KasAccount::create([
            'masjid_id' => $masjid->id,
            'name' => 'Kas Baru',
            'type' => 'cash',
            'initial_balance' => 500000,
            'is_active' => true,
        ]);

        $second = $this->actingAs($user)->get(route('admin.dashboard'));

        $second->assertInertia(fn ($page) => $page->where('stats.totalKas', 0));
    }
}
