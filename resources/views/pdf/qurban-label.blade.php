<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; margin: 0; padding: 8px; }
        .label { border: 2px dashed #16a34a; border-radius: 8px; padding: 10px; text-align: center; }
        .masjid { font-size: 9px; color: #64748b; }
        .title { font-size: 12px; font-weight: bold; color: #16a34a; margin: 4px 0; }
        .name { font-size: 14px; font-weight: bold; }
        .address { font-size: 9px; color: #475569; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="label">
        <p class="masjid">{{ $distribution->masjid->name }}</p>
        <p class="title">DAGING QURBAN {{ $distribution->year }} H</p>
        <p class="name">{{ $distribution->recipient_name }}</p>
        <p class="address">{{ $distribution->address }}</p>
        @if ($distribution->weight_kg)
            <p class="address">{{ $distribution->weight_kg }} kg — {{ $distribution->package_count }} paket</p>
        @endif
    </div>
</body>
</html>
