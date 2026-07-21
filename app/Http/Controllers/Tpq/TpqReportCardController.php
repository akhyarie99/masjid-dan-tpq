<?php

namespace App\Http\Controllers\Tpq;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateReportCard;
use App\Jobs\SendReportCardWhatsApp;
use App\Models\TpqReportCard;
use App\Models\TpqSemester;
use App\Models\TpqStudent;
use App\Services\TpqReportCardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use ZipArchive;

class TpqReportCardController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Learning/Tpq/ReportCards/Index', [
            'semesters' => TpqSemester::whereHas('academicYear', fn ($q) => $q->where('masjid_id', $request->user()->masjid_id))
                ->orderByDesc('start_date')->get(['id', 'name']),
        ]);
    }

    public function semester(Request $request, TpqSemester $semester): Response
    {
        $masjidId = $request->user()->masjid_id;

        $students = TpqStudent::where('masjid_id', $masjidId)
            ->where('status', 'aktif')
            ->with(['reportCards' => fn ($q) => $q->where('semester_id', $semester->id)])
            ->when($request->filled('class_id'), fn ($q) => $q->whereHas(
                'studentClasses',
                fn ($sq) => $sq->where('class_id', $request->string('class_id'))->where('academic_year_id', $semester->academic_year_id)
            ))
            ->orderBy('name')
            ->get();

        return Inertia::render('Learning/Tpq/ReportCards/Semester', [
            'semester' => $semester,
            'students' => $students->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'nis' => $s->nis,
                'reportCard' => $s->reportCards->first(),
            ]),
        ]);
    }

    public function generate(TpqSemester $semester, TpqStudent $student, TpqReportCardService $service): RedirectResponse
    {
        $service->generate($student, $semester);

        return back()->with('success', "Raport {$student->name} berhasil dibuat.");
    }

    public function generateAll(Request $request, TpqSemester $semester): RedirectResponse
    {
        $students = TpqStudent::where('masjid_id', $request->user()->masjid_id)->where('status', 'aktif')->get();

        foreach ($students as $student) {
            GenerateReportCard::dispatch($student, $semester);
        }

        return back()->with('success', "Pembuatan raport untuk {$students->count()} santri sedang diproses di latar belakang.");
    }

    public function preview(TpqReportCard $reportCard): Response
    {
        $reportCard->load(['student', 'class', 'semester.academicYear']);

        return Inertia::render('Learning/Tpq/ReportCards/Preview', ['reportCard' => $reportCard]);
    }

    public function downloadPdf(Request $request, TpqReportCard $reportCard): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless($reportCard->pdf_path && Storage::disk('public')->exists($reportCard->pdf_path), 404);

        if ($request->boolean('inline')) {
            return response()->file(Storage::disk('public')->path($reportCard->pdf_path));
        }

        return Storage::disk('public')->download($reportCard->pdf_path, "raport-{$reportCard->student->name}.pdf");
    }

    public function downloadAll(TpqSemester $semester): \Symfony\Component\HttpFoundation\Response
    {
        $reportCards = TpqReportCard::with('student')->where('semester_id', $semester->id)->whereNotNull('pdf_path')->get();

        $zipPath = storage_path("app/private/tpq/raport-{$semester->id}.zip");
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($reportCards as $reportCard) {
            $fullPath = Storage::disk('public')->path($reportCard->pdf_path);
            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, "raport-{$reportCard->student->name}.pdf");
            }
        }

        $zip->close();

        return response()->download($zipPath)->deleteFileAfterSend();
    }

    public function sendWhatsApp(TpqReportCard $reportCard): RedirectResponse
    {
        SendReportCardWhatsApp::dispatch($reportCard);

        return back()->with('success', 'Raport sedang dikirim via WhatsApp.');
    }

    public function sendWhatsAppAll(TpqSemester $semester): RedirectResponse
    {
        $reportCards = TpqReportCard::where('semester_id', $semester->id)->whereNotNull('pdf_path')->get();

        foreach ($reportCards as $index => $reportCard) {
            SendReportCardWhatsApp::dispatch($reportCard)->delay(now()->addSeconds($index * 3));
        }

        return back()->with('success', 'Raport sedang dikirim ke seluruh wali murid.');
    }
}
