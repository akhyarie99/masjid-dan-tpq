<?php

namespace App\Http\Controllers\Jamaah;

use App\Http\Controllers\Controller;
use App\Models\JamaahProfile;
use App\Models\SocialProgram;
use App\Models\SocialProgramRecipient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SocialProgramController extends Controller
{
    public function index(Request $request): Response
    {
        $programs = SocialProgram::where('masjid_id', $request->user()->masjid_id)
            ->withCount('recipients')
            ->latest('start_date')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Jamaah/SocialPrograms/Index', ['programs' => $programs]);
    }

    public function create(): Response
    {
        return Inertia::render('Jamaah/SocialPrograms/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        SocialProgram::create([
            ...$this->validateProgram($request),
            'masjid_id' => $request->user()->masjid_id,
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('admin.jamaah.program-sosial.index')->with('success', 'Program sosial berhasil dibuat.');
    }

    public function show(Request $request, SocialProgram $programSosial): Response
    {
        return Inertia::render('Jamaah/SocialPrograms/Show', [
            'program' => $programSosial,
            'recipients' => $programSosial->recipients()->orderBy('name')->get(),
            'jamaahOptions' => JamaahProfile::where('masjid_id', $request->user()->masjid_id)->orderBy('name')->get(['id', 'name', 'phone', 'address']),
        ]);
    }

    public function update(Request $request, SocialProgram $programSosial): RedirectResponse
    {
        $programSosial->update($this->validateProgram($request));

        return back()->with('success', 'Program sosial berhasil diperbarui.');
    }

    public function destroy(SocialProgram $programSosial): RedirectResponse
    {
        $programSosial->delete();

        return redirect()->route('admin.jamaah.program-sosial.index')->with('success', 'Program sosial berhasil dihapus.');
    }

    public function addRecipient(Request $request, SocialProgram $programSosial): RedirectResponse
    {
        $data = $request->validate([
            'jamaah_id' => ['nullable', 'uuid', 'exists:jamaah_profiles,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
        ]);

        $programSosial->recipients()->create($data);

        return back()->with('success', 'Penerima berhasil ditambahkan.');
    }

    public function distribute(Request $request, SocialProgram $programSosial, SocialProgramRecipient $recipient): RedirectResponse
    {
        $data = $request->validate([
            'aid_type' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $recipient->update([
            ...$data,
            'distributed_at' => now(),
            'distributed_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Distribusi berhasil dicatat.');
    }

    public function receipt(SocialProgram $programSosial, SocialProgramRecipient $recipient): \Illuminate\Http\Response
    {
        $recipient->load('program.masjid');

        $pdf = Pdf::loadView('pdf.social-program-receipt', [
            'program' => $recipient->program,
            'masjid' => $recipient->program->masjid,
            'recipient' => $recipient,
        ]);

        return $pdf->download("tanda-terima-{$recipient->name}.pdf");
    }

    public function report(SocialProgram $programSosial): \Illuminate\Http\Response
    {
        $recipients = $programSosial->recipients()->whereNotNull('distributed_at')->get();

        $pdf = Pdf::loadView('pdf.social-program-report', [
            'program' => $programSosial,
            'masjid' => $programSosial->masjid,
            'recipients' => $recipients,
            'totalAmount' => $recipients->sum('amount'),
        ]);

        return $pdf->download("laporan-distribusi-{$programSosial->name}.pdf");
    }

    private function validateProgram(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'budget' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:draft,active,closed'],
        ]);
    }
}
