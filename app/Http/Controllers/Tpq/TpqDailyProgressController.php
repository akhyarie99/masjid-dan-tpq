<?php

namespace App\Http\Controllers\Tpq;

use App\Exports\TpqDailyProgressExport;
use App\Http\Controllers\Controller;
use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqDailyProgress;
use App\Models\TpqLevelPromotion;
use App\Models\TpqStudent;
use App\Models\TpqStudentClass;
use App\Services\GuardianNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

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
                'level_label' => $s->levelLabel(),
            ]);

        return response()->json(['students' => $students]);
    }

    /**
     * Dituju setelah scan QR (isi QR di kartu santri = student id, lihat
     * TpqStudentController::card) atau klik hasil pencarian nama.
     */
    public function showStudent(Request $request, TpqStudent $student): Response
    {
        $this->authorizeSameMasjid($request, $student);

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

        // method/jilid HARUS ikut jenjang resmi santri (current_method/
        // current_jilid), bukan tebak-tebakan dari entri terakhir — entri
        // terakhir bisa saja dari sebelum santri naik jilid. halaman/surah/ayat
        // masih boleh dilanjutkan dari entri terakhir, TAPI cuma kalau entri itu
        // memang di jenjang yang sama; kalau sudah naik jilid, itu bukan
        // lanjutan yang relevan lagi.
        $continuesFromLastEntry = $lastEntry
            && $lastEntry->method === $student->current_method
            && $lastEntry->jilid === $student->current_jilid;

        $next = $student->nextLevel();

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
                'method' => $todayEntry?->method ?? $student->current_method,
                'jilid' => $todayEntry?->jilid ?? $student->current_jilid,
                'halaman' => $todayEntry?->halaman ?? ($continuesFromLastEntry ? $lastEntry->halaman : null),
                'surah' => $todayEntry?->surah ?? ($continuesFromLastEntry ? $lastEntry->surah : null),
                'ayat_awal' => $todayEntry?->ayat_awal,
                'ayat_akhir' => $todayEntry?->ayat_akhir,
                'keterangan' => $todayEntry?->keterangan ?? 'lancar',
                'catatan' => $todayEntry?->catatan,
                'level_label' => $student->levelLabel(),
                'next_level_label' => $next ? ($next['method'] === 'quran' ? "Al-Qur'an" : "Iqro {$next['jilid']}") : null,
                'recent_promotions' => $student->levelPromotions()
                    ->with('promoter:id,name')
                    ->latest('created_at')
                    ->limit(3)
                    ->get()
                    ->map(fn (TpqLevelPromotion $p) => [
                        'from_label' => $p->from_method === 'quran' ? "Al-Qur'an" : "Iqro {$p->from_jilid}",
                        'to_label' => $p->to_method === 'quran' ? "Al-Qur'an" : "Iqro {$p->to_jilid}",
                        'date' => $p->created_at->toDateString(),
                        'promoted_by' => $p->promoter?->name,
                    ]),
            ],
        ]);
    }

    public function promoteLevel(Request $request, TpqStudent $student): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $student);

        $next = $student->nextLevel();

        if (! $next) {
            return back()->with('error', "{$student->name} sudah di jenjang tertinggi (Al-Qur'an).");
        }

        TpqLevelPromotion::create([
            'student_id' => $student->id,
            'from_method' => $student->current_method,
            'from_jilid' => $student->current_jilid,
            'to_method' => $next['method'],
            'to_jilid' => $next['jilid'],
            'promoted_by' => $request->user()->id,
        ]);

        $student->update(['current_method' => $next['method'], 'current_jilid' => $next['jilid']]);

        return back()->with('success', "Jenjang {$student->name} naik ke {$student->fresh()->levelLabel()}.");
    }

    public function storeStudent(Request $request, TpqStudent $student, GuardianNotifier $notifier): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $student);

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

    public function recap(Request $request): Response
    {
        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;
        $classId = $request->string('class_id')->toString() ?: null;

        $entries = $this->recapQuery($request, (int) $month, (int) $year, $classId)
            ->orderByDesc('date')
            ->get();

        return Inertia::render('Learning/Tpq/DailyProgress/Recap', [
            'classes' => TpqClass::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->orderBy('order')->get(['id', 'name']),
            'month' => (int) $month,
            'year' => (int) $year,
            'classId' => $classId,
            'entries' => $entries->map(fn (TpqDailyProgress $e) => [
                'id' => $e->id,
                'date' => $e->date->toDateString(),
                'student_name' => $e->student?->name,
                'student_nis' => $e->student?->nis,
                'class_name' => $e->class?->name,
                'method' => $e->method,
                'summary' => $e->summary(),
                'keterangan' => $e->keterangan,
                'catatan' => $e->catatan,
                'recorded_by' => $e->recorder?->name,
            ]),
        ]);
    }

    public function exportRecap(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $month = $request->integer('month') ?: now()->month;
        $year = $request->integer('year') ?: now()->year;
        $classId = $request->string('class_id')->toString() ?: null;

        $entries = $this->recapQuery($request, (int) $month, (int) $year, $classId)->orderBy('date')->get();

        return Excel::download(new TpqDailyProgressExport($entries), "rekap-mengaji-harian-{$year}-{$month}.xlsx");
    }

    private function recapQuery(Request $request, int $month, int $year, ?string $classId)
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return TpqDailyProgress::with(['student:id,name,nis', 'class:id,name', 'recorder:id,name'])
            ->whereHas('student', fn ($q) => $q->where('masjid_id', $request->user()->masjid_id))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->when($classId, fn ($q) => $q->where('class_id', $classId));
    }

    public function show(Request $request, TpqClass $class): Response
    {
        $this->authorizeSameMasjid($request, $class);

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
        $this->authorizeSameMasjid($request, $class);

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
            // where('masjid_id', ...) di sini, BUKAN cuma find(id) — student_id
            // datang dari body request, cuma divalidasi exists:tpq_students,id
            // (tidak di-scope tenant), jadi tanpa ini admin tenant A bisa
            // menyisipkan UUID santri tenant B dan diam-diam menulis progres +
            // mengirim notifikasi WhatsApp ke wali tenant lain.
            $student = TpqStudent::where('masjid_id', $request->user()->masjid_id)
                ->find($item['student_id']);
            if (! $student) {
                continue;
            }

            $this->saveEntry($student, [...$item, 'date' => $data['date'], 'class_id' => $class->id], $request->user()->id, $notifier);
        }

        return back()->with('success', 'Progres mengaji harian berhasil disimpan.');
    }

    /**
     * TpqStudent/TpqClass tidak punya global scope tenant — route-model binding
     * cuma cari berdasarkan id, jadi tanpa ini admin tenant A yang tahu/menebak
     * UUID santri/kelas tenant B bisa lihat/isi progresnya lewat URL langsung.
     */
    private function authorizeSameMasjid(Request $request, TpqStudent|TpqClass $model): void
    {
        abort_unless($model->masjid_id === $request->user()->masjid_id, 404);
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
