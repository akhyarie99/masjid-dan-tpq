<?php

namespace App\Http\Controllers\Tpq;

use App\Http\Controllers\Controller;
use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqDailyProgress;
use App\Models\TpqStudent;
use App\Models\TpqStudentClass;
use App\Services\GuardianNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TpqDailyProgressController extends Controller
{
    /**
     * Landing page baru: scan QR atau cari nama santri, bukan daftar panjang
     * sekaligus — dengan ratusan santri, scroll-cari-nama itu sendiri yang lambat.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Learning/Tpq/DailyProgress/Index', [
            'date' => $request->string('date')->toString() ?: now()->toDateString(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->string('q'));

        if ($query === '') {
            return response()->json(['students' => []]);
        }

        $activeYear = TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->first();

        $students = TpqStudent::where('masjid_id', $request->user()->masjid_id)
            ->where('status', 'aktif')
            ->where(fn ($q) => $q->where('name', 'like', "%{$query}%")->orWhere('nis', 'like', "%{$query}%"))
            ->with(['studentClasses' => fn ($q) => $q->where('academic_year_id', $activeYear?->id)->with('class:id,name')])
            ->limit(10)
            ->get()
            ->map(fn (TpqStudent $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'nis' => $s->nis,
                'photo' => $s->photo,
                'class' => $s->studentClasses->first()?->class?->name,
            ]);

        return response()->json(['students' => $students]);
    }

    /**
     * Dituju setelah scan QR (isi QR di kartu santri = student id, lihat
     * TpqStudentController::card) atau klik hasil pencarian nama.
     */
    public function showStudent(Request $request, TpqStudent $student): Response
    {
        abort_unless($student->masjid_id === $request->user()->masjid_id, 404);

        $date = $request->string('date')->toString() ?: now()->toDateString();
        $activeYear = TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->first();

        $classData = TpqStudentClass::with('class:id,name')
            ->where('student_id', $student->id)
            ->where('academic_year_id', $activeYear?->id)
            ->first();

        $todayEntry = TpqDailyProgress::where('student_id', $student->id)->whereDate('date', $date)->first();
        $lastEntry = $todayEntry ?? TpqDailyProgress::where('student_id', $student->id)
            ->whereDate('date', '<', $date)
            ->orderByDesc('date')
            ->first();

        return Inertia::render('Learning/Tpq/DailyProgress/InputSantri', [
            'date' => $date,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'nis' => $student->nis,
                'photo' => $student->photo,
                'class' => $classData?->class?->name,
                'class_id' => $classData?->class_id,
                'filled' => (bool) $todayEntry,
                'method' => $lastEntry?->method ?? 'iqro',
                'jilid' => $lastEntry?->jilid,
                'halaman' => $todayEntry?->halaman ?? $lastEntry?->halaman,
                'surah' => $lastEntry?->surah,
                'ayat_awal' => $todayEntry?->ayat_awal,
                'ayat_akhir' => $todayEntry?->ayat_akhir,
                'keterangan' => $todayEntry?->keterangan ?? 'lancar',
                'catatan' => $todayEntry?->catatan,
            ],
        ]);
    }

    public function storeStudent(Request $request, TpqStudent $student, GuardianNotifier $notifier): RedirectResponse
    {
        abort_unless($student->masjid_id === $request->user()->masjid_id, 404);

        $data = $request->validate([
            'date' => ['required', 'date'],
            'class_id' => ['nullable', 'uuid', 'exists:tpq_classes,id'],
            'method' => ['required', 'in:iqro,quran'],
            'jilid' => ['nullable', 'integer', 'min:1', 'max:6', 'required_if:method,iqro'],
            'halaman' => ['nullable', 'integer', 'min:1'],
            'surah' => ['nullable', 'string', 'max:255', 'required_if:method,quran'],
            'ayat_awal' => ['nullable', 'integer', 'min:1'],
            'ayat_akhir' => ['nullable', 'integer', 'min:1'],
            'keterangan' => ['required', 'in:lancar,ulang'],
            'catatan' => ['nullable', 'string'],
        ]);

        $this->saveEntry($student, $data, $request->user()->id, $notifier);

        return back()->with('success', "Progres mengaji {$student->name} tersimpan, wali sudah diberi tahu.");
    }

    // === Alur lama: pilih kelas, isi sekaligus satu kelas (dipertahankan untuk rekap/kelas kecil) ===

    public function kelasIndex(Request $request): Response
    {
        return Inertia::render('Learning/Tpq/DailyProgress/KelasIndex', [
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
            $student = TpqStudent::find($item['student_id']);
            if (! $student) {
                continue;
            }

            $this->saveEntry($student, [...$item, 'date' => $data['date'], 'class_id' => $class->id], $request->user()->id, $notifier);
        }

        return back()->with('success', 'Progres mengaji harian berhasil disimpan.');
    }

    private function saveEntry(TpqStudent $student, array $data, string $recordedBy, GuardianNotifier $notifier): TpqDailyProgress
    {
        $isNew = ! TpqDailyProgress::where('student_id', $student->id)
            ->whereDate('date', $data['date'])
            ->exists();

        $progress = TpqDailyProgress::updateOrCreate(
            ['student_id' => $student->id, 'date' => $data['date']],
            [
                'class_id' => $data['class_id'] ?? null,
                'method' => $data['method'],
                'jilid' => $data['method'] === 'iqro' ? ($data['jilid'] ?? null) : null,
                'halaman' => $data['halaman'] ?? null,
                'surah' => $data['method'] === 'quran' ? ($data['surah'] ?? null) : null,
                'ayat_awal' => $data['method'] === 'quran' ? ($data['ayat_awal'] ?? null) : null,
                'ayat_akhir' => $data['method'] === 'quran' ? ($data['ayat_akhir'] ?? null) : null,
                'keterangan' => $data['keterangan'],
                'catatan' => $data['catatan'] ?? null,
                'recorded_by' => $recordedBy,
            ],
        );

        if ($isNew) {
            $notifier->notify(
                $student,
                "Assalamu'alaikum, Ananda {$student->name} hari ini mengaji: {$progress->summary()} ({$progress->keterangan}). Barakallahu fiik."
            );
            $progress->update(['notified_at' => now()]);
        }

        return $progress;
    }
}
