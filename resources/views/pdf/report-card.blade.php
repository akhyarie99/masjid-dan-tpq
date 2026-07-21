<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: 'Plus Jakarta Sans', Arial, sans-serif; font-size: 11pt; color: #1e293b; }
    .header { text-align: center; border-bottom: 2px solid #16a34a; padding-bottom: 12px; }
    .logo { width: 70px; height: 70px; }
    .school-name { font-size: 16pt; font-weight: bold; color: #16a34a; }
    .raport-title { font-size: 14pt; margin: 8px 0; }

    table.grades { width: 100%; border-collapse: collapse; }
    table.grades th, table.grades td { border: 1px solid #ccc; padding: 6px 10px; }
    table.grades th { background: #f0fdf4; }

    .ornament-border { border: 3px double #16a34a; padding: 20px; }

    .promotion-status { font-size: 14pt; font-weight: bold; text-align: center;
                        border: 2px solid; padding: 8px; margin: 12px 0; }
    .naik  { color: #16a34a; border-color: #16a34a; }
    .tinggal { color: #dc2626; border-color: #dc2626; }
    .lulus { color: #d97706; border-color: #d97706; }

    .signature-area { display: flex; justify-content: space-between; margin-top: 30px; }
    .signature-box { text-align: center; width: 180px; }
    .signature-line { border-bottom: 1px solid black; margin: 40px 0 4px; }
  </style>
</head>
<body>
<div class="ornament-border">
  <!-- HEADER -->
  <div class="header">
    @if($tpqLogo)
      <img src="{{ $tpqLogo }}" class="logo">
    @endif
    <div class="school-name">{{ $tpqName }}</div>
    <div>{{ $masjidName }} | {{ $masjidAddress }}</div>
    <div class="raport-title">RAPORT SEMESTER {{ $semester->number }} — TAHUN AJARAN {{ $academicYear }}</div>
  </div>

  <!-- IDENTITAS SANTRI -->
  <table style="width:100%; margin: 12px 0;">
    <tr>
      <td style="width:70%">
        <table>
          <tr><td width="140">Nama Santri</td><td>: <strong>{{ $student->name }}</strong></td></tr>
          <tr><td>NIS</td><td>: {{ $student->nis }}</td></tr>
          <tr><td>Kelas</td><td>: {{ $class->name }}</td></tr>
          <tr><td>Wali Kelas</td><td>: {{ $homeroomTeacher }}</td></tr>
          <tr><td>Tahun Ajaran</td><td>: {{ $academicYear }}</td></tr>
        </table>
      </td>
      <td style="text-align:right">
        @if($student->photo)
          <img src="{{ $student->photo }}" style="width:80px; height:100px; object-fit:cover; border:1px solid #ccc;">
        @endif
      </td>
    </tr>
  </table>

  <!-- NILAI PER MAPEL -->
  <table class="grades">
    <thead>
      <tr>
        <th>No</th>
        <th>Mata Pelajaran</th>
        <th>Nilai</th>
        <th>Predikat</th>
        <th>Keterangan</th>
      </tr>
    </thead>
    <tbody>
      @foreach($grades as $i => $grade)
      <tr>
        <td>{{ $i + 1 }}</td>
        <td>{{ $grade->subject->name }}</td>
        <td style="text-align:center; font-weight:bold">{{ $grade->score }}</td>
        <td style="text-align:center">{{ $grade->grade_letter }}</td>
        <td>{{ $grade->description }}</td>
      </tr>
      @endforeach
      <tr style="background:#f0fdf4; font-weight:bold">
        <td colspan="2">Rata-Rata</td>
        <td style="text-align:center">{{ number_format($reportCard->average_score, 1) }}</td>
        <td colspan="2"></td>
      </tr>
    </tbody>
  </table>

  <!-- KEHADIRAN -->
  <table class="grades" style="margin-top:12px">
    <thead>
      <tr><th colspan="4" style="text-align:left">Rekap Kehadiran</th></tr>
      <tr><th>Hadir</th><th>Sakit</th><th>Izin</th><th>Alfa</th></tr>
    </thead>
    <tbody>
      <tr>
        <td style="text-align:center">{{ $reportCard->present_count }} hari</td>
        <td style="text-align:center">{{ $reportCard->sick_count }} hari</td>
        <td style="text-align:center">{{ $reportCard->permission_count }} hari</td>
        <td style="text-align:center">{{ $reportCard->absent_count }} hari</td>
      </tr>
    </tbody>
  </table>

  <!-- HAFALAN -->
  <div style="margin-top:12px; padding:8px; background:#f0fdf4; border-radius:4px">
    <strong>Pencapaian Hafalan Semester Ini:</strong>
    <div>{{ $hafalanSummary }}</div>
  </div>

  <!-- CATATAN WALI KELAS -->
  <div style="margin-top:12px">
    <strong>Catatan Wali Kelas:</strong>
    <div style="border:1px solid #ccc; padding:8px; min-height:50px; margin-top:4px">
      {{ $reportCard->homeroom_notes ?? '-' }}
    </div>
  </div>

  <!-- CATATAN KEPALA TPQ -->
  <div style="margin-top:8px">
    <strong>Catatan Kepala TPQ:</strong>
    <div style="border:1px solid #ccc; padding:8px; min-height:40px; margin-top:4px">
      {{ $reportCard->head_notes ?? '-' }}
    </div>
  </div>

  <!-- STATUS KENAIKAN KELAS -->
  @if($reportCard->promotion_status)
  <div class="promotion-status {{ $reportCard->promotion_status }}">
    @if($reportCard->promotion_status === 'naik')
      ✓ DINYATAKAN NAIK KE KELAS BERIKUTNYA
    @elseif($reportCard->promotion_status === 'tinggal')
      ✗ DINYATAKAN TINGGAL KELAS
    @else
      ★ DINYATAKAN LULUS / KHATAM
    @endif
  </div>
  @endif

  <!-- TANDA TANGAN -->
  <div class="signature-area">
    <div class="signature-box">
      <div>Wali Murid</div>
      <div class="signature-line"></div>
      <div>( __________________ )</div>
    </div>
    <div class="signature-box">
      <div>{{ $masjidCity }}, {{ now()->translatedFormat('d F Y') }}</div>
      <div>Wali Kelas</div>
      @if($homeroomSignature)
        <img src="{{ $homeroomSignature }}" style="height:50px; margin: 4px auto;">
      @else
        <div style="height:50px"></div>
      @endif
      <div class="signature-line"></div>
      <div>{{ $homeroomTeacher }}</div>
    </div>
    <div class="signature-box">
      <div>Kepala TPQ</div>
      @if($headSignature)
        <img src="{{ $headSignature }}" style="height:50px; margin: 4px auto;">
      @else
        <div style="height:50px"></div>
      @endif
      <div class="signature-line"></div>
      <div>{{ $tpqHeadName }}</div>
    </div>
  </div>
</div>
</body>
</html>
