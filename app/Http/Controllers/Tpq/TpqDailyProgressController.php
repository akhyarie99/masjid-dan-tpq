<?php

namespace App\Http\Controllers\Tpq;

use App\Http\Controllers\Controller;
use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqDailyProgress;
use App\Models\TpqStudentClass;
use App\Services\GuardianNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TpqDailyProgressController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Learning/Tpq/DailyProgress/Index', [
            'classes' => TpqClass::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->orderBy('order')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, TpqClass $class): Response
    {
        $date = $request->string('date')->toString() ?: now()->toDateString();
        $activeYear = TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->first();

        $students = TpqStudentClass::with('student:id,name,nis,photo')
            ->where('class_id', $class->id)
            ->where('academic_year_id', $activeYear?->id)
            ->get()
            ->pluck('student')
            ->filter();

        $todayEntries = TpqDailyProgress::where('class_id', $class->id)
            ->whereDate('date', $date)
            ->get()
            ->keyBy('student_id');

        // Isi awal method/jilid/surah dari entri terakhir tiap santri, biar ustadz
        // tinggal lanjutkan progres sebelumnya alih-alih mengetik ulang dari kosong.
        $lastEntries = TpqDailyProgress::whereIn('student_id', $students->pluck('id'))
            ->whereDate('date', '<', $date)
            ->orderByDesc('date')
            ->get()
            ->unique('student_id')
            ->keyBy('student_id');

        return Inertia::render('Learning/Tpq/DailyProgress/Show', [
            'class' => $class->only(['id', 'name']),
            'date' => $date,
            'students' => $students->map(function ($student) use ($todayEntries, $lastEntries) {
                $entry = $todayEntries->get($student->id) ?? $lastEntries->get($student->id);

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'photo' => $student->photo,
                    'filled' => $todayEntries->has($student->id),
                    'method' => $entry?->method ?? 'iqro',
                    'jilid' => $todayEntries->get($student->id)?->jilid ?? $entry?->jilid,
                    'halaman' => $todayEntries->get($student->id)?->halaman ?? $entry?->halaman,
                    'surah' => $todayEntries->get($student->id)?->surah ?? $entry?->surah,
                    'ayat_awal' => $todayEntries->get($student->id)?->ayat_awal,
                    'ayat_akhir' => $todayEntries->get($student->id)?->ayat_akhir,
                    'keterangan' => $todayEntries->get($student->id)?->keterangan ?? 'lancar',
                    'catatan' => $todayEntries->get($student->id)?->catatan,
                ];
            })->values(),
        ]);
    }

    public function store(Request $request, TpqClass $class, GuardianNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'entries' => ['required', 'array'],
            'entries.*.student_id' => ['required', 'uuid', 'exists:tpq_students,id'],
            'entries.*.method' => ['required', 'in:iqro,quran'],
            'entries.*.jilid' => ['nullable', 'integer', 'min:1', 'max:6', 'required_if:entries.*.method,iqro'],
            'entries.*.halaman' => ['nullable', 'integer', 'min:1'],
            'entries.*.surah' => ['nullable', 'string', 'max:255', 'required_if:entries.*.method,quran'],
            'entries.*.ayat_awal' => ['nullable', 'integer', 'min:1'],
            'entries.*.ayat_akhir' => ['nullable', 'integer', 'min:1'],
            'entries.*.keterangan' => ['required', 'in:lancar,ulang'],
            'entries.*.catatan' => ['nullable', 'string'],
        ]);

        foreach ($data['entries'] as $item) {
            $isNew = ! TpqDailyProgress::where('student_id', $item['student_id'])
                ->whereDate('date', $data['date'])
                ->exists();

            $values = [
                'class_id' => $class->id,
                'method' => $item['method'],
                'jilid' => $item['method'] === 'iqro' ? ($item['jilid'] ?? null) : null,
                'halaman' => $item['halaman'] ?? null,
                'surah' => $item['method'] === 'quran' ? ($item['surah'] ?? null) : null,
                'ayat_awal' => $item['method'] === 'quran' ? ($item['ayat_awal'] ?? null) : null,
                'ayat_akhir' => $item['method'] === 'quran' ? ($item['ayat_akhir'] ?? null) : null,
                'keterangan' => $item['keterangan'],
                'catatan' => $item['catatan'] ?? null,
                'recorded_by' => $request->user()->id,
            ];

            $progress = TpqDailyProgress::updateOrCreate(
                ['student_id' => $item['student_id'], 'date' => $data['date']],
                $values,
            );

            if ($isNew) {
                $progress->loadMissing('student');
                $notifier->notify(
                    $progress->student,
                    "Assalamu'alaikum, Ananda {$progress->student->name} hari ini mengaji: {$progress->summary()} ({$progress->keterangan}). Barakallahu fiik."
                );
                $progress->update(['notified_at' => now()]);
            }
        }

        return back()->with('success', 'Progres mengaji harian berhasil disimpan.');
    }
}
