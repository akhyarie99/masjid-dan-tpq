<?php

namespace App\Http\Controllers;

use App\Jobs\SendWhatsAppNotification;
use App\Mail\WaliResetPasswordMail;
use App\Models\TpqDailyProgress;
use App\Models\TpqGuardianPushSubscription;
use App\Models\TpqReportCard;
use App\Models\TpqSppBill;
use App\Models\TpqStudent;
use App\Models\WaliAccount;
use App\Models\WaliPasswordResetToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WaliController extends Controller
{
    public function showLogin(): Response
    {
        return Inertia::render('Wali/Login');
    }

    /**
     * Manifest khusus portal wali (beda dari manifest.json umum) supaya ikon &
     * nama yang muncul saat "Add to Home Screen" itu identitas masjidnya, bukan
     * generik "SiMasjid" — dan start_url langsung ke dashboard wali, bukan
     * landing page publik.
     */
    public function manifest(Request $request): JsonResponse
    {
        $account = $this->currentAccount($request);
        $masjid = $account->students()->first()?->masjid;

        return response()->json([
            'name' => 'Portal Wali'.($masjid ? " - {$masjid->name}" : ''),
            'short_name' => 'Portal Wali',
            'start_url' => '/wali/dashboard',
            'scope' => '/wali',
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#16a34a',
            'lang' => 'id',
            'icons' => [
                [
                    'src' => $masjid?->logo_url ?? '/icons/icon.svg',
                    'sizes' => $masjid?->logo_url ? '512x512' : 'any',
                    'type' => $masjid?->logo_url ? 'image/png' : 'image/svg+xml',
                    'purpose' => 'any maskable',
                ],
            ],
        ])->header('Content-Type', 'application/manifest+json');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $account = WaliAccount::where('phone', $credentials['phone'])->first();

        if (! $account || ! Hash::check($credentials['password'], $account->password)) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor HP atau kata sandi salah.',
            ]);
        }

        $request->session()->put('wali_account_id', $account->id);
        $request->session()->regenerate();

        return redirect()->route('wali.dashboard');
    }

    public function showForgotPassword(): Response
    {
        return Inertia::render('Wali/ForgotPassword');
    }

    /**
     * Langkah 1: cek nomor HP-nya terdaftar & channel apa saja yang bisa dipakai
     * (WA belum tentu aktif — tidak semua nomor yang didaftarkan itu WhatsApp),
     * supaya wali bisa pilih sendiri mau dikirim ke mana.
     */
    public function findAccountForReset(Request $request): JsonResponse
    {
        $data = $request->validate(['phone' => ['required', 'string']]);

        $account = WaliAccount::where('phone', $data['phone'])->first();

        if (! $account) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found' => true,
            'whatsapp' => $account->maskedPhone(),
            'email' => $account->maskedEmail(),
        ]);
    }

    public function sendResetLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'channel' => ['required', 'in:whatsapp,email'],
        ]);

        $account = WaliAccount::where('phone', $data['phone'])->first();

        if (! $account || ($data['channel'] === 'email' && ! $account->email)) {
            return response()->json(['message' => 'Tidak bisa mengirim link reset. Coba lagi atau hubungi pengurus TPQ.'], 422);
        }

        $plainToken = WaliPasswordResetToken::createFor($account);
        $resetUrl = route('wali.reset-password.show', ['token' => $plainToken, 'account' => $account->id]);

        if ($data['channel'] === 'whatsapp') {
            SendWhatsAppNotification::dispatch(
                $account->phone,
                "Assalamu'alaikum, ada permintaan reset password Portal Wali. Klik link ini untuk buat password baru (berlaku 15 menit):\n{$resetUrl}\n\nAbaikan pesan ini kalau bukan Anda yang meminta."
            );
        } else {
            Mail::to($account->email)->send(new WaliResetPasswordMail($account, $resetUrl));
        }

        return response()->json(['message' => 'Link terkirim.']);
    }

    public function showResetPassword(Request $request, string $token): Response
    {
        $account = WaliAccount::find($request->query('account'));
        $valid = $account && WaliPasswordResetToken::verify($account, $token);

        return Inertia::render('Wali/ResetPassword', [
            'valid' => $valid,
            'token' => $token,
            'account' => $account?->id,
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account' => ['required', 'uuid'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $account = WaliAccount::find($data['account']);

        if (! $account || ! WaliPasswordResetToken::verify($account, $data['token'])) {
            throw ValidationException::withMessages([
                'password' => 'Link reset sudah kedaluwarsa atau tidak valid. Minta link baru.',
            ]);
        }

        $account->update(['password' => $data['password']]);
        WaliPasswordResetToken::invalidateFor($account);

        $request->session()->put('wali_account_id', $account->id);
        $request->session()->regenerate();

        return redirect()->route('wali.dashboard')->with('success', 'Password berhasil diperbarui.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('wali_account_id');
        $request->session()->regenerate();

        return redirect()->route('wali.login');
    }

    public function dashboard(Request $request): Response
    {
        $account = $this->currentAccount($request);

        $students = $account->students()
            ->with(['studentClasses' => fn ($q) => $q->whereHas('academicYear', fn ($aq) => $aq->where('is_active', true))->with('class:id,name')])
            ->get();

        return Inertia::render('Wali/Dashboard', [
            'students' => $students->map(fn (TpqStudent $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'nis' => $s->nis,
                'photo' => $s->photo,
                'class' => $s->studentClasses->first()?->class,
            ]),
            'notifyWhatsapp' => (bool) $students->first()?->notify_whatsapp,
            'notifyWebpush' => (bool) $students->first()?->notify_webpush,
            'vapidPublicKey' => config('services.vapid.public_key'),
        ]);
    }

    /**
     * Halaman satu santri — tujuan link notifikasi (WA & push) supaya wali
     * dengan lebih dari satu anak langsung diarahkan ke anak yang bersangkutan,
     * bukan mendarat di dashboard umum lalu harus cari sendiri.
     */
    public function studentDetail(Request $request, TpqStudent $student): Response
    {
        $account = $this->currentAccount($request);
        abort_unless($account->students()->where('tpq_students.id', $student->id)->exists(), 403);

        $reportCards = TpqReportCard::where('student_id', $student->id)
            ->with('semester:id,name')
            ->latest('created_at')
            ->get();

        $dailyProgress = TpqDailyProgress::where('student_id', $student->id)
            ->with('recorder:id,name')
            ->latest('date')
            ->limit(20)
            ->get();

        $sppBills = TpqSppBill::where('student_id', $student->id)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(12)
            ->get();

        return Inertia::render('Wali/StudentDetail', [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'nis' => $student->nis,
                'photo' => $student->photo,
            ],
            'reportCards' => $reportCards,
            'dailyProgress' => $dailyProgress->map(fn (TpqDailyProgress $p) => [
                'date' => $p->date->toDateString(),
                'summary' => $p->summary(),
                'keterangan' => $p->keterangan,
                'catatan' => $p->catatan,
                'recorded_by' => $p->recorder?->name,
            ]),
            'sppBills' => $sppBills->map(fn (TpqSppBill $b) => [
                'id' => $b->id,
                'month' => $b->month,
                'year' => $b->year,
                'amount' => (float) $b->amount,
                'paid_amount' => (float) $b->paid_amount,
                'status' => $b->status,
                'proof_status' => $b->proof_status,
                'proof_file_url' => $b->proof_file_url,
                'proof_rejection_reason' => $b->proof_rejection_reason,
            ]),
        ]);
    }

    /**
     * Wali kirim bukti transfer Infaq sendiri lewat portal — admin yang
     * memverifikasi ke rekening lalu Setujui/Tolak (lihat
     * TpqSppController::approveProof/rejectProof). Bill yang sudah lunas atau
     * masih menunggu review sebelumnya sengaja ditolak di sini supaya tidak ada
     * bukti dobel yang tumpang tindih untuk tagihan yang sama.
     */
    public function sppUploadProof(Request $request, TpqSppBill $bill): RedirectResponse
    {
        $account = $this->currentAccount($request);
        abort_unless($account->students()->where('tpq_students.id', $bill->student_id)->exists(), 403);

        if ($bill->status === 'paid') {
            return back()->with('error', 'Tagihan ini sudah lunas.');
        }
        if ($bill->proof_status === 'pending') {
            return back()->with('error', 'Bukti transfer untuk tagihan ini sudah dikirim dan masih menunggu konfirmasi.');
        }

        $data = $request->validate([
            'proof_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        if ($bill->proof_file) {
            Storage::disk('public')->delete($bill->proof_file);
        }

        $bill->update([
            'proof_file' => $request->file('proof_file')->store('spp-proofs', 'public'),
            'proof_status' => 'pending',
            'proof_submitted_at' => now(),
            'proof_rejection_reason' => null,
        ]);

        return back()->with('success', 'Bukti transfer berhasil dikirim, menunggu konfirmasi admin.');
    }

    public function updateNotificationPreferences(Request $request): RedirectResponse
    {
        $account = $this->currentAccount($request);

        $data = $request->validate([
            'notify_whatsapp' => ['required', 'boolean'],
            'notify_webpush' => ['required', 'boolean'],
        ]);

        TpqStudent::whereIn('id', $account->students()->pluck('tpq_students.id'))->update($data);

        return back()->with('success', 'Preferensi notifikasi disimpan.');
    }

    public function pushSubscribe(Request $request): RedirectResponse
    {
        $account = $this->currentAccount($request);

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        TpqGuardianPushSubscription::updateOrCreate(
            ['wali_account_id' => $account->id, 'endpoint' => $data['endpoint']],
            ['public_key' => $data['keys']['p256dh'], 'auth_token' => $data['keys']['auth']],
        );

        TpqStudent::whereIn('id', $account->students()->pluck('tpq_students.id'))->update(['notify_webpush' => true]);

        return back()->with('success', 'Notifikasi push diaktifkan.');
    }

    public function pushUnsubscribe(Request $request): RedirectResponse
    {
        $account = $this->currentAccount($request);

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        TpqGuardianPushSubscription::where('wali_account_id', $account->id)->where('endpoint', $data['endpoint'])->delete();

        if (! TpqGuardianPushSubscription::where('wali_account_id', $account->id)->exists()) {
            TpqStudent::whereIn('id', $account->students()->pluck('tpq_students.id'))->update(['notify_webpush' => false]);
        }

        return back()->with('success', 'Notifikasi push dinonaktifkan.');
    }

    public function reportCard(Request $request, TpqReportCard $reportCard): Response
    {
        $account = $this->currentAccount($request);
        $reportCard->load(['student', 'class', 'semester.academicYear']);

        abort_unless($account->students()->where('tpq_students.id', $reportCard->student_id)->exists(), 403);

        return Inertia::render('Wali/ReportCard', ['reportCard' => $reportCard]);
    }

    public function reportCardPdf(Request $request, TpqReportCard $reportCard): \Symfony\Component\HttpFoundation\Response
    {
        $account = $this->currentAccount($request);
        $reportCard->loadMissing('student');

        abort_unless($account->students()->where('tpq_students.id', $reportCard->student_id)->exists(), 403);
        abort_unless($reportCard->pdf_path && Storage::disk('public')->exists($reportCard->pdf_path), 404);

        if ($request->boolean('inline')) {
            return response()->file(Storage::disk('public')->path($reportCard->pdf_path));
        }

        return Storage::disk('public')->download($reportCard->pdf_path, "raport-{$reportCard->student->name}.pdf");
    }

    private function currentAccount(Request $request): WaliAccount
    {
        $account = WaliAccount::find($request->session()->get('wali_account_id'));

        abort_unless($account, 403);

        return $account;
    }
}
