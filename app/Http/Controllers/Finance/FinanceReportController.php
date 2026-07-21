<?php

namespace App\Http\Controllers\Finance;

use App\Exports\FinancialReportExport;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FinanceReportController extends Controller
{
    public function index(Request $request): Response
    {
        [$from, $to, $prevFrom, $prevTo] = $this->resolvePeriod($request);
        $masjidId = $request->user()->masjid_id;

        $summary = $this->summary($masjidId, $from, $to);
        $prevSummary = $this->summary($masjidId, $prevFrom, $prevTo);

        $breakdown = Transaction::with('category:id,name,type')
            ->where('masjid_id', $masjidId)
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

        $transactions = Transaction::with(['category:id,name', 'kasAccount:id,name'])
            ->where('masjid_id', $masjidId)
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$from, $to])
            ->orderBy('transaction_date')
            ->get();

        $qrSvg = base64_encode(QrCode::size(180)->generate(route('public.finance')));

        return Inertia::render('Finance/Report/Index', [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'preset' => $request->string('preset')->toString() ?: 'month'],
            'summary' => [
                ...$summary,
                'incomeChangePercent' => $this->percentChange($prevSummary['income'], $summary['income']),
                'expenseChangePercent' => $this->percentChange($prevSummary['expense'], $summary['expense']),
            ],
            'breakdown' => $breakdown,
            'transactions' => $transactions,
            'qrCode' => "data:image/svg+xml;base64,{$qrSvg}",
        ]);
    }

    public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        [$from, $to] = $this->resolvePeriod($request);
        $masjid = $request->user()->masjid;

        $transactions = Transaction::with(['category:id,name', 'kasAccount:id,name'])
            ->where('masjid_id', $masjid->id)
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$from, $to])
            ->orderBy('transaction_date')
            ->get();

        $summary = $this->summary($masjid->id, $from, $to);

        $pdf = Pdf::loadView('pdf.financial-report', [
            'masjid' => $masjid,
            'from' => $from,
            'to' => $to,
            'transactions' => $transactions,
            'summary' => $summary,
            'printedAt' => now(),
        ]);

        return $pdf->download("laporan-keuangan-{$from->format('Ymd')}-{$to->format('Ymd')}.pdf");
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        [$from, $to] = $this->resolvePeriod($request);

        $transactions = Transaction::with(['category:id,name', 'kasAccount:id,name'])
            ->where('masjid_id', $request->user()->masjid_id)
            ->where('status', 'approved')
            ->whereBetween('transaction_date', [$from, $to])
            ->orderBy('transaction_date')
            ->get();

        return Excel::download(new FinancialReportExport($transactions), "laporan-keuangan-{$from->format('Ymd')}-{$to->format('Ymd')}.xlsx");
    }

    private function summary(string $masjidId, Carbon $from, Carbon $to): array
    {
        $income = (float) Transaction::where('masjid_id', $masjidId)->where('status', 'approved')->where('type', 'income')
            ->whereBetween('transaction_date', [$from, $to])->sum('amount');

        $expense = (float) Transaction::where('masjid_id', $masjidId)->where('status', 'approved')->where('type', 'expense')
            ->whereBetween('transaction_date', [$from, $to])->sum('amount');

        return ['income' => $income, 'expense' => $expense, 'balance' => $income - $expense];
    }

    private function percentChange(float $previous, float $current): ?float
    {
        if ($previous == 0.0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /** @return array{0: Carbon, 1: Carbon, 2: Carbon, 3: Carbon} */
    private function resolvePeriod(Request $request): array
    {
        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->string('from'))->startOfDay();
            $to = Carbon::parse($request->string('to'))->endOfDay();
        } else {
            $preset = $request->string('preset')->toString() ?: 'month';
            $to = now()->endOfDay();
            $from = match ($preset) {
                'week' => now()->startOfWeek(),
                'year' => now()->startOfYear(),
                default => now()->startOfMonth(),
            };
        }

        $length = $from->diffInDays($to) + 1;
        $prevTo = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($length - 1)->startOfDay();

        return [$from, $to, $prevFrom, $prevTo];
    }
}
