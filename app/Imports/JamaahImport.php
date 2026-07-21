<?php

namespace App\Imports;

use App\Models\JamaahProfile;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class JamaahImport implements ToModel, WithHeadingRow, WithValidation
{
    public int $imported = 0;

    public function __construct(private string $masjidId) {}

    public function model(array $row): ?JamaahProfile
    {
        if (empty($row['name']) || empty($row['phone'])) {
            return null;
        }

        $this->imported++;

        return new JamaahProfile([
            'masjid_id' => $this->masjidId,
            'name' => $row['name'],
            'nik' => $row['nik'] ?? null,
            'birth_date' => $row['birth_date'] ?? null,
            'phone' => (string) $row['phone'],
            'address' => $row['address'] ?? null,
            'rt' => $row['rt'] ?? null,
            'rw' => $row['rw'] ?? null,
            'status' => $row['status'] ?? 'aktif',
            'tags' => ! empty($row['tags']) ? array_map('trim', explode(',', $row['tags'])) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'phone' => ['required'],
        ];
    }
}
