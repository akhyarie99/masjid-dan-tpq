<?php

namespace App\Http\Controllers;

use App\Models\TpqDailyProgress;
use App\Models\TpqGuardianPushSubscription;
use App\Models\TpqReportCard;
use App\Models\TpqStudent;
use App\Models\WaliAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
    public function manifest(Request $request): \Illuminate\Http\JsonResponse
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
            ->latest('date')
            ->limit(20)
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
            ]),
        ]);
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
