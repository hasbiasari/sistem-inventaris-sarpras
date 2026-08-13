<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $judulLaporan }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #222; }
        .kop { text-align: center; margin-bottom: 14px; border-bottom: 2px solid #0F6B4C; padding-bottom: 10px; }
        .kop h1 { font-size: 16px; margin: 0 0 4px; color: #0F6B4C; }
        .kop .sub { font-size: 10px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background: #0F6B4C; color: #fff; font-size: 10px; }
        td { font-size: 10px; }
        tr:nth-child(even) td { background: #f5f7f6; }
        .ringkasan { margin-top: 16px; }
        .ringkasan-judul { font-weight: bold; margin-bottom: 4px; }
        .ringkasan table { width: auto; }
        .ringkasan th, .ringkasan td { padding: 3px 10px; text-align: center; }
        .footer-cetak { margin-top: 24px; font-size: 8px; color: #888; text-align: right; }
    </style>
</head>
<body>
    <div class="kop">
        <h1>{{ $judulLaporan }}</h1>
        <div class="sub">
            Dicetak: {{ now()->format('d/m/Y H:i') }} WIB &nbsp;|&nbsp; Filter: {{ $labelFilter }}
        </div>
    </div>

    @yield('konten')

    @hasSection('ringkasan')
        <div class="ringkasan">
            <div class="ringkasan-judul">Ringkasan</div>
            @yield('ringkasan')
        </div>
    @endif

    <div class="footer-cetak">Sistem Inventaris Sarpras &mdash; STT Cipasung</div>
</body>
</html>
