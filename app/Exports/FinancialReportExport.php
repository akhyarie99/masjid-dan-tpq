<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FinancialReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $transactions) {}

    public function collection(): Collection
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return ['No. Referensi', 'Tanggal', 'Tipe', 'Kategori', 'Rekening Kas', 'Nominal', 'Keterangan', 'Status'];
    }

    public function map($transaction): array
    {
        return [
            $transaction->reference_number,
            $transaction->transaction_date->format('Y-m-d'),
            $transaction->type === 'income' ? 'Pemasukan' : 'Pengeluaran',
            $transaction->category?->name,
            $transaction->kasAccount?->name,
            (float) $transaction->amount,
            $transaction->description,
            $transaction->status,
        ];
    }
}
