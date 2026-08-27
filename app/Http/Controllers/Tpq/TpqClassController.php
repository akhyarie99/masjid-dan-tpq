<?php

namespace App\Http\Controllers\Tpq;

use App\Http\Controllers\Controller;
use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqClassTeacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TpqClassController extends Controller
{
    public function index(Request $request): Response
    {
        $masjidId = $request->user()->masjid_id;

        $classes = TpqClass::where('masjid_id', $masjidId)
            ->withCount('studentClasses')
            ->with('teachers.teacher:id,name')
            ->orderBy('order')
            ->get()
            ->map(fn (TpqClass $class) => [
                ...$class->toArray(),
                'teacher_ids' => $class->teachers->pluck('teacher_id'),
            ]);

        $activeYear = TpqAcademicYear::where('masjid_id', $masjidId)->where('is_active', true)->first();

        return Inertia::render('Learning/Tpq/Classes/Index', [
            'classes' => $classes,
            'teachers' => User::where('masjid_id', $masjidId)->role('ustadz')->get(['id', 'name']),
            'activeAcademicYear' => $activeYear?->only(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $class = TpqClass::create([...$this->validateClass($request), 'masjid_id' => $request->user()->masjid_id]);

        $this->syncTeachers($request, $class);

        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, TpqClass $kela): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $kela);

        $kela->update($this->validateClass($request));

        $this->syncTeachers($request, $kela);

        return back()->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Request $request, TpqClass $kela): RedirectResponse
    {
        $this->authorizeSameMasjid($request, $kela);

        $kela->delete();

        return back()->with('success', 'Kelas berhasil dihapus.');
    }

    /**
     * TpqClass tidak punya global scope tenant — route-model binding cuma cari
     * berdasarkan id, jadi tanpa ini admin tenant A yang tahu/menebak UUID kelas
     * tenant B bisa ubah/hapus datanya lewat URL langsung.
     */
    private function authorizeSameMasjid(Request $request, TpqClass $class): void
    {
        abort_unless($class->masjid_id === $request->user()->masjid_id, 404);
    }

    private function validateClass(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'order' => ['integer', 'min:0'],
            'capacity' => ['required', 'integer', 'min:1'],
            'room' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
    }

    private function syncTeachers(Request $request, TpqClass $class): void
    {
        if (! $request->has('teacher_ids')) {
            return;
        }

        $data = $request->validate([
            'teacher_ids' => ['array'],
            'teacher_ids.*' => ['uuid', 'exists:users,id'],
        ]);

        $teacherIds = $data['teacher_ids'] ?? [];

        if (empty($teacherIds)) {
            TpqClassTeacher::where('class_id', $class->id)->delete();

            return;
        }

        $activeYear = TpqAcademicYear::where('masjid_id', $class->masjid_id)->where('is_active', true)->first();

        if (! $activeYear) {
            throw ValidationException::withMessages([
                'teacher_ids' => 'Tidak ada tahun ajaran aktif. Aktifkan tahun ajaran terlebih dahulu di menu Tahun Ajaran sebelum menetapkan guru pengampu.',
            ]);
        }

        TpqClassTeacher::where('class_id', $class->id)
            ->where('academic_year_id', $activeYear->id)
            ->whereNotIn('teacher_id', $teacherIds)
            ->delete();

        foreach ($teacherIds as $teacherId) {
            TpqClassTeacher::firstOrCreate([
                'class_id' => $class->id,
                'academic_year_id' => $activeYear->id,
                'teacher_id' => $teacherId,
            ]);
        }
    }
}
