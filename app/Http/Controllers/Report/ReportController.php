<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Asset;
use App\Models\JamaahProfile;
use App\Models\SocialProgram;
use App\Models\Transaction;
use App\Models\TpqStudent;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function finance(Request $request)
    {
        return redirect()->route('admin.finance.laporan', $request->query());
    }

    public function asset(Request $request): Response
    {
        $masjidId = $request->user()->masjid_id;

        $assets = Asset::where('masjid_id', $masjidId)->get();

        return Inertia::render('Report/Asset', [
            'summary' => [
                'total' => $assets->count(),
                'byCondition' => $assets->groupBy('condition')->map->count(),
                'byStatus' => $assets->groupBy('status')->map->count(),
                'totalValue' => (float) $assets->sum('purchase_price'),
            ],
            'assets' => $assets->load('category:id,name'),
        ]);
    }

    public function activity(Request $request): Response
    {
        $masjidId = $request->user()->masjid_id;
        $year = $request->integer('year') ?: now()->year;

        $activities = Activity::where('masjid_id', $masjidId)
            ->whereYear('start_at', $year)
            ->withCount('registrations')
            ->get();

        return Inertia::render('Report/Activity', [
            'year' => $year,
            'summary' => [
                'total' => $activities->count(),
                'byCategory' => $activities->groupBy('category')->map->count(),
                'totalRegistrations' => $activities->sum('registrations_count'),
            ],
            'activities' => $activities,
        ]);
    }

    public function jamaah(Request $request): Response
    {
        $masjidId = $request->user()->masjid_id;
        $jamaah = JamaahProfile::where('masjid_id', $masjidId)->get();

        return Inertia::render('Report/Jamaah', [
            'summary' => [
                'total' => $jamaah->count(),
                'byStatus' => $jamaah->groupBy('status')->map->count(),
                'byRt' => $jamaah->whereNotNull('rt')->groupBy('rt')->map->count(),
            ],
        ]);
    }

    public function lpj(Request $request): Response
    {
        return Inertia::render('Report/Lpj', [
            'currentYear' => now()->year,
        ]);
    }

    public function generateLpj(Request $request): \Illuminate\Http\Response
    {
        $year = $request->integer('year') ?: now()->year;
        $masjid = $request->user()->masjid;
        $masjidId = $masjid->id;

        $from = Carbon::create($year, 1, 1)->startOfYear();
        $to = Carbon::create($year, 1, 1)->endOfYear();

        $income = (float) Transaction::where('masjid_id', $masjidId)->where('status', 'approved')->where('type', 'income')
            ->whereBetween('transaction_date', [$from, $to])->sum('amount');
        $expense = (float) Transaction::where('masjid_id', $masjidId)->where('status', 'approved')->where('type', 'expense')
            ->whereBetween('transaction_date', [$from, $to])->sum('amount');

        $categoryBreakdown = Transaction::with('category:id,name,type')
            ->where('masjid_id', $masjidId)->where('status', 'approved')
            ->whereBetween('transaction_date', [$from, $to])
            ->selectRaw('category_id, type, SUM(amount) as total')
            ->groupBy('category_id', 'type')
            ->get();

        $activities = Activity::where('masjid_id', $masjidId)
            ->whereYear('start_at', $year)
            ->whereIn('status', ['done', 'ongoing'])
            ->get(['name', 'category', 'start_at', 'location']);

        $assets = Asset::where('masjid_id', $masjidId)->get();

        $tpqStudentCount = TpqStudent::where('masjid_id', $masjidId)->where('status', 'aktif')->count();

        $socialPrograms = SocialProgram::where('masjid_id', $masjidId)
            ->whereYear('start_date', $year)
            ->withCount('recipients')
            ->get();

        $pengurus = User::where('masjid_id', $masjidId)->with('roles:id,name')->get();

        $pdf = Pdf::loadView('pdf.lpj-annual', [
            'masjid' => $masjid,
            'year' => $year,
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
            'categoryBreakdown' => $categoryBreakdown,
            'activities' => $activities,
            'assets' => $assets,
            'tpqStudentCount' => $tpqStudentCount,
            'socialPrograms' => $socialPrograms,
            'pengurus' => $pengurus,
        ]);

        return $pdf->download("lpj-tahunan-{$masjid->slug}-{$year}.pdf");
    }
}
