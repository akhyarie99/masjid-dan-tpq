<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1e293b; }
        .cover { text-align: center; padding-top: 120px; page-break-after: always; }
        .cover h1 { font-size: 22pt; color: #15803d; margin-bottom: 4px; }
        .cover h2 { font-size: 14pt; color: #475569; font-weight: normal; }
        .cover .period { margin-top: 40px; font-size: 13pt; }
        .bab { page-break-before: always; }
        .bab-title { font-size: 15pt; color: #16a34a; font-weight: bold; border-bottom: 2px solid #16a34a; padding-bottom: 8px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #f0fdf4; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <!-- COVER -->
    <div class="cover">
        <h1>{{ $masjid->name }}</h1>
        <h2>{{ $masjid->address }}</h2>
        <div class="period">
            <p><strong>LAPORAN PERTANGGUNGJAWABAN (LPJ)</strong></p>
            <p>DEWAN KEMAKMURAN MASJID</p>
            <p>TAHUN {{ $year }}</p>
        </div>
    </div>

    <!-- BAB 1 -->
    <div class="bab">
        <p class="bab-title">Bab 1 — Kata Pengantar</p>
        <p>Assalamu'alaikum warahmatullahi wabarakatuh.</p>
        <p>
            Puji syukur kehadirat Allah SWT atas rahmat dan karunia-Nya, sehingga pengurus Dewan Kemakmuran
            Masjid {{ $masjid->name }} dapat menyelesaikan tugas pengelolaan masjid selama tahun {{ $year }}.
            Laporan ini disusun sebagai bentuk pertanggungjawaban dan transparansi kepada seluruh jamaah.
        </p>
    </div>

    <!-- BAB 2 -->
    <div class="bab">
        <p class="bab-title">Bab 2 — Susunan Pengurus</p>
        <table>
            <thead><tr><th>Nama</th><th>Jabatan/Peran</th></tr></thead>
            <tbody>
                @forelse ($pengurus as $person)
                    <tr><td>{{ $person->name }}</td><td>{{ $person->roles->pluck('name')->implode(', ') }}</td></tr>
                @empty
                    <tr><td colspan="2">Data pengurus belum tersedia.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- BAB 3 -->
    <div class="bab">
        <p class="bab-title">Bab 3 — Laporan Keuangan</p>
        <table>
            <tr><td>Total Pemasukan</td><td class="text-right">Rp {{ number_format($income, 0, ',', '.') }}</td></tr>
            <tr><td>Total Pengeluaran</td><td class="text-right">Rp {{ number_format($expense, 0, ',', '.') }}</td></tr>
            <tr style="font-weight:bold; background:#f0fdf4"><td>Saldo Akhir</td><td class="text-right">Rp {{ number_format($balance, 0, ',', '.') }}</td></tr>
        </table>

        <p style="margin-top:12px;"><strong>Rincian per Kategori:</strong></p>
        <table>
            <thead><tr><th>Kategori</th><th>Tipe</th><th class="text-right">Total</th></tr></thead>
            <tbody>
                @forelse ($categoryBreakdown as $row)
                    <tr>
                        <td>{{ $row->category?->name ?? '-' }}</td>
                        <td>{{ $row->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}</td>
                        <td class="text-right">{{ number_format($row->total, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Tidak ada transaksi pada tahun ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- BAB 4 -->
    <div class="bab">
        <p class="bab-title">Bab 4 — Program dan Kegiatan yang Terlaksana</p>
        <table>
            <thead><tr><th>Nama Kegiatan</th><th>Kategori</th><th>Tanggal</th><th>Lokasi</th></tr></thead>
            <tbody>
                @forelse ($activities as $activity)
                    <tr>
                        <td>{{ $activity->name }}</td>
                        <td>{{ $activity->category }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($activity->start_at)->format('d-m-Y') }}</td>
                        <td>{{ $activity->location }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Belum ada kegiatan tercatat pada tahun ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- BAB 5 -->
    <div class="bab">
        <p class="bab-title">Bab 5 — Laporan Aset</p>
        <p>Total aset tercatat: {{ $assets->count() }} unit.</p>
        <table>
            <thead><tr><th>Kondisi</th><th class="text-right">Jumlah</th></tr></thead>
            <tbody>
                @foreach ($assets->groupBy('condition') as $condition => $group)
                    <tr><td>{{ ucfirst(str_replace('_', ' ', $condition)) }}</td><td class="text-right">{{ $group->count() }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- BAB 6 -->
    <div class="bab">
        <p class="bab-title">Bab 6 — Laporan TPQ</p>
        <p>Jumlah santri aktif tahun {{ $year }}: <strong>{{ $tpqStudentCount }}</strong> santri.</p>
    </div>

    <!-- BAB 7 -->
    <div class="bab">
        <p class="bab-title">Bab 7 — Program Sosial</p>
        <table>
            <thead><tr><th>Nama Program</th><th class="text-right">Jumlah Penerima</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($socialPrograms as $program)
                    <tr>
                        <td>{{ $program->name }}</td>
                        <td class="text-right">{{ $program->recipients_count }}</td>
                        <td>{{ $program->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Belum ada program sosial pada tahun ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- BAB 8 -->
    <div class="bab">
        <p class="bab-title">Bab 8 — Penutup dan Rencana ke Depan</p>
        <p>
            Demikian laporan pertanggungjawaban ini disusun sebagai wujud transparansi dan akuntabilitas
            pengelolaan {{ $masjid->name }} kepada seluruh jamaah. Semoga Allah SWT senantiasa memberkahi
            langkah kita bersama dalam memakmurkan rumah-Nya. Kritik dan saran membangun sangat kami harapkan
            demi perbaikan pengelolaan masjid di tahun mendatang.
        </p>
        <p>Wassalamu'alaikum warahmatullahi wabarakatuh.</p>
    </div>
</body>
</html>
