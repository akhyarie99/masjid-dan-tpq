<?php

namespace App\Http\Controllers\Tpq;

use App\Http\Controllers\Controller;
use App\Models\TpqAcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TpqAcademicYearController extends Controller
{
    public function index(Request $request): Response
    {
        $years = TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)
            ->withCount('semesters')
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('Learning/Tpq/AcademicYears/Index', ['years' => $years]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateYear($request);

        if ($data['is_active'] ?? false) {
            TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)->update(['is_active' => false]);
        }

        TpqAcademicYear::create([...$data, 'masjid_id' => $request->user()->masjid_id]);

        return back()->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function update(Request $request, TpqAcademicYear $tahunAjaran): RedirectResponse
    {
        $data = $this->validateYear($request);

        if ($data['is_active'] ?? false) {
            TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)->update(['is_active' => false]);
        }

        $tahunAjaran->update($data);

        return back()->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    public function destroy(TpqAcademicYear $tahunAjaran): RedirectResponse
    {
        $tahunAjaran->delete();

        return back()->with('success', 'Tahun ajaran berhasil dihapus.');
    }

    private function validateYear(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
        ]);
    }
}
