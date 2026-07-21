<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Asset;
use App\Models\Donation;
use App\Models\KasAccount;
use App\Models\Transaction;
use App\Models\TpqStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $masjidId = $request->user()->masjid_id;

        $data = Cache::remember("dashboard:{$masjidId}", 60, fn () => $this->buildDashboard($masjidId));

        return Inertia::render('Dashboard/Index', $data);
    }

    private function buildDashboard(string $masjidId): array
    {
        $totalKas = KasAccount::where('masjid_id', $masjidId)->sum('initial_balance')
            + Transaction::where('masjid_id', $masjidId)->where('status', 'approved')->where('type', 'income')->sum('amount')
            - Transaction::where('masjid_id', $masjidId)->where('status', 'approved')->where('type', 'expense')->sum('amount');

        $incomeThisMonth = Transaction::where('masjid_id', $masjidId)
            ->where('status', 'approved')->where('type', 'income')
            ->whereYear('transaction_date', now()->year)
            ->whereMonth('transaction_date', now()->month)
            ->sum('amount');

        $expenseThisMonth = Transaction::where('masjid_id', $masjidId)
            ->where('status', 'approved')->where('type', 'expense')
            ->whereYear('transaction_date', now()->year)
            ->whereMonth('transaction_date', now()->month)
            ->sum('amount');

        $activeStudents = TpqStudent::where('masjid_id', $masjidId)->where('status', 'aktif')->count();

        $chart = $this->monthlyChart($masjidId);

        $upcomingActivities = Activity::where('masjid_id', $masjidId)
            ->whereIn('status', ['published', 'ongoing'])
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->limit(3)
            ->get(['id', 'name', 'category', 'location', 'start_at']);

        $assetsNeedingAttention = Asset::where('masjid_id', $masjidId)
            ->where(function ($query) {
                $query->where('condition', 'rusak_ringan')
                    ->orWhere('condition', 'rusak_berat')
                    ->orWhereBetween('next_maintenance_date', [now()->toDateString(), now()->addDays(7)->toDateString()]);
            })
            ->limit(5)
            ->get(['id', 'name', 'condition', 'location', 'next_maintenance_date']);

        $latestDonations = Donation::where('masjid_id', $masjidId)
            ->where('status', 'paid')
            ->latest('paid_at')
            ->limit(5)
            ->get(['id', 'donor_name', 'amount', 'paid_at']);

        return [
            'stats' => [
                'totalKas' => (float) $totalKas,
                'incomeThisMonth' => (float) $incomeThisMonth,
                'expenseThisMonth' => (float) $expenseThisMonth,
                'activeStudents' => $activeStudents,
            ],
            'chart' => $chart,
            'upcomingActivities' => $upcomingActivities,
            'assetsNeedingAttention' => $assetsNeedingAttention,
            'latestDonations' => $latestDonations,
        ];
    }

    private function monthlyChart(string $masjidId): array
    {
        $start = Carbon::now()->subMonths(11)->startOfMonth();

        $rows = Transaction::where('masjid_id', $masjidId)
            ->where('status', 'approved')
            ->where('transaction_date', '>=', $start->toDateString())
            ->get(['transaction_date', 'type', 'amount'])
            ->groupBy(fn (Transaction $t) => $t->transaction_date->format('Y-m'));

        $labels = [];
        $income = [];
        $expense = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->translatedFormat('M Y');

            $bucket = $rows->get($key, collect());
            $income[] = (float) $bucket->where('type', 'income')->sum('amount');
            $expense[] = (float) $bucket->where('type', 'expense')->sum('amount');
        }

        return ['labels' => $labels, 'income' => $income, 'expense' => $expense];
    }
}
