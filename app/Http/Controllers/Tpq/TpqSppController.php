<?php

namespace App\Http\Controllers\Tpq;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppNotification;
use App\Models\TpqSppBill;
use App\Models\TpqSppPayment;
use App\Models\TpqStudent;
use App\Services\GuardianNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
                'pendingProof' => $bills->where('proof_status', 'pending')->count(),
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

        return back()->with('success', "{$created} tagihan Infaq baru berhasil dibuat.");
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

        $this->recordPayment($bill, $data, $request->user()->id);

        return back()->with('success', 'Pembayaran Infaq berhasil dicatat.');
    }

    /**
     * Wali sudah kirim bukti transfer lewat portal (lihat
     * WaliController::sppUploadProof) — admin sudah cek ke rekening, setujui di
     * sini untuk sekaligus mencatat pembayarannya (lewat jalur yang SAMA dengan
     * "Catat Bayar" manual, bukan alur terpisah) dan kirim notifikasi
     * terima kasih ke wali.
     */
    public function approveProof(Request $request, TpqSppBill $bill): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $bill);

        if ($bill->proof_status !== 'pending') {
            return back()->with('error', 'Tidak ada bukti transfer yang menunggu konfirmasi untuk tagihan ini.');
        }

        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'paid_date' => ['nullable', 'date'],
        ]);

        $this->recordPayment($bill, [
            'amount' => $data['amount'] ?? (float) ($bill->amount - $bill->paid_amount),
            'paid_date' => $data['paid_date'] ?? now()->toDateString(),
            'payment_method' => 'transfer',
            'notes' => 'Dikonfirmasi dari bukti transfer wali.',
        ], $request->user()->id);

        // proof_file TETAP disimpan sebagai arsip — cuma status-nya yang
        // di-reset, supaya kalau perlu dicek ulang nanti masih ada buktinya.
        $bill->update(['proof_status' => 'none']);

        $student = $bill->student;
        $monthName = Carbon::create($bill->year, $bill->month, 1)->translatedFormat('F Y');
        $sapaan = $student->gender === 'P' ? 'sholehah' : 'sholeh';

        app(GuardianNotifier::class)->notify($student, "Assalamu'alaikum Bapak/Ibu Wali {$student->name},\n\n"
            ."Alhamdulillah, pembayaran Infaq TPQ bulan {$monthName} sebesar Rp"
            .number_format($bill->fresh()->paid_amount, 0, ',', '.')." sudah kami terima dan konfirmasi. "
            ."Jazakumullahu khairan atas keikhlasannya.\n\n"
            ."Semoga Allah lapangkan rizki Bapak/Ibu, berkahi setiap langkahnya, dan menjadikan Ananda {$student->name} anak yang {$sapaan}, berbakti, dan bermanfaat dunia akhirat. Aamiin.\n\n"
            .'Barakallahu fiikum.');

        return back()->with('success', 'Bukti transfer disetujui, pembayaran tercatat dan wali sudah diberi tahu.');
    }

    public function rejectProof(Request $request, TpqSppBill $bill): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $bill);

        if ($bill->proof_status !== 'pending') {
            return back()->with('error', 'Tidak ada bukti transfer yang menunggu konfirmasi untuk tagihan ini.');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $bill->update([
            'proof_status' => 'rejected',
            'proof_rejection_reason' => $data['reason'],
        ]);

        $student = $bill->student;
        $monthName = Carbon::create($bill->year, $bill->month, 1)->translatedFormat('F Y');

        app(GuardianNotifier::class)->notify($student, "Assalamu'alaikum Bapak/Ibu Wali {$student->name},\n\n"
            ."Mohon maaf, bukti transfer Infaq bulan {$monthName} yang dikirimkan belum bisa kami konfirmasi.\n"
            ."Alasan: {$data['reason']}\n\n"
            .'Silakan kirim ulang bukti transfer yang sesuai lewat Portal Wali. Jazakumullahu khairan.');

        return back()->with('success', 'Bukti transfer ditolak dan wali sudah diberi tahu.');
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

        $monthName = Carbon::create($year, $month, 1)->translatedFormat('F Y');

        foreach ($bills as $index => $bill) {
            if (empty($bill->student->guardian_whatsapp)) {
                continue;
            }

            $sisa = $bill->amount - $bill->paid_amount;
            $message = "Assalamu'alaikum Bapak/Ibu Wali {$bill->student->name},\n\n"
                ."Mengingatkan tagihan Infaq TPQ bulan {$monthName} sebesar Rp"
                .number_format($sisa, 0, ',', '.')." belum terbayar.\n\n"
                ."Jazakumullahu khairan.";

            SendWhatsAppNotification::dispatch($bill->student->guardian_whatsapp, $message)->delay(now()->addSeconds($index * 2));
            $bill->update(['reminder_sent' => true]);
        }

        return back()->with('success', "Reminder Infaq dikirim ke {$bills->count()} wali murid.");
    }

    private function recordPayment(TpqSppBill $bill, array $data, string $receivedBy): void
    {
        TpqSppPayment::create([
            ...$data,
            'bill_id' => $bill->id,
            'received_by' => $receivedBy,
            'receipt_number' => 'SPP-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -6)),
        ]);

        $totalPaid = $bill->paid_amount + $data['amount'];

        $bill->update([
            'paid_amount' => $totalPaid,
            'status' => $totalPaid >= $bill->amount ? 'paid' : 'partial',
        ]);
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
