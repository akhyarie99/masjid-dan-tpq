<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\ZakatRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ZakatController extends Controller
{
    public function index(Request $request): Response
    {
        $masjidId = $request->user()->masjid_id;
        $year = $request->integer('year') ?: now()->year;

        $records = ZakatRecord::where('masjid_id', $masjidId)->where('year', $year)->get();

        return Inertia::render('Finance/Zakat/Index', [
            'year' => $year,
            'records' => $records->sortByDesc('created_at')->values(),
            'summary' => [
                'totalUang' => (float) $records->where('payment_type', 'uang')->sum('total_amount'),
                'totalBeras' => (float) $records->where('payment_type', 'beras')->sum('rice_kg'),
                'totalMuzakki' => $records->count(),
                'byType' => $records->groupBy('type')->map->count(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Finance/Zakat/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        ZakatRecord::create([...$this->validateRecord($request), 'masjid_id' => $request->user()->masjid_id]);

        return redirect()->route('admin.finance.zakat.index')->with('success', 'Data zakat berhasil dicatat.');
    }

    public function edit(Request $request, ZakatRecord $penerimaan): Response
    {
        $this->authorizeSameMasjid($request, $penerimaan);

        return Inertia::render('Finance/Zakat/Form', ['record' => $penerimaan]);
    }

    public function update(Request $request, ZakatRecord $penerimaan): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $penerimaan);

        $penerimaan->update($this->validateRecord($request));

        return redirect()->route('admin.finance.zakat.index')->with('success', 'Data zakat berhasil diperbarui.');
    }

    public function destroy(Request $request, ZakatRecord $penerimaan): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $penerimaan);

        $penerimaan->delete();

        return back()->with('success', 'Data zakat berhasil dihapus.');
    }

    /**
     * ZakatRecord tidak punya global scope tenant — route-model binding cuma cari
     * berdasarkan id, jadi tanpa ini pengurus tenant A yang tahu/menebak UUID
     * catatan zakat tenant B bisa lihat/ubah/hapus lewat URL langsung.
     */
    private function authorizeSameMasjid(Request $request, ZakatRecord $record): void
    {
        abort_unless($record->masjid_id === $request->user()->masjid_id, 404);
    }

    private function validateRecord(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:fitrah,maal,profesi,infaq'],
            'payer_name' => ['required', 'string', 'max:255'],
            'payer_phone' => ['nullable', 'string', 'max:30'],
            'dependents' => ['required', 'integer', 'min:1'],
            'amount_per_person' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'payment_type' => ['required', 'in:beras,uang'],
            'rice_kg' => ['nullable', 'numeric', 'min:0'],
            'year' => ['required', 'integer', 'min:2020'],
            'ramadhan' => ['boolean'],
        ]);
    }
}
