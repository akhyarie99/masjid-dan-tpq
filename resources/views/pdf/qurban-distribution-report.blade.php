<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #16a34a; padding-bottom: 12px; }
        .header h1 { margin: 0; font-size: 16px; color: #15803d; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #f0fdf4; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $masjid->name }}</h1>
        <p><strong>Laporan Distribusi Daging Qurban</strong> — {{ $year }} H</p>
    </div>

    <table>
        <thead><tr><th>No</th><th>Nama Penerima</th><th>Alamat</th><th class="text-right">Berat (kg)</th><th class="text-right">Paket</th><th>Tanggal</th></tr></thead>
        <tbody>
            @forelse ($distributions as $index => $distribution)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $distribution->recipient_name }}</td>
                    <td>{{ $distribution->address }}</td>
                    <td class="text-right">{{ $distribution->weight_kg ?? '-' }}</td>
                    <td class="text-right">{{ $distribution->package_count }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($distribution->distributed_at)->format('d-m-Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;">Belum ada distribusi.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top:12px;">
        Total Penerima: {{ $distributions->count() }} orang | Total Daging Dibagikan: {{ number_format($totalKg, 1) }} kg
    </p>
</body>
</html>
