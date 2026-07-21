<?php

namespace App\Console\Commands;

use App\Models\TpqSppBill;
use App\Models\TpqStudent;
use Illuminate\Console\Command;

class GenerateSppBills extends Command
{
    protected $signature = 'tpq:generate-spp-bills';

    protected $description = 'Buat tagihan SPP bulan berjalan untuk semua santri aktif (dijalankan tanggal 1 setiap bulan)';

    public function handle(): int
    {
        $month = now()->month;
        $year = now()->year;
        $students = TpqStudent::where('status', 'aktif')->get();
        $created = 0;

        foreach ($students as $student) {
            if (TpqSppBill::where('student_id', $student->id)->where('month', $month)->where('year', $year)->exists()) {
                continue;
            }

            $lastBill = TpqSppBill::where('student_id', $student->id)->latest('year')->latest('month')->first();
            $amount = $lastBill?->amount ?? 50000;
            $isScholarship = $lastBill?->is_scholarship ?? false;

            TpqSppBill::create([
                'student_id' => $student->id,
                'year' => $year,
                'month' => $month,
                'amount' => $amount,
                'status' => $isScholarship ? 'paid' : 'unpaid',
                'paid_amount' => $isScholarship ? $amount : 0,
                'is_scholarship' => $isScholarship,
            ]);

            $created++;
        }

        $this->info("{$created} tagihan SPP baru dibuat untuk bulan ini.");

        return self::SUCCESS;
    }
}
