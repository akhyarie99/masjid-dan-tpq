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
        <p><strong>Laporan Distribusi Program Sosial</strong></p>
        <p>{{ $program->name }}</p>
    </div>

    <table>
        <thead>
            <tr><th>No</th><th>Nama Penerima</th><th>Alamat</th><th>Jenis Bantuan</th><th class="text-right">Nominal</th><th>Tanggal</th></tr>
        </thead>
        <tbody>
            @forelse ($recipients as $index => $recipient)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $recipient->name }}</td>
                    <td>{{ $recipient->address }}</td>
                    <td>{{ $recipient->aid_type }}</td>
                    <td class="text-right">{{ $recipient->amount ? number_format($recipient->amount, 0, ',', '.') : '-' }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($recipient->distributed_at)->format('d-m-Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;">Belum ada distribusi.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top:12px;">
        Total Penerima: {{ $recipients->count() }} orang | Total Bantuan: Rp {{ number_format($totalAmount, 0, ',', '.') }}
    </p>
</body>
</html>
