<?php

namespace App\Http\Controllers\Tpq;

use App\Http\Controllers\Controller;
use App\Models\TpqAcademicYear;
use App\Models\TpqSemester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TpqSemesterController extends Controller
{
    public function index(Request $request): Response
    {
        $semesters = TpqSemester::with('academicYear:id,name')
            ->whereHas('academicYear', fn ($q) => $q->where('masjid_id', $request->user()->masjid_id))
            ->orderByDesc('start_date')
            ->get();

        return Inertia::render('Learning/Tpq/Semesters/Index', [
            'semesters' => $semesters,
            'academicYears' => TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)->orderByDesc('start_date')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateSemester($request);

        if ($data['is_active'] ?? false) {
            TpqSemester::whereHas('academicYear', fn ($q) => $q->where('masjid_id', $request->user()->masjid_id))->update(['is_active' => false]);
        }

        TpqSemester::create($data);

        return back()->with('success', 'Semester berhasil ditambahkan.');
    }

    public function update(Request $request, TpqSemester $semester): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $semester);

        $data = $this->validateSemester($request);

        if ($data['is_active'] ?? false) {
            TpqSemester::whereHas('academicYear', fn ($q) => $q->where('masjid_id', $request->user()->masjid_id))->update(['is_active' => false]);
        }

        $semester->update($data);

        return back()->with('success', 'Semester berhasil diperbarui.');
    }

    public function destroy(Request $request, TpqSemester $semester): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $semester);

        $semester->delete();

        return back()->with('success', 'Semester berhasil dihapus.');
    }

    /**
     * TpqSemester tidak punya kolom masjid_id maupun global scope tenant —
     * kepemilikannya diturunkan dari tahun ajaran induknya. Tanpa ini admin
     * tenant A yang tahu/menebak UUID semester tenant B bisa ubah/hapus datanya
     * lewat URL langsung.
     */
    private function authorizeSameMasjid(Request $request, TpqSemester $semester): void
    {
        abort_unless($semester->academicYear?->masjid_id === $request->user()->masjid_id, 404);
    }

    private function validateSemester(Request $request): array
    {
        return $request->validate([
            'academic_year_id' => ['required', 'uuid', 'exists:tpq_academic_years,id'],
            'number' => ['required', 'integer', 'in:1,2'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
        ]);
    }
}
