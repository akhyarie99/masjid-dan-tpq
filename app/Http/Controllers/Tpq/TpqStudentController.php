<?php

namespace App\Http\Controllers\Tpq;

use App\Http\Controllers\Controller;
use App\Imports\TpqStudentImport;
use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqStudent;
use App\Models\TpqStudentClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TpqStudentController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $masjidId = $request->user()->masjid_id;
        $activeYear = TpqAcademicYear::where('masjid_id', $masjidId)->where('is_active', true)->first();

        $query = TpqStudent::where('masjid_id', $masjidId)
            ->with(['studentClasses' => fn ($q) => $q->where('academic_year_id', $activeYear?->id)->with('class:id,name')]);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('nis', 'like', "%{$search}%"));
        }
        if ($request->filled('class_id')) {
            $query->whereHas('studentClasses', fn ($q) => $q->where('class_id', $request->string('class_id'))->where('academic_year_id', $activeYear?->id));
        }

        return Inertia::render('Learning/Tpq/Students/Index', [
            'students' => $query->orderBy('name')->paginate(15)->withQueryString(),
            'filters' => $request->only(['status', 'search', 'class_id']),
            'classes' => TpqClass::where('masjid_id', $masjidId)->orderBy('order')->get(['id', 'name']),
            'activeYear' => $activeYear,
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        return Inertia::render('Learning/Tpq/Students/Form', [
            'classes' => TpqClass::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->orderBy('order')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateStudent($request);
        $masjidId = $request->user()->masjid_id;
        $nis = ($data['nis'] ?? null) ?: $this->generateNis($masjidId);

        $student = TpqStudent::create([
            ...collect($data)->except('class_id')->all(),
            'masjid_id' => $masjidId,
            'nis' => $nis,
            'guardian_password' => $nis,
        ]);

        $activeYear = TpqAcademicYear::where('masjid_id', $masjidId)->where('is_active', true)->first();

        if ($activeYear && ! empty($data['class_id'])) {
            TpqStudentClass::create([
                'student_id' => $student->id,
                'class_id' => $data['class_id'],
                'academic_year_id' => $activeYear->id,
            ]);
        }

        return redirect()->route('admin.tpq.santri.index')->with('success', 'Santri berhasil ditambahkan.');
    }

    public function edit(Request $request, TpqStudent $santri): InertiaResponse
    {
        $activeYear = TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->first();
        $currentClass = TpqStudentClass::where('student_id', $santri->id)->where('academic_year_id', $activeYear?->id)->first();

        return Inertia::render('Learning/Tpq/Students/Form', [
            'student' => $santri,
            'currentClassId' => $currentClass?->class_id,
            'classes' => TpqClass::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->orderBy('order')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, TpqStudent $santri): RedirectResponse
    {
        $data = $this->validateStudent($request, $santri->id);

        $santri->update(collect($data)->except('class_id')->all());

        $activeYear = TpqAcademicYear::where('masjid_id', $request->user()->masjid_id)->where('is_active', true)->first();

        if ($activeYear && ! empty($data['class_id'])) {
            TpqStudentClass::updateOrCreate(
                ['student_id' => $santri->id, 'academic_year_id' => $activeYear->id],
                ['class_id' => $data['class_id']]
            );
        }

        return redirect()->route('admin.tpq.santri.index')->with('success', 'Data santri berhasil diperbarui.');
    }

    public function destroy(TpqStudent $santri): RedirectResponse
    {
        $santri->delete();

        return back()->with('success', 'Santri berhasil dihapus.');
    }

    public function card(TpqStudent $student): InertiaResponse
    {
        $qrSvg = base64_encode(QrCode::size(160)->generate($student->id));

        return Inertia::render('Learning/Tpq/Students/Card', [
            'student' => $student->only(['id', 'nis', 'name', 'photo']),
            'masjidName' => $student->masjid->name,
            'qrCode' => "data:image/svg+xml;base64,{$qrSvg}",
        ]);
    }

    public function importTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $path = storage_path('app/templates/tpq-santri-import-template.csv');

        if (! file_exists($path)) {
            @mkdir(dirname($path), 0755, true);
            file_put_contents(
                $path,
                "nis,name,gender,birth_place,birth_date,father_name,mother_name,guardian_name,guardian_phone,guardian_whatsapp,parent_occupation,address,entry_date,kelas\n"
            );
        }

        return Response::download($path, 'template-import-santri.csv');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,xlsx,xls']]);

        $import = new TpqStudentImport($request->user()->masjid_id);
        Excel::import($import, $request->file('file'));

        $failedCount = $import->failures()->count();
        $message = "{$import->imported} santri berhasil diimpor.";
        if ($failedCount > 0) {
            $message .= " {$failedCount} baris dilewati karena data tidak lengkap/tidak valid.";
        }

        return back()->with('success', $message);
    }

    private function generateNis(string $masjidId): string
    {
        $year = now()->format('y');
        $count = TpqStudent::withTrashed()->where('masjid_id', $masjidId)->where('nis', 'like', "{$year}%")->count();

        return $year.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    private function validateStudent(Request $request, ?string $studentId = null): array
    {
        return $request->validate([
            'nis' => ['nullable', 'string', 'max:50', 'unique:tpq_students,nis,'.$studentId],
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['nullable', 'string', 'max:30'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['required', 'in:L,P'],
            'address' => ['nullable', 'string'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_phone' => ['required', 'string', 'max:30'],
            'guardian_whatsapp' => ['nullable', 'string', 'max:30'],
            'parent_occupation' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:aktif,cuti,lulus,keluar'],
            'entry_date' => ['required', 'date'],
            'exit_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'class_id' => ['nullable', 'uuid', 'exists:tpq_classes,id'],
        ]);
    }
}
