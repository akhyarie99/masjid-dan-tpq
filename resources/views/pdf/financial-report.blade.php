<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #16a34a; padding-bottom: 12px; }
        .header h1 { margin: 0; font-size: 16px; color: #15803d; }
        .header p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #f0fdf4; }
        .text-right { text-align: right; }
        .summary { width: 50%; margin-top: 16px; margin-left: auto; }
        .summary td { border: none; padding: 3px 8px; }
        .signature { margin-top: 50px; width: 100%; }
        .signature td { border: none; text-align: center; width: 33%; }
        .footer { margin-top: 8px; font-size: 9px; color: #64748b; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $masjid->name }}</h1>
        <p>{{ $masjid->address }}</p>
        <p><strong>Laporan Keuangan</strong> — Periode {{ $from->translatedFormat('d F Y') }} s/d {{ $to->translatedFormat('d F Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No. Ref</th>
                <th>Tanggal</th>
                <th>Tipe</th>
                <th>Kategori</th>
                <th>Keterangan</th>
                <th class="text-right">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->reference_number }}</td>
                    <td>{{ $transaction->transaction_date->format('d-m-Y') }}</td>
                    <td>{{ $transaction->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</td>
                    <td>{{ $transaction->category?->name }}</td>
                    <td>{{ $transaction->description }}</td>
                    <td class="text-right">{{ number_format((float) $transaction->amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;">Tidak ada transaksi pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr><td>Total Pemasukan</td><td class="text-right">Rp {{ number_format($summary['income'], 0, ',', '.') }}</td></tr>
        <tr><td>Total Pengeluaran</td><td class="text-right">Rp {{ number_format($summary['expense'], 0, ',', '.') }}</td></tr>
        <tr><td><strong>Saldo</strong></td><td class="text-right"><strong>Rp {{ number_format($summary['balance'], 0, ',', '.') }}</strong></td></tr>
    </table>

    <table class="signature">
        <tr>
            <td>Mengetahui,<br>Ketua Takmir<br><br><br><br>(______________________)</td>
            <td></td>
            <td>{{ $printedAt->translatedFormat('d F Y') }}<br>Bendahara<br><br><br><br>(______________________)</td>
        </tr>
    </table>

    <div class="footer">Dicetak otomatis oleh SiMasjid pada {{ $printedAt->format('d-m-Y H:i') }}</div>
</body>
</html>
