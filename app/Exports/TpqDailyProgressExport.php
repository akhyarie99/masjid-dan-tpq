<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TpqDailyProgressExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $entries) {}

    public function collection(): Collection
    {
        return $this->entries;
    }

    public function headings(): array
    {
        return ['Tanggal', 'Santri', 'NIS', 'Kelas', 'Metode', 'Jilid', 'Halaman', 'Surat', 'Ayat Awal', 'Ayat Akhir', 'Keterangan', 'Catatan', 'Dicatat Oleh'];
    }

    public function map($entry): array
    {
        return [
            $entry->date->format('Y-m-d'),
            $entry->student?->name,
            $entry->student?->nis,
            $entry->class?->name,
            $entry->method === 'iqro' ? 'Iqro' : "Al-Qur'an",
            $entry->jilid,
            $entry->halaman,
            $entry->surah,
            $entry->ayat_awal,
            $entry->ayat_akhir,
            $entry->keterangan === 'lancar' ? 'Lancar' : 'Ulang',
            $entry->catatan,
            $entry->recorder?->name,
        ];
    }
}
