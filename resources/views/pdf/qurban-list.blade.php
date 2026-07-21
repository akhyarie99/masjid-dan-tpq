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
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $masjid->name }}</h1>
        <p><strong>Daftar Shohibul Qurban</strong> — {{ $year }} H</p>
    </div>

    <table>
        <thead>
            <tr><th>No</th><th>Nama Shohibul</th><th>No. HP</th><th>Jenis Hewan</th><th>Bagian</th><th>Nama Hewan</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse ($registrations as $index => $registration)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $registration->shohibul_name }}</td>
                    <td>{{ $registration->phone }}</td>
                    <td class="capitalize">{{ ucfirst($registration->animal_type) }}</td>
                    <td>{{ $registration->animal_type === 'sapi' ? $registration->share_count.'/7' : '1' }}</td>
                    <td>{{ $registration->animal_name ?? '-' }}</td>
                    <td>{{ $registration->payment_status === 'paid' ? 'Lunas' : 'Belum Lunas' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;">Belum ada pendaftaran qurban.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top:12px;">Total Shohibul: {{ $registrations->count() }} orang</p>
</body>
</html>
