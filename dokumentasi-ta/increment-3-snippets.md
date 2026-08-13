# Snippet Kode Penting — Increment 3

## Daftar Proyektor + Ringkasan Status + Tren Pemakaian (SMA)
**File:** app/Http/Controllers/PemeliharaanProyektorController.php, method index(), ambilDaftarProyektor() & hitungRingkasanStatus()
```php
// daftar proyektor + prediksi pemeliharaan (SMA)
public function index()
{
    $daftarProyektor = $this->ambilDaftarProyektor();

    $ringkasanStatus = $this->hitungRingkasanStatus($daftarProyektor);

    // tren pemakaian per minggu (gabungan semua unit) + overlay SMA rolling 4 minggu
    $trenProyektor = AsetUmum::trenMingguanGabungan(8);

    return view('pemeliharaan-proyektor.index', compact('daftarProyektor', 'ringkasanStatus', 'trenProyektor'));
}

// daftar proyektor, yang paling kritis (persentase menuju threshold tertinggi) di atas
private function ambilDaftarProyektor()
{
    return AsetUmum::where('nama_alat', 'Proyektor')
        ->get()
        ->sortByDesc('persentase_menuju_servis')
        ->values();
}

private function hitungRingkasanStatus($daftarProyektor): array
{
    return [
        'normal' => $daftarProyektor->where('status_pemeliharaan', 'normal')->count(),
        'perlu_perhatian' => $daftarProyektor->where('status_pemeliharaan', 'perlu_perhatian')->count(),
        'perlu_pemeliharaan' => $daftarProyektor->where('status_pemeliharaan', 'perlu_pemeliharaan')->count(),
        'dalam_pemeliharaan' => $daftarProyektor->where('status_pemeliharaan', 'dalam_pemeliharaan')->count(),
    ];
}
```

## Tandai Proyektor Masuk Pemeliharaan
**File:** app/Http/Controllers/PemeliharaanProyektorController.php, method setPemeliharaan()
```php
// proyektor ditarik buat diservis
public function setPemeliharaan(AsetUmum $asetUmum)
{
    if ($asetUmum->nama_alat !== 'Proyektor') {
        abort(403);
    }

    $asetUmum->update(['status' => 'pemeliharaan']);

    return redirect()->route('admin.pemeliharaan-proyektor')->with('success', "{$asetUmum->nama_lengkap} ditandai sedang pemeliharaan.");
}
```

## Selesai Pemeliharaan (Reset Jam Pakai & Penanda Notifikasi)
**File:** app/Http/Controllers/PemeliharaanProyektorController.php, method selesaiPemeliharaan()
```php
// proyektor selesai diservis: jam pakai direset, status balik tersedia, boleh dinotif lagi kalau nanti nyampe threshold lagi
public function selesaiPemeliharaan(AsetUmum $asetUmum)
{
    if ($asetUmum->nama_alat !== 'Proyektor') {
        abort(403);
    }

    $asetUmum->update([
        'status' => 'tersedia',
        'total_jam_pakai' => 0,
        'notifikasi_pemeliharaan_at' => null,
        'notifikasi_perhatian_at' => null,
    ]);

    return redirect()->route('admin.pemeliharaan-proyektor')->with('success', "{$asetUmum->nama_lengkap} selesai pemeliharaan, jam pakai direset.");
}
```

