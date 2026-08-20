<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Announcement;
use App\Models\Asset;
use App\Models\Donation;
use App\Models\KasAccount;
use App\Models\StaffAttendance;
use App\Models\Transaction;
use App\Models\TpqClassTeacher;
use App\Models\TpqDailyProgress;
use App\Models\TpqStudent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Tanpa dashboard.admin (mis. ustadz) dapat dashboard pribadi —
        // tidak ada angka keuangan masjid-wide, cuma data milik sendiri.
        if (! $user->can('dashboard.admin')) {
            return $this->ustadzDashboard($user);
        }

        $masjidId = $user->masjid_id;

        $data = Cache::remember("dashboard:{$masjidId}", 60, fn () => $this->buildDashboard($masjidId));

        return Inertia::render('Dashboard/Index', $data);
    }

    private function ustadzDashboard(User $user): Response
    {
        $masjidId = $user->masjid_id;
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();

        $attendanceRecords = StaffAttendance::where('masjid_id', $masjidId)
            ->where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $attendanceSummary = [
            'presentCount' => $attendanceRecords->whereNotNull('clock_in')->count(),
            'completeCount' => $attendanceRecords->whereNotNull('clock_in')->whereNotNull('clock_out')->count(),
            'totalDays' => $start->diffInDays($end) + 1,
        ];

        // TpqDailyProgress tidak punya kolom masjid_id sendiri (scoped lewat
        // student/class) — recorded_by sudah cukup untuk "punya ustadz ini",
        // karena satu user cuma terhubung ke satu masjid.
        $recentProgress = TpqDailyProgress::where('recorded_by', $user->id)
            ->with(['student:id,name', 'class:id,name'])
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'student_id', 'class_id', 'date', 'method', 'jilid', 'halaman', 'surah', 'created_at']);

        $classesTaught = TpqClassTeacher::where('teacher_id', $user->id)
            ->whereHas('academicYear', fn ($q) => $q->where('is_active', true))
            ->with('class:id,name')
            ->get()
            ->pluck('class')
            ->filter()
            ->values();

        $announcements = Announcement::where('masjid_id', $masjidId)
            ->where('is_published', true)
            ->latest('published_at')
            ->limit(3)
            ->get(['id', 'title', 'content', 'type', 'published_at']);

        return Inertia::render('Dashboard/UstadzIndex', [
            'attendanceSummary' => $attendanceSummary,
            'recentProgress' => $recentProgress,
            'classesTaught' => $classesTaught,
            'announcements' => $announcements,
        ]);
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
