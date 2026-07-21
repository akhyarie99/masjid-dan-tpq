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
        .center { text-align: center; }
        .footer { margin-top: 8px; font-size: 9px; color: #64748b; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $masjid->name }}</h1>
        <p><strong>Daftar Hadir</strong> — {{ $activity->name }}</p>
        <p>{{ \Illuminate\Support\Carbon::parse($activity->start_at)->translatedFormat('d F Y, H:i') }} · {{ $activity->location }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="center" style="width:30px;">No</th>
                <th>Nama</th>
                <th>No. HP</th>
                <th class="center" style="width:80px;">Hadir</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($registrations as $index => $registration)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $registration->name }}</td>
                    <td>{{ $registration->phone }}</td>
                    <td class="center">{{ $registration->is_attended ? 'Ya' : 'Tidak' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="center">Belum ada pendaftar.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top:12px;">Total Pendaftar: {{ $registrations->count() }} | Hadir: {{ $registrations->where('is_attended', true)->count() }}</p>

    <div class="footer">Dicetak otomatis oleh SiMasjid pada {{ $printedAt->format('d-m-Y H:i') }}</div>
</body>
</html>