## Koreksi Manual Jam Pakai (Reset Penanda Notifikasi Kondisional)
**File:** app/Http/Controllers/PemeliharaanProyektorController.php, method updateJamPakai()
```php
// koreksi manual jam pakai & batas jam maksimal -- dipakai buat input jam pakai awal
// (proyektor yang udah lama dipakai sebelum sistem ini ada) atau ganti spesifikasi lampu per merek/tipe
public function updateJamPakai(Request $request, AsetUmum $asetUmum)
{
    if ($asetUmum->nama_alat !== 'Proyektor') {
        abort(403);
    }

    $validated = $request->validate([
        'total_jam_pakai' => 'required|numeric|min:0',
        'batas_jam_maksimal' => 'required|integer|min:1',
    ], [
        // ...
    ]);

    $asetUmum->update($validated);
    $asetUmum->refresh();

    // kalau status barunya udah di bawah tingkat yang pernah dinotif, reset penandanya --
    // biar kalau nanti naik lagi bisa dinotif ulang, gak ke-skip gara-gara penanda lama nyangkut
    // dari sebelum jam pakainya dikoreksi
    $resetPenanda = [];
    if ($asetUmum->status_pemeliharaan !== 'perlu_pemeliharaan') {
        $resetPenanda['notifikasi_pemeliharaan_at'] = null;
    }
    if ($asetUmum->status_pemeliharaan === 'normal') {
        $resetPenanda['notifikasi_perhatian_at'] = null;
    }
    if ($resetPenanda) {
        $asetUmum->update($resetPenanda);
    }

    return redirect()->route('admin.pemeliharaan-proyektor')->with('success', "Jam pakai {$asetUmum->nama_lengkap} berhasil diupdate.");
}
```

## Export Laporan Servis Proyektor (PDF & Excel)
**File:** app/Http/Controllers/PemeliharaanProyektorController.php, method exportPdf() & exportExcel()
```php
// export laporan servis proyektor ke PDF -- halaman ini belum ada filter tab, jadi selalu semua data
public function exportPdf()
{
    $daftarProyektor = $this->ambilDaftarProyektor();
    $ringkasanStatus = $this->hitungRingkasanStatus($daftarProyektor);

    $judulLaporan = 'Laporan Servis Proyektor';
    $labelFilter = 'Semua Data';

    $pdf = Pdf::loadView('pemeliharaan-proyektor.laporan-pdf', compact('daftarProyektor', 'ringkasanStatus', 'judulLaporan', 'labelFilter'))
        ->setPaper('a4', 'landscape');

    return $pdf->stream('laporan-servis-proyektor-' . now()->format('Y-m-d') . '.pdf');
}

// export laporan servis proyektor ke Excel -- sama kayak PDF, selalu semua data
public function exportExcel()
{
    $daftarProyektor = $this->ambilDaftarProyektor();
    $ringkasanStatus = $this->hitungRingkasanStatus($daftarProyektor);

    return Excel::download(
        new ProyektorExport($daftarProyektor, 'Semua Data', $ringkasanStatus),
        'laporan-servis-proyektor-' . now()->format('Y-m-d') . '.xlsx'
    );
}
```

## Mapping Data Baris Excel (Label Estimasi & Status)
**File:** app/Exports/ProyektorExport.php, method array()
```php
foreach ($this->daftarProyektor as $p) {
    $estimasi = $p->estimasiMingguMenujuServis();
    $labelEstimasi = match (true) {
        $p->status === 'pemeliharaan' => 'Sedang diservis',
        $estimasi === null => 'Belum cukup data',
        $estimasi <= 0 => 'Sekarang',
        default => "~{$estimasi} minggu lagi",
    };
    $labelStatus = match ($p->status_pemeliharaan) {
        'dalam_pemeliharaan' => 'Dalam Pemeliharaan',
        'perlu_pemeliharaan' => 'Perlu Pemeliharaan',
        'perlu_perhatian' => 'Perlu Perhatian',
        default => 'Normal',
    };

    $baris[] = [
        '#' . $p->nomor_unit,
        $p->merek ?? '-',
        $p->total_jam_pakai,
        $p->batas_jam_maksimal,
        $p->persentase_menuju_servis,
        $labelEstimasi,
        $labelStatus,
    ];
}
```

