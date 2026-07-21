<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #16a34a; padding-bottom: 12px; }
        .header h1 { margin: 0; font-size: 16px; color: #15803d; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        td { padding: 6px 4px; }
        .label { width: 160px; color: #64748b; }
        .signature { margin-top: 60px; display: flex; justify-content: space-between; }
        .signature div { text-align: center; width: 200px; }
        .signature .line { border-bottom: 1px solid #000; margin: 50px 0 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $masjid->name }}</h1>
        <p><strong>TANDA TERIMA BANTUAN</strong></p>
        <p>{{ $program->name }}</p>
    </div>

    <table>
        <tr><td class="label">Nama Penerima</td><td>: {{ $recipient->name }}</td></tr>
        <tr><td class="label">Alamat</td><td>: {{ $recipient->address ?? '-' }}</td></tr>
        <tr><td class="label">Jenis Bantuan</td><td>: {{ $recipient->aid_type }}</td></tr>
        <tr><td class="label">Nominal/Jumlah</td><td>: {{ $recipient->amount ? 'Rp'.number_format($recipient->amount, 0, ',', '.') : '-' }}</td></tr>
        <tr><td class="label">Tanggal Distribusi</td><td>: {{ \Illuminate\Support\Carbon::parse($recipient->distributed_at)->translatedFormat('d F Y') }}</td></tr>
    </table>

    <p style="margin-top:20px;">Dengan ini menyatakan telah menerima bantuan tersebut di atas dengan baik.</p>

    <div class="signature">
        <div>
            <p>Yang Menyerahkan,</p>
            <div class="line"></div>
            <p>(Pengurus Masjid)</p>
        </div>
        <div>
            <p>Yang Menerima,</p>
            <div class="line"></div>
            <p>({{ $recipient->name }})</p>
        </div>
    </div>
</body>
</html>
