<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Amiri', serif; text-align: center; color: #1e293b; }
        .frame { border: 6px double #16a34a; padding: 40px; margin: 20px; }
        .title { font-size: 28pt; color: #16a34a; font-weight: bold; margin-bottom: 4px; }
        .subtitle { font-size: 13pt; color: #64748b; margin-bottom: 30px; }
        .cert-number { font-size: 10pt; color: #94a3b8; }
        .presented { font-size: 12pt; margin-top: 20px; }
        .student-name { font-size: 22pt; font-weight: bold; color: #1e293b; margin: 10px 0; border-bottom: 2px solid #16a34a; display: inline-block; padding-bottom: 6px; }
        .achievement { font-size: 13pt; margin: 20px 0; }
        .footer { display: flex; justify-content: space-around; margin-top: 60px; font-size: 11pt; }
    </style>
</head>
<body>
    <div class="frame">
        <p class="cert-number">No. {{ $certificate->certificate_number }}</p>
        <p class="title">SERTIFIKAT</p>
        <p class="subtitle">{{ strtoupper(str_replace('_', ' ', $certificate->type)) }}</p>

        <p class="presented">Diberikan kepada:</p>
        <p class="student-name">{{ $student->name }}</p>
        <p>NIS: {{ $student->nis }}</p>

        <p class="achievement">
            Atas pencapaian: <strong>{{ $certificate->achievement ?? '-' }}</strong>
        </p>

        <p>{{ $masjid->name }}, {{ \Illuminate\Support\Carbon::parse($certificate->issued_date)->translatedFormat('d F Y') }}</p>

        <div class="footer">
            <div>
                <p>&nbsp;</p>
                <p>_____________________</p>
                <p>Kepala TPQ</p>
            </div>
        </div>
    </div>
</body>
</html>
