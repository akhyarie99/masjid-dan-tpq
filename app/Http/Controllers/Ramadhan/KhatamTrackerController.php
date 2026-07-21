<?php

namespace App\Http\Controllers\Ramadhan;

use App\Http\Controllers\Controller;
use App\Models\KhatamTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KhatamTrackerController extends Controller
{
    public function index(Request $request): Response
    {
        $year = $request->integer('year') ?: now()->year;

        $trackers = KhatamTracker::where('masjid_id', $request->user()->masjid_id)
            ->where('year', $year)
            ->orderByDesc('is_completed')
            ->orderBy('participant_name')
            ->get();

        return Inertia::render('Ramadhan/Khatam/Index', [
            'year' => $year,
            'trackers' => $trackers,
            'summary' => [
                'total' => $trackers->count(),
                'completed' => $trackers->where('is_completed', true)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        KhatamTracker::create([
            ...$this->validateTracker($request),
            'masjid_id' => $request->user()->masjid_id,
            'year' => $request->integer('year') ?: now()->year,
        ]);

        return back()->with('success', 'Peserta khatam berhasil ditambahkan.');
    }

    public function update(Request $request, KhatamTracker $khatam): RedirectResponse
    {
        $data = $request->validate([
            'completed_juz' => ['required', 'array'],
            'completed_juz.*' => ['integer', 'min:1', 'max:30'],
        ]);

        $isCompleted = count($data['completed_juz']) >= 30;

        $khatam->update([
            'completed_juz' => $data['completed_juz'],
            'is_completed' => $isCompleted,
            'completed_date' => $isCompleted ? now()->toDateString() : null,
        ]);

        return back()->with('success', 'Progress khatam berhasil diperbarui.');
    }

    public function destroy(KhatamTracker $khatam): RedirectResponse
    {
        $khatam->delete();

        return back()->with('success', 'Peserta khatam berhasil dihapus.');
    }

    private function validateTracker(Request $request): array
    {
        return $request->validate([
            'participant_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
    }
}
