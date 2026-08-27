<?php

namespace App\Http\Controllers\Ramadhan;

use App\Http\Controllers\Controller;
use App\Models\JamaahProfile;
use App\Models\QurbanDistribution;
use App\Models\QurbanRegistration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QurbanController extends Controller
{
    public function index(Request $request): Response
    {
        $year = $request->integer('year') ?: now()->year;
        $masjidId = $request->user()->masjid_id;

        $registrations = QurbanRegistration::where('masjid_id', $masjidId)->where('year', $year)->orderByDesc('created_at')->get();
        $distributions = QurbanDistribution::where('masjid_id', $masjidId)->where('year', $year)->orderByDesc('distributed_at')->get();

        $sapiShares = $registrations->where('animal_type', 'sapi')->sum('share_count');
        $kambingCount = $registrations->where('animal_type', 'kambing')->count();

        return Inertia::render('Ramadhan/Qurban/Index', [
            'year' => $year,
            'registrations' => $registrations,
            'distributions' => $distributions,
            'jamaahOptions' => JamaahProfile::where('masjid_id', $masjidId)->orderBy('name')->get(['id', 'name', 'address']),
            'summary' => [
                'totalShohibul' => $registrations->count(),
                'sapiEkor' => intdiv($sapiShares, 7) + ($sapiShares % 7 > 0 ? 1 : 0),
                'sapiShares' => $sapiShares,
                'kambingEkor' => $kambingCount,
                'totalDistributedKg' => (float) $distributions->sum('weight_kg'),
                'totalPenerima' => $distributions->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRegistration($request);

        QurbanRegistration::create([
            ...$data,
            'masjid_id' => $request->user()->masjid_id,
            'year' => $request->integer('year') ?: now()->year,
            'share_count' => $data['animal_type'] === 'sapi' ? ($data['share_count'] ?? 1) : 1,
        ]);

        return back()->with('success', 'Pendaftaran qurban berhasil dicatat.');
    }

    public function update(Request $request, QurbanRegistration $qurban): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $qurban);

        $qurban->update($this->validateRegistration($request));

        return back()->with('success', 'Data pendaftaran qurban berhasil diperbarui.');
    }

    public function destroy(Request $request, QurbanRegistration $qurban): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $qurban);

        $qurban->delete();

        return back()->with('success', 'Pendaftaran qurban berhasil dihapus.');
    }

    public function exportPdf(Request $request): \Illuminate\Http\Response
    {
        $year = $request->integer('year') ?: now()->year;
        $masjid = $request->user()->masjid;

        $registrations = QurbanRegistration::where('masjid_id', $masjid->id)->where('year', $year)->orderBy('shohibul_name')->get();

        $pdf = Pdf::loadView('pdf.qurban-list', ['masjid' => $masjid, 'year' => $year, 'registrations' => $registrations]);

        return $pdf->download("daftar-shohibul-qurban-{$year}.pdf");
    }

    public function storeDistribution(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'jamaah_id' => ['nullable', 'uuid', 'exists:jamaah_profiles,id'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'package_count' => ['required', 'integer', 'min:1'],
        ]);

        QurbanDistribution::create([
            ...$data,
            'masjid_id' => $request->user()->masjid_id,
            'year' => $request->integer('year') ?: now()->year,
            'distributed_at' => now(),
            'distributed_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Distribusi daging qurban berhasil dicatat.');
    }

    public function distributionLabel(Request $request, QurbanDistribution $distribution): \Illuminate\Http\Response
    {
        $this->authorizeSameMasjid($request, $distribution);

        $pdf = Pdf::loadView('pdf.qurban-label', ['distribution' => $distribution->load('masjid')])
            ->setPaper([0, 0, 283, 170]);

        return $pdf->stream("label-{$distribution->recipient_name}.pdf");
    }

    public function distributionReport(Request $request): \Illuminate\Http\Response
    {
        $year = $request->integer('year') ?: now()->year;
        $masjid = $request->user()->masjid;

        $distributions = QurbanDistribution::where('masjid_id', $masjid->id)->where('year', $year)->orderBy('recipient_name')->get();

        $pdf = Pdf::loadView('pdf.qurban-distribution-report', [
            'masjid' => $masjid,
            'year' => $year,
            'distributions' => $distributions,
            'totalKg' => $distributions->sum('weight_kg'),
        ]);

        return $pdf->download("laporan-distribusi-qurban-{$year}.pdf");
    }

    /**
     * QurbanRegistration/QurbanDistribution tidak punya global scope tenant —
     * route-model binding cuma cari berdasarkan id, jadi tanpa ini pengurus
     * tenant A yang tahu/menebak UUID pendaftaran/distribusi tenant B bisa
     * ubah/hapus/cetak labelnya lewat URL langsung.
     */
    private function authorizeSameMasjid(Request $request, QurbanRegistration|QurbanDistribution $model): void
    {
        abort_unless($model->masjid_id === $request->user()->masjid_id, 404);
    }

    private function validateRegistration(Request $request): array
    {
        return $request->validate([
            'shohibul_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'animal_type' => ['required', 'in:sapi,kambing'],
            'share_count' => ['nullable', 'integer', 'min:1', 'max:7'],
            'animal_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', 'in:pending,paid'],
        ]);
    }
}
