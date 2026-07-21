<?php

namespace App\Http\Controllers\Study;

use App\Http\Controllers\Controller;
use App\Models\StudySession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudySessionController extends Controller
{
    public function index(Request $request): Response
    {
        $sessions = StudySession::where('masjid_id', $request->user()->masjid_id)
            ->orderBy('day_of_week')
            ->get();

        return Inertia::render('Study/Sessions/Index', ['sessions' => $sessions]);
    }

    public function store(Request $request): RedirectResponse
    {
        StudySession::create([...$this->validateSession($request), 'masjid_id' => $request->user()->masjid_id]);

        return back()->with('success', 'Sesi kajian berhasil ditambahkan.');
    }

    public function update(Request $request, StudySession $sesi): RedirectResponse
    {
        $sesi->update($this->validateSession($request));

        return back()->with('success', 'Sesi kajian berhasil diperbarui.');
    }

    public function destroy(StudySession $sesi): RedirectResponse
    {
        $sesi->delete();

        return back()->with('success', 'Sesi kajian berhasil dihapus.');
    }

    private function validateSession(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'ustadz_name' => ['required', 'string', 'max:255'],
            'ustadz_phone' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string'],
            'day_of_week' => ['nullable', 'in:senin,selasa,rabu,kamis,jumat,sabtu,ahad'],
            'time' => ['nullable', 'date_format:H:i'],
            'location' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
    }
}
