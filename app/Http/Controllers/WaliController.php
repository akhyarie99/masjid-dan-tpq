<?php

namespace App\Http\Controllers;

use App\Models\TpqReportCard;
use App\Models\TpqStudent;
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

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $students = TpqStudent::where('guardian_phone', $credentials['phone'])->get();

        $matched = $students->first(fn (TpqStudent $s) => $s->guardian_password && Hash::check($credentials['password'], $s->guardian_password));

        if (! $matched) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor HP atau kata sandi salah.',
            ]);
        }

        $request->session()->put('wali_phone', $credentials['phone']);
        $request->session()->regenerate();

        return redirect()->route('wali.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('wali_phone');
        $request->session()->regenerate();

        return redirect()->route('wali.login');
    }

    public function dashboard(Request $request): Response
    {
        $phone = $request->session()->get('wali_phone');

        $students = TpqStudent::where('guardian_phone', $phone)
            ->with(['studentClasses' => fn ($q) => $q->whereHas('academicYear', fn ($aq) => $aq->where('is_active', true))->with('class:id,name')])
            ->get();

        $reportCards = TpqReportCard::whereIn('student_id', $students->pluck('id'))
            ->with('semester:id,name')
            ->latest('created_at')
            ->get()
            ->groupBy('student_id');

        return Inertia::render('Wali/Dashboard', [
            'students' => $students->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'nis' => $s->nis,
                'photo' => $s->photo,
                'class' => $s->studentClasses->first()?->class,
                'reportCards' => $reportCards->get($s->id, collect())->values(),
            ]),
        ]);
    }

    public function reportCard(Request $request, TpqReportCard $reportCard): Response
    {
        $phone = $request->session()->get('wali_phone');
        $reportCard->load(['student', 'class', 'semester.academicYear']);

        abort_unless($reportCard->student->guardian_phone === $phone, 403);

        return Inertia::render('Wali/ReportCard', ['reportCard' => $reportCard]);
    }

    public function reportCardPdf(Request $request, TpqReportCard $reportCard): \Symfony\Component\HttpFoundation\Response
    {
        $phone = $request->session()->get('wali_phone');
        $reportCard->loadMissing('student');

        abort_unless($reportCard->student->guardian_phone === $phone, 403);
        abort_unless($reportCard->pdf_path && Storage::disk('public')->exists($reportCard->pdf_path), 404);

        if ($request->boolean('inline')) {
            return response()->file(Storage::disk('public')->path($reportCard->pdf_path));
        }

        return Storage::disk('public')->download($reportCard->pdf_path, "raport-{$reportCard->student->name}.pdf");
    }
}