## Algoritma Prediksi Pemeliharaan Proyektor (SMA) — Isi Lengkap Service
**File:** app/Services/PrediksiProyektorService.php
```php
<?php

namespace App\Services;

use App\Models\AsetUmum;

// Rumus fitur Prediksi Pemeliharaan Proyektor (algoritma SMA), semua numpuk di sini
class PrediksiProyektorService
{
    // Persentase menuju batas servis yang bikin status jadi "perlu pemeliharaan" (merah)
    private const PERSEN_PERLU_PEMELIHARAAN = 80;

    // Persentase menuju batas servis yang bikin status jadi "perlu perhatian" (kuning)
    private const PERSEN_PERLU_PERHATIAN = 70;

    // Batas servis = batas jam maksimal alat itu sendiri.
    // 100% artinya total_jam_pakai udah sama persis dengan batas_jam_maksimal (mis. 6000/6000 jam) --
    // bisa lebih dari 100% kalau total_jam_pakai udah kelewat dari batas_jam_maksimal
    public function thresholdServis(AsetUmum $aset): ?float
    {
        return $aset->batas_jam_maksimal ? (float) $aset->batas_jam_maksimal : null;
    }

    // Cek udah nyampe titik "perlu pemeliharaan" (>= 80% batas servis) atau belum
    public function perluPemeliharaan(AsetUmum $aset): bool
    {
        return ($this->persentaseMenujuServis($aset) ?? 0) >= self::PERSEN_PERLU_PEMELIHARAAN;
    }

    // Persentase menuju batas servis
    // Rumus: persentase = (total_jam_pakai / batas_servis) x 100
    public function persentaseMenujuServis(AsetUmum $aset): ?float
    {
        $threshold = $this->thresholdServis($aset);

        if (! $threshold) {
            return null;
        }

        return round(($aset->total_jam_pakai / $threshold) * 100, 1);
    }

    // Status 4 tingkat, berdasarkan persentase menuju batas servis:
    // >= 80% -> perlu_pemeliharaan (merah), 70-79.99% -> perlu_perhatian (kuning), < 70% -> normal (hijau)
    public function statusPemeliharaan(AsetUmum $aset): string
    {
        if ($aset->status === 'pemeliharaan') {
            return 'dalam_pemeliharaan';
        }

        $persentase = $this->persentaseMenujuServis($aset) ?? 0;

        if ($persentase >= self::PERSEN_PERLU_PEMELIHARAAN) {
            return 'perlu_pemeliharaan';
        }

        if ($persentase >= self::PERSEN_PERLU_PERHATIAN) {
            return 'perlu_perhatian';
        }

        return 'normal';
    }

    // Histori jam pakai per minggu, N minggu terakhir
    public function riwayatJamMingguan(AsetUmum $aset, int $jumlahMinggu = 4)
    {
        $mingguan = collect();

        for ($mingguKe = $jumlahMinggu - 1; $mingguKe >= 0; $mingguKe--) {
            $awalMinggu = now()->subWeeks($mingguKe)->startOfWeek();
            $akhirMinggu = $awalMinggu->copy()->endOfWeek();

            $jam = $aset->peminjamanDetails()
                ->whereHas('peminjaman', function ($q) use ($awalMinggu, $akhirMinggu) {
                    $q->where('status', 'selesai')
                        ->whereBetween('tanggal_pakai', [$awalMinggu->toDateString(), $akhirMinggu->toDateString()]);
                })
                ->with('peminjaman')
                ->get()
                ->sum(fn ($detail) => $detail->peminjaman->durasi_jam * $detail->jumlah);

            $mingguan->push([
                'label' => $awalMinggu->translatedFormat('d M') . '-' . $akhirMinggu->translatedFormat('d M'),
                'awal' => $awalMinggu->toDateString(),
                'jam' => round($jam, 2),
            ]);
        }

        return $mingguan;
    }

    // Inti algoritma SMA (Simple Moving Average)
    // Rumus: SMA = total jam pakai N minggu terakhir / N
    public function smaJamMingguan(AsetUmum $aset, int $jumlahMinggu = 4): float
    {
        return round($this->riwayatJamMingguan($aset, $jumlahMinggu)->avg('jam'), 2);
    }

    // Estimasi minggu menuju titik "perlu pemeliharaan" (80% batas servis)
    // Rumus: estimasi_minggu = ((batas_servis x 80%) - total_jam_pakai) / SMA
    // null = gak bisa diprediksi, 0 = udah harus servis sekarang
    public function estimasiMingguMenujuServis(AsetUmum $aset, int $jumlahMingguSma = 4): ?float
    {
        $threshold = $this->thresholdServis($aset);

        if ($threshold === null) {
            return null;
        }

        if ($this->perluPemeliharaan($aset)) {
            return 0;
        }

        $sma = $this->smaJamMingguan($aset, $jumlahMingguSma);

        if ($sma <= 0) {
            return null;
        }

        $titikPerluPemeliharaan = $threshold * (self::PERSEN_PERLU_PEMELIHARAAN / 100);
        $sisaJam = $titikPerluPemeliharaan - $aset->total_jam_pakai;

        return round($sisaJam / $sma, 1);
    }

    // Jam pakai gabungan semua unit proyektor, per minggu
    public function riwayatJamMingguanGabungan(int $jumlahMinggu = 8)
    {
        $daftarProyektor = AsetUmum::where('nama_alat', 'Proyektor')->get();

        // Array biasa, biar akumulasi nempel ke elemen aslinya
        $gabungan = [];

        foreach ($daftarProyektor as $proyektor) {
            foreach ($this->riwayatJamMingguan($proyektor, $jumlahMinggu)->values() as $i => $minggu) {
                $gabungan[$i] ??= ['label' => $minggu['label'], 'awal' => $minggu['awal'], 'jam' => 0];
                $gabungan[$i]['jam'] += $minggu['jam'];
            }
        }

        return collect($gabungan)->map(fn ($minggu) => [...$minggu, 'jam' => round($minggu['jam'], 2)]);
    }

    // Tren mingguan gabungan + overlay garis SMA buat grafik
    public function trenMingguanGabungan(int $jumlahMinggu = 8): array
    {
        $riwayat = $this->riwayatJamMingguanGabungan($jumlahMinggu)->values();

        $sma = $riwayat->map(function ($minggu, $i) use ($riwayat) {
            $mulai = max(0, $i - 3);

            return round($riwayat->slice($mulai, $i - $mulai + 1)->avg('jam'), 2);
        });

        return [
            'label' => $riwayat->pluck('label')->all(),
            'jam' => $riwayat->pluck('jam')->all(),
            'sma' => $sma->all(),
        ];
    }
}
```

