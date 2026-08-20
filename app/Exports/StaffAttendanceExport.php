<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StaffAttendanceExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $records) {}

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'Tanggal', 'Nama Staf', 'Jam Masuk', 'Jam Keluar',
            'Lokasi Masuk', 'Lokasi Pulang',
            'GPS Palsu (Masuk)', 'GPS Palsu (Pulang)',
            'Verifikasi Wajah OK (Masuk)', 'Verifikasi Wajah OK (Pulang)',
        ];
    }

    public function map($record): array
    {
        return [
            $record->date->format('Y-m-d'),
            $record->user?->name,
            $record->clock_in?->format('H:i'),
            $record->clock_out?->format('H:i'),
            $record->clockInLocation?->name,
            $record->clockOutLocation?->name,
            $record->clock_in_is_mock_location ? 'Ya' : 'Tidak',
            $record->clock_out_is_mock_location ? 'Ya' : 'Tidak',
            $record->clock_in ? ($record->clock_in_liveness_verified ? 'Ya' : 'Tidak') : '-',
            $record->clock_out ? ($record->clock_out_liveness_verified ? 'Ya' : 'Tidak') : '-',
        ];
    }
}
