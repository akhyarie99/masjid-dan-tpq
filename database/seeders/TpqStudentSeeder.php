<?php

namespace Database\Seeders;

use App\Models\Masjid;
use App\Models\TpqAcademicYear;
use App\Models\TpqClass;
use App\Models\TpqStudent;
use App\Models\TpqStudentClass;
use Illuminate\Database\Seeder;

class TpqStudentSeeder extends Seeder
{
    private array $students = [
        ['name' => 'Ahmad Fauzi', 'gender' => 'L', 'father' => 'Slamet Riyadi', 'mother' => 'Siti Aminah'],
        ['name' => 'Muhammad Rizki', 'gender' => 'L', 'father' => 'Budi Santoso', 'mother' => 'Dewi Lestari'],
        ['name' => 'Abdullah Hafiz', 'gender' => 'L', 'father' => 'Agus Salim', 'mother' => 'Rina Wati'],
        ['name' => 'Zaid Ibrahim', 'gender' => 'L', 'father' => 'Hendra Gunawan', 'mother' => 'Nurul Hidayah'],
        ['name' => 'Umar Faruq', 'gender' => 'L', 'father' => 'Dedi Kurniawan', 'mother' => 'Yuni Astuti'],
        ['name' => 'Ali Akbar', 'gender' => 'L', 'father' => 'Wahyu Nugroho', 'mother' => 'Fitriani'],
        ['name' => 'Hamzah Al-Fatih', 'gender' => 'L', 'father' => 'Eko Prasetyo', 'mother' => 'Wulandari'],
        ['name' => 'Yusuf Maulana', 'gender' => 'L', 'father' => 'Bambang Setiawan', 'mother' => 'Indah Permata'],
        ['name' => 'Ismail Ramadhan', 'gender' => 'L', 'father' => 'Joko Widodo', 'mother' => 'Sri Wahyuni'],
        ['name' => 'Bilal Saputra', 'gender' => 'L', 'father' => 'Rudi Hartono', 'mother' => 'Anisa Putri'],
        ['name' => 'Siti Aisyah', 'gender' => 'P', 'father' => 'Andi Firmansyah', 'mother' => 'Maya Sari'],
        ['name' => 'Fatimah Az-Zahra', 'gender' => 'P', 'father' => 'Hadi Prabowo', 'mother' => 'Lina Marlina'],
        ['name' => 'Khadijah Salsabila', 'gender' => 'P', 'father' => 'Yayan Suryana', 'mother' => 'Devi Oktaviani'],
        ['name' => 'Zahra Amelia', 'gender' => 'P', 'father' => 'Iwan Setiadi', 'mother' => 'Rahmawati'],
        ['name' => 'Nur Azizah', 'gender' => 'P', 'father' => 'Fajar Nugraha', 'mother' => 'Puspita Sari'],
        ['name' => 'Halimah Putri', 'gender' => 'P', 'father' => 'Taufik Hidayat', 'mother' => 'Erna Susanti'],
        ['name' => 'Maryam Safira', 'gender' => 'P', 'father' => 'Yudi Kusuma', 'mother' => 'Diah Ayu'],
        ['name' => 'Aisha Ramadhani', 'gender' => 'P', 'father' => 'Asep Saepudin', 'mother' => 'Kartika Sari'],
        ['name' => 'Salma Nur Fadilah', 'gender' => 'P', 'father' => 'Rian Hidayat', 'mother' => 'Novi Andriani'],
        ['name' => 'Hafshah Zahra', 'gender' => 'P', 'father' => 'Dian Saputra', 'mother' => 'Winda Sari'],
    ];

    private array $defaultClasses = ['Iqra 1', 'Iqra 2', 'Iqra 3', 'Al-Quran'];

    public function run(): void
    {
        $masjid = Masjid::firstOrFail();

        $academicYear = TpqAcademicYear::where('masjid_id', $masjid->id)->where('is_active', true)->first();

        if (! $academicYear) {
            $academicYear = TpqAcademicYear::create([
                'masjid_id' => $masjid->id,
                'name' => now()->year.'/'.(now()->year + 1),
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear(),
                'is_active' => true,
            ]);
        }

        $classes = TpqClass::where('masjid_id', $masjid->id)->orderBy('order')->get();

        if ($classes->isEmpty()) {
            $classes = collect($this->defaultClasses)->map(
                fn (string $name, int $order) => TpqClass::create([
                    'masjid_id' => $masjid->id,
                    'name' => $name,
                    'order' => $order,
                    'capacity' => 20,
                    'is_active' => true,
                ])
            );
        }

        foreach ($this->students as $index => $data) {
            $nis = now()->format('y').str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $phoneSuffix = str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

            $student = TpqStudent::firstOrCreate(
                ['masjid_id' => $masjid->id, 'nis' => $nis],
                [
                    'name' => $data['name'],
                    'gender' => $data['gender'],
                    'birth_place' => 'Jakarta',
                    'birth_date' => now()->subYears(rand(7, 12))->subDays(rand(0, 365)),
                    'father_name' => $data['father'],
                    'mother_name' => $data['mother'],
                    'guardian_name' => $data['father'],
                    'guardian_phone' => "0812{$phoneSuffix}0000",
                    'guardian_whatsapp' => "0812{$phoneSuffix}0000",
                    'guardian_password' => $nis,
                    'status' => 'aktif',
                    'entry_date' => now()->subMonths(rand(1, 12)),
                ]
            );

            $class = $classes[$index % $classes->count()];

            TpqStudentClass::firstOrCreate([
                'student_id' => $student->id,
                'academic_year_id' => $academicYear->id,
            ], [
                'class_id' => $class->id,
            ]);
        }
    }
}