## Accessor AsetUmum Terkait Status & Prediksi Pemeliharaan
**File:** app/Models/AsetUmum.php
```php
// total unit yang lagi kepake sekarang
private function jumlahDipinjamSekarang(): int
{
    return $this->relationLoaded('peminjamanDetailAktifSekarang')
        ? $this->peminjamanDetailAktifSekarang->sum('jumlah')
        : $this->peminjamanDetailAktifSekarang()->sum('jumlah');
}

// status real, ikut peminjaman aktif kalau kolomnya masih "tersedia"
public function getStatusEfektifAttribute()
{
    if (in_array($this->status, ['rusak', 'pemeliharaan', 'dipinjam'])) {
        return $this->status;
    }

    return $this->jumlahDipinjamSekarang() > 0 ? 'dipinjam' : 'tersedia';
}

// Batas servis = batas jam maksimal alat itu sendiri (titik 100% buat persentase_menuju_servis)
public function getThresholdServisAttribute()
{
    return app(PrediksiProyektorService::class)->thresholdServis($this);
}

// udah nyampe/lewat threshold servis apa belum
public function getPerluPemeliharaanAttribute(): bool
{
    return app(PrediksiProyektorService::class)->perluPemeliharaan($this);
}

// udah berapa persen jalan menuju threshold servis (bisa lebih dari 100 kalau udah kelewat)
public function getPersentaseMenujuServisAttribute()
{
    return app(PrediksiProyektorService::class)->persentaseMenujuServis($this);
}

// status pemeliharaan 3 tingkat: normal / perlu_perhatian / perlu_pemeliharaan (+ dalam_pemeliharaan kalau lagi diservis)
public function getStatusPemeliharaanAttribute(): string
{
    return app(PrediksiProyektorService::class)->statusPemeliharaan($this);
}

// nama class CSS buat highlight baris tabel sesuai status_pemeliharaan.
// Cuma 'row-perlu-pemeliharaan' & 'row-perlu-perhatian' yang beneran punya style
// (lihat mazer-green-theme.css) -- status lain ('normal', 'dalam_pemeliharaan') gak ngefek apa-apa.
public function getKelasBarisAttribute(): string
{
    return 'row-' . str_replace('_', '-', $this->status_pemeliharaan);
}

// warna Bootstrap (success/warning/danger) buat progress bar "menuju batas servis".
// 'dalam_pemeliharaan' sengaja ikut 'success' (netral ijo), bukan warna terpisah.
public function getWarnaStatusAttribute(): string
{
    return match ($this->status_pemeliharaan) {
        'perlu_pemeliharaan' => 'danger',
        'perlu_perhatian' => 'warning',
        default => 'success',
    };
}
```

