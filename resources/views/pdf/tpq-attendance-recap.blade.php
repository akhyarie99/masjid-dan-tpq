<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #16a34a; padding-bottom: 12px; }
        .header h1 { margin: 0; font-size: 15px; color: #15803d; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 8px; text-align: center; }
        th { background: #f0fdf4; }
        td.name { text-align: left; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $masjid->name }}</h1>
        <p><strong>Rekap Kehadiran TPQ</strong> — Kelas {{ $class->name }} — {{ $month->translatedFormat('F Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th><th style="text-align:left">Nama Santri</th><th>NIS</th>
                <th>Hadir</th><th>Sakit</th><th>Izin</th><th>Alfa</th><th>%</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recap as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="name">{{ $row['student']->name }}</td>
                    <td>{{ $row['student']->nis }}</td>
                    <td>{{ $row['present'] }}</td>
                    <td>{{ $row['sick'] }}</td>
                    <td>{{ $row['permission'] }}</td>
                    <td>{{ $row['absent'] }}</td>
                    <td>{{ $row['percent'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="8">Belum ada santri di kelas ini.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
