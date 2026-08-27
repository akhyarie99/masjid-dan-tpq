<?php

namespace App\Http\Controllers\Tpq;

use App\Http\Controllers\Controller;
use App\Models\TpqHafalanProgress;
use App\Models\TpqStudent;
use App\Support\QuranSurahs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TpqHafalanController extends Controller
{
    public function show(Request $request, TpqStudent $student): Response
    {
        $this->authorizeSameMasjid($request, $student);

        $existing = TpqHafalanProgress::where('student_id', $student->id)->get()->keyBy('surah_number');

        $progress = collect(QuranSurahs::all())->map(function ($surah, $number) use ($existing, $student) {
            $record = $existing->get($number);

            if (! $record) {
                $record = TpqHafalanProgress::create([
                    'student_id' => $student->id,
                    'surah_number' => $number,
                    'surah_name' => $surah[0],
                    'total_ayah' => $surah[1],
                    'memorized_ayah' => 0,
                    'status' => 'belum',
                ]);
            }

            return [
                'surah_number' => $number,
                'surah_name' => $surah[0],
                'total_ayah' => $surah[1],
                'memorized_ayah' => $record->memorized_ayah,
                'status' => $record->status,
                'memorized_date' => $record->memorized_date,
            ];
        })->values();

        return Inertia::render('Learning/Tpq/Hafalan', [
            'student' => $student->only(['id', 'name', 'nis']),
            'progress' => $progress,
            'summary' => [
                'hafal' => $progress->where('status', 'hafal')->count(),
                'sedang' => $progress->where('status', 'sedang')->count(),
                'belum' => $progress->where('status', 'belum')->count(),
            ],
        ]);
    }

    public function update(Request $request, TpqStudent $student): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $student);

        $data = $request->validate([
            'surah_number' => ['required', 'integer', 'min:1', 'max:114'],
            'memorized_ayah' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:belum,sedang,hafal'],
        ]);

        TpqHafalanProgress::where('student_id', $student->id)
            ->where('surah_number', $data['surah_number'])
            ->update([
                'memorized_ayah' => $data['memorized_ayah'],
                'status' => $data['status'],
                'memorized_date' => $data['status'] === 'hafal' ? now()->toDateString() : null,
                'verified_by' => $request->user()->id,
            ]);

        return back()->with('success', 'Progress hafalan berhasil disimpan.');
    }

    /**
     * TpqStudent tidak punya global scope tenant — route-model binding cuma cari
     * berdasarkan id, jadi tanpa ini admin tenant A yang tahu/menebak UUID santri
     * tenant B bisa lihat/ubah progres hafalannya lewat URL langsung.
     */
    private function authorizeSameMasjid(Request $request, TpqStudent $student): void
    {
        abort_unless($student->masjid_id === $request->user()->masjid_id, 404);
    }
}
