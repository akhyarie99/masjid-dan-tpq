<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Announcement;
use App\Models\Asset;
use App\Models\BuildingProject;
use App\Models\Donation;
use App\Models\ImamSchedule;
use App\Models\Masjid;
use App\Models\PrayerSchedule;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PublicPortalController extends Controller
{
    public function index(): Response
    {
        $masjid = Masjid::where('is_active', true)->firstOrFail();

        $todaySchedule = Cache::remember(
            "prayer-schedule:{$masjid->id}:today",
            now()->endOfDay(),
            fn () => PrayerSchedule::where('masjid_id', $masjid->id)->whereDate('date', now()->toDateString())->first()
        );

        $announcements = Announcement::where('masjid_id', $masjid->id)
            ->where('is_published', true)
            ->latest('published_at')
            ->limit(3)
            ->get(['id', 'title', 'content', 'type', 'published_at']);

        $activities = Activity::where('masjid_id', $masjid->id)
            ->whereIn('status', ['published', 'ongoing'])
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->limit(5)
            ->get(['id', 'name', 'category', 'location', 'start_at', 'registration_link']);

        $weekStart = Carbon::today()->startOfWeek();
        $imamSchedules = ImamSchedule::with('imam:id,name,type', 'substituteImam:id,name')
            ->where('masjid_id', $masjid->id)
            ->whereBetween('date', [$weekStart->toDateString(), $weekStart->copy()->addDays(6)->toDateString()])
            ->orderBy('date')
            ->get();

        $donationThisMonth = Donation::where('masjid_id', $masjid->id)
            ->where('status', 'paid')
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('amount');

        $latestDonors = Donation::where('masjid_id', $masjid->id)
            ->where('status', 'paid')
            ->latest('paid_at')
            ->limit(5)
            ->get(['donor_name', 'amount', 'paid_at']);

        $incomeThisMonth = Transaction::where('masjid_id', $masjid->id)
            ->where('status', 'approved')->where('type', 'income')
            ->whereYear('transaction_date', now()->year)->whereMonth('transaction_date', now()->month)
            ->sum('amount');

        $expenseThisMonth = Transaction::where('masjid_id', $masjid->id)
            ->where('status', 'approved')->where('type', 'expense')
            ->whereYear('transaction_date', now()->year)->whereMonth('transaction_date', now()->month)
            ->sum('amount');

        return Inertia::render('Public/Portal', [
            'masjid' => $masjid,
            'todaySchedule' => $todaySchedule,
            'announcements' => $announcements,
            'activities' => $activities,
            'imamSchedules' => $imamSchedules,
            'donation' => [
                'thisMonth' => (float) $donationThisMonth,
                'latestDonors' => $latestDonors,
            ],
            'finance' => [
                'income' => (float) $incomeThisMonth,
                'expense' => (float) $expenseThisMonth,
                'balance' => (float) ($incomeThisMonth - $expenseThisMonth),
            ],
        ]);
    }

    public function donation(): Response
    {
        $masjid = Masjid::where('is_active', true)->firstOrFail();

        return Inertia::render('Public/Donation/Index', [
            'masjid' => $masjid->only(['id', 'name', 'bank_accounts', 'logo_url']),
            'midtrans' => [
                'clientKey' => config('services.midtrans.client_key'),
                'isProduction' => (bool) config('services.midtrans.is_production'),
            ],
        ]);
    }

    public function financialReport(): Response
    {
        $masjid = Masjid::where('is_active', true)->firstOrFail();

        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        $breakdown = Transaction::with('category:id,name,type')
            ->where('masjid_id', $masjid->id)
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$from, $to])
            ->selectRaw('category_id, type, SUM(amount) as total')
            ->groupBy('category_id', 'type')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category?->name ?? '-',
                'type' => $row->type,
                'total' => (float) $row->total,
            ]);

        $income = (float) Transaction::where('masjid_id', $masjid->id)->where('status', 'approved')->where('type', 'income')
            ->whereBetween('transaction_date', [$from, $to])->sum('amount');
        $expense = (float) Transaction::where('masjid_id', $masjid->id)->where('status', 'approved')->where('type', 'expense')
            ->whereBetween('transaction_date', [$from, $to])->sum('amount');

        return Inertia::render('Public/FinancialReport', [
            'masjid' => $masjid->only(['id', 'name', 'logo_url']),
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'summary' => ['income' => $income, 'expense' => $expense, 'balance' => $income - $expense],
            'breakdown' => $breakdown,
        ]);
    }

    public function imamSchedule(): Response
    {
        $masjid = Masjid::where('is_active', true)->firstOrFail();

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $schedules = ImamSchedule::with('imam:id,name,type', 'substituteImam:id,name')
            ->where('masjid_id', $masjid->id)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('date')
            ->get();

        return Inertia::render('Public/ImamSchedule', [
            'masjid' => $masjid->only(['id', 'name', 'logo_url']),
            'month' => now()->month,
            'year' => now()->year,
            'schedules' => $schedules,
        ]);
    }

    public function activities(): Response
    {
        $masjid = Masjid::where('is_active', true)->firstOrFail();

        $activities = Activity::where('masjid_id', $masjid->id)
            ->whereIn('status', ['published', 'ongoing'])
            ->where('start_at', '>=', now()->subDay())
            ->orderBy('start_at')
            ->get(['id', 'name', 'description', 'category', 'location', 'start_at', 'end_at', 'quota', 'registration_link']);

        return Inertia::render('Public/Activities', [
            'masjid' => $masjid->only(['id', 'name', 'logo_url']),
            'activities' => $activities,
        ]);
    }

    public function assetDetail(string $assetCode): Response
    {
        $asset = Asset::with('category:id,name')->where('asset_code', $assetCode)->firstOrFail();

        return Inertia::render('Public/AssetDetail', [
            'asset' => $asset,
            'masjidName' => $asset->masjid->name,
        ]);
    }

    public function buildingProject(BuildingProject $project): Response
    {
        $project->load(['updates.poster:id,name', 'masjid']);

        return Inertia::render('Public/BuildingProject', [
            'project' => [...$project->toArray(), 'funding_percent' => $project->fundingPercent()],
        ]);
    }

    public function digitalClock(): Response
    {
        $masjid = Masjid::where('is_active', true)->firstOrFail();

        $todaySchedule = Cache::remember(
            "prayer-schedule:{$masjid->id}:today",
            now()->endOfDay(),
            fn () => PrayerSchedule::where('masjid_id', $masjid->id)->whereDate('date', now()->toDateString())->first()
        );

        $announcements = Announcement::where('masjid_id', $masjid->id)
            ->where('is_published', true)
            ->latest('published_at')
            ->limit(5)
            ->get(['title']);

        return Inertia::render('Prayer/DigitalClock', [
            'masjid' => $masjid->only(['id', 'name', 'logo_url', 'background_url', 'iqomah_offset_minutes']),
            'schedule' => $todaySchedule,
            'tickerItems' => $announcements->pluck('title'),
        ]);
    }
}
