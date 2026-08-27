<?php

namespace App\Http\Controllers\Tpq;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppNotification;
use App\Models\TpqSppBill;
use App\Models\TpqSppPayment;
use App\Models\TpqStudent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TpqSppController extends Controller
{
    public function index(Request $request): Response
    {
        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;
        $masjidId = $request->user()->masjid_id;

        $bills = TpqSppBill::with('student:id,name,nis')
            ->whereHas('student', fn ($q) => $q->where('masjid_id', $masjidId))
            ->where('month', $month)
            ->where('year', $year)
            ->get()
            ->sortBy('student.name')
            ->values();

        return Inertia::render('Learning/Tpq/Spp/Index', [
            'month' => (int) $month,
            'year' => (int) $year,
            'bills' => $bills,
            'summary' => [
                'paid' => $bills->where('status', 'paid')->count(),
                'unpaid' => $bills->whereIn('status', ['unpaid', 'partial'])->count(),
                'outstanding' => (float) $bills->whereIn('status', ['unpaid', 'partial'])->sum(fn ($b) => $b->amount - $b->paid_amount),
            ],
        ]);
    }

    public function generateBills(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $students = TpqStudent::where('masjid_id', $request->user()->masjid_id)->where('status', 'aktif')->get();
        $created = 0;

        foreach ($students as $student) {
            $bill = TpqSppBill::firstOrNew([
                'student_id' => $student->id,
                'year' => $data['year'],
                'month' => $data['month'],
            ]);

            if (! $bill->exists) {
                $bill->amount = $data['amount'];
                $bill->status = 'unpaid';
                $bill->save();
                $created++;
            }
        }

        return back()->with('success', "{$created} tagihan SPP baru berhasil dibuat.");
    }

    public function pay(Request $request, TpqSppBill $bill): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $bill);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        TpqSppPayment::create([
            ...$data,
            'bill_id' => $bill->id,
            'received_by' => $request->user()->id,
            'receipt_number' => 'SPP-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -6)),
        ]);

        $totalPaid = $bill->paid_amount + $data['amount'];

        $bill->update([
            'paid_amount' => $totalPaid,
            'status' => $totalPaid >= $bill->amount ? 'paid' : 'partial',
        ]);

        return back()->with('success', 'Pembayaran SPP berhasil dicatat.');
    }

    public function sendReminders(Request $request): RedirectResponse
    {
        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;

        $bills = TpqSppBill::with('student')
            ->whereHas('student', fn ($q) => $q->where('masjid_id', $request->user()->masjid_id))
            ->where('month', $month)
            ->where('year', $year)
            ->whereIn('status', ['unpaid', 'partial'])
            ->get();

        $monthName = \Illuminate\Support\Carbon::create($year, $month, 1)->translatedFormat('F Y');

        foreach ($bills as $index => $bill) {
            if (empty($bill->student->guardian_whatsapp)) {
                continue;
            }

            $sisa = $bill->amount - $bill->paid_amount;
            $message = "Assalamu'alaikum Bapak/Ibu Wali {$bill->student->name},\n\n"
                ."Mengingatkan tagihan SPP TPQ bulan {$monthName} sebesar Rp"
                .number_format($sisa, 0, ',', '.')." belum terbayar.\n\n"
                ."Jazakumullahu khairan.";

            SendWhatsAppNotification::dispatch($bill->student->guardian_whatsapp, $message)->delay(now()->addSeconds($index * 2));
            $bill->update(['reminder_sent' => true]);
        }

        return back()->with('success', "Reminder SPP dikirim ke {$bills->count()} wali murid.");
    }

    /**
     * TpqSppBill tidak punya kolom masjid_id maupun global scope tenant —
     * kepemilikannya diturunkan dari santri pemiliknya. Tanpa ini admin tenant A
     * yang tahu/menebak UUID tagihan tenant B bisa mencatat pembayaran atasnya
     * lewat URL langsung.
     */
    private function authorizeSameMasjid(Request $request, TpqSppBill $bill): void
    {
        abort_unless($bill->student?->masjid_id === $request->user()->masjid_id, 404);
    }
}
