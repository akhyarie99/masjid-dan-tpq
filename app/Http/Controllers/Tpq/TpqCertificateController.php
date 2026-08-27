<?php

namespace App\Http\Controllers\Tpq;

use App\Http\Controllers\Controller;
use App\Models\TpqCertificate;
use App\Models\TpqStudent;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TpqCertificateController extends Controller
{
    public function index(Request $request): Response
    {
        $certificates = TpqCertificate::with('student:id,name,nis')
            ->whereHas('student', fn ($q) => $q->where('masjid_id', $request->user()->masjid_id))
            ->latest('issued_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Learning/Tpq/Certificates/Index', ['certificates' => $certificates]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Learning/Tpq/Certificates/Form', [
            'students' => TpqStudent::where('masjid_id', $request->user()->masjid_id)->orderBy('name')->get(['id', 'name', 'nis']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCertificate($request);

        $certificate = TpqCertificate::create([
            ...$data,
            'certificate_number' => $this->generateNumber($request->user()->masjid_id),
            'issued_by' => $request->user()->id,
        ]);

        $this->generatePdf($certificate);

        return redirect()->route('admin.tpq.sertifikat.index')->with('success', 'Sertifikat berhasil diterbitkan.');
    }

    public function destroy(Request $request, TpqCertificate $sertifikat): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $sertifikat);

        $sertifikat->delete();

        return back()->with('success', 'Sertifikat berhasil dihapus.');
    }

    /**
     * TpqCertificate tidak punya kolom masjid_id maupun global scope tenant —
     * kepemilikannya diturunkan dari santri pemiliknya. Tanpa ini admin tenant A
     * yang tahu/menebak UUID sertifikat tenant B bisa menghapusnya lewat URL
     * langsung.
     */
    private function authorizeSameMasjid(Request $request, TpqCertificate $certificate): void
    {
        abort_unless($certificate->student?->masjid_id === $request->user()->masjid_id, 404);
    }

    private function generatePdf(TpqCertificate $certificate): void
    {
        $certificate->load('student.masjid');

        $pdf = Pdf::loadView('pdf.certificate', [
            'certificate' => $certificate,
            'student' => $certificate->student,
            'masjid' => $certificate->student->masjid,
        ])->setPaper('a4', 'landscape');

        $path = "tpq/certificates/{$certificate->id}.pdf";
        Storage::disk('public')->put($path, $pdf->output());

        $certificate->update(['pdf_path' => $path]);
    }

    private function generateNumber(string $masjidId): string
    {
        $year = now()->year;
        $count = TpqCertificate::whereHas('student', fn ($q) => $q->where('masjid_id', $masjidId))
            ->whereYear('issued_date', $year)
            ->count();

        return 'SERT/'.$year.'/'.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    private function validateCertificate(Request $request): array
    {
        return $request->validate([
            'student_id' => ['required', 'uuid', 'exists:tpq_students,id'],
            'type' => ['required', 'in:khatam_iqra,khatam_quran,tahfidz,ijazah'],
            'issued_date' => ['required', 'date'],
            'achievement' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
