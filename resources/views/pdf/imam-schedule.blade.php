<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 16px; border-bottom: 2px solid #16a34a; padding-bottom: 12px; }
        .header h1 { margin: 0; font-size: 15px; color: #15803d; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; text-align: center; }
        th { background: #f0fdf4; }
        td.date { text-align: left; white-space: nowrap; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $masjid->name }}</h1>
        <p><strong>Jadwal Imam</strong> — {{ $month->translatedFormat('F Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                @foreach ($prayers as $prayer)
                    <th>{{ ['fajr' => 'Subuh', 'dhuhr' => 'Dzuhur', 'asr' => 'Ashar', 'maghrib' => 'Maghrib', 'isha' => 'Isya', 'jumuah' => 'Jumat'][$prayer] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @for ($day = 1; $day <= $month->daysInMonth; $day++)
                @php $date = $month->copy()->day($day); @endphp
                <tr>
                    <td class="date">{{ $date->translatedFormat('D, d M') }}</td>
                    @foreach ($prayers as $prayer)
                        @php
                            $schedule = $schedules->first(fn ($s) => $s->date->isSameDay($date) && $s->prayer === $prayer);
                            $imamName = $schedule?->is_substituted ? $schedule?->substituteImam?->name : $schedule?->imam?->name;
                        @endphp
                        <td>{{ $imamName ?? '-' }}</td>
                    @endforeach
                </tr>
            @endfor
        </tbody>
    </table>
</body>
</html>