## Anti-Spam Notifikasi Pemeliharaan Proyektor
**File:** app/Http/Controllers/NotifikasiController.php, method kirimNotifikasiPemeliharaan() & kirimNotifikasiTierProyektor()
```php
// cek proyektor yang masuk tingkat "perlu perhatian" (70-79.99%) ATAU udah "perlu pemeliharaan" (>=80%),
// kirim notif ke semua admin -- 2 tingkat ini ditandai kolom terpisah, biar notif "perlu perhatian"
// yang duluan muncul gak nutupin notif "perlu pemeliharaan" yang lebih mendesak pas nanti nyampe situ
private function kirimNotifikasiPemeliharaan()
{
    $this->kirimNotifikasiTierProyektor(
        'perlu_perhatian',
        'notifikasi_perhatian_at',
        fn ($alat) => "{$alat->nama_lengkap} sudah mendekati batas servis ({$alat->persentase_menuju_servis}%), mulai jadwalkan pemeliharaan."
    );

    $this->kirimNotifikasiTierProyektor(
        'perlu_pemeliharaan',
        'notifikasi_pemeliharaan_at',
        fn ($alat) => "{$alat->nama_lengkap} sudah mencapai {$alat->total_jam_pakai} jam pemakaian, perlu segera dijadwalkan pemeliharaan."
    );
}

// kirim notif ke semua admin buat proyektor yang baru masuk $statusTarget, sekali per proyektor
// per kolom penanda (gak berulang tiap polling sampai kolomnya direset pas selesai pemeliharaan)
private function kirimNotifikasiTierProyektor(string $statusTarget, string $kolomPenanda, \Closure $buatPesan)
{
    $daftarProyektor = AsetUmum::where('nama_alat', 'Proyektor')
        ->whereNull($kolomPenanda)
        ->get()
        ->filter(fn ($alat) => $alat->status_pemeliharaan === $statusTarget);

    if ($daftarProyektor->isEmpty()) {
        return;
    }

    $adminList = User::where('role', 'admin_tu')->get();

    foreach ($daftarProyektor as $alat) {
        foreach ($adminList as $admin) {
            Notifikasi::create([
                'user_id' => $admin->id,
                'pesan' => $buatPesan($alat),
                'link' => route('admin.pemeliharaan-proyektor'),
            ]);
        }

        $alat->update([$kolomPenanda => now()]);
    }
}
```
