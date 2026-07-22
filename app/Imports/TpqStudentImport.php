<?php

namespace App\Imports;

use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqStudent;
use App\Models\TpqStudentClass;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class TpqStudentImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    private ?TpqAcademicYear $activeYear;

    /** @var array<string, string> nama kelas (lowercase) => id, di-cache sekali per import */
    private array $classesByName = [];

    public function __construct(private string $masjidId)
    {
        $this->activeYear = TpqAcademicYear::where('masjid_id', $masjidId)->where('is_active', true)->first();

        $this->classesByName = TpqClass::where('masjid_id', $masjidId)
            ->get(['id', 'name'])
            ->mapWithKeys(fn (TpqClass $class) => [Str::lower($class->name) => $class->id])
            ->all();
    }

    public function model(array $row): ?TpqStudent
    {
        if (empty($row['name']) || empty($row['gender']) || empty($row['guardian_phone'])) {
            return null;
        }

        $nis = ! empty($row['nis']) ? (string) $row['nis'] : $this->generateNis();

        $student = TpqStudent::create([
            'masjid_id' => $this->masjidId,
            'nis' => $nis,
            'name' => $row['name'],
            'nik' => $row['nik'] ?? null,
            'birth_place' => $row['birth_place'] ?? null,
            'birth_date' => ! empty($row['birth_date']) ? $row['birth_date'] : null,
            'gender' => Str::upper($row['gender']) === 'P' ? 'P' : 'L',
            'address' => $row['address'] ?? null,
            'father_name' => $row['father_name'] ?? null,
            'mother_name' => $row['mother_name'] ?? null,
            'guardian_name' => $row['guardian_name'] ?? null,
            'guardian_phone' => (string) $row['guardian_phone'],
            'guardian_whatsapp' => ! empty($row['guardian_whatsapp']) ? (string) $row['guardian_whatsapp'] : null,
            'guardian_password' => $nis,
            'parent_occupation' => $row['parent_occupation'] ?? null,
            'status' => 'aktif',
            'entry_date' => ! empty($row['entry_date']) ? $row['entry_date'] : now()->toDateString(),
        ]);

        $this->imported++;

        $classId = $this->activeYear && ! empty($row['kelas'])
            ? ($this->classesByName[Str::lower($row['kelas'])] ?? null)
            : null;

        if ($classId) {
            TpqStudentClass::create([
                'student_id' => $student->id,
                'class_id' => $classId,
                'academic_year_id' => $this->activeYear->id,
            ]);
        }

        return $student;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'gender' => ['required'],
            'guardian_phone' => ['required'],
            'nis' => ['nullable', 'unique:tpq_students,nis'],
        ];
    }

    private function generateNis(): string
    {
        $year = now()->format('y');
        $count = TpqStudent::withTrashed()->where('masjid_id', $this->masjidId)->where('nis', 'like', "{$year}%")->count() + 1;

        do {
            $candidate = $year.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
            $count++;
        } while (TpqStudent::withTrashed()->where('nis', $candidate)->exists());

        return $candidate;
    }
}
