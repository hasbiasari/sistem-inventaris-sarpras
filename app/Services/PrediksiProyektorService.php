<?php

namespace App\Services;

use App\Models\AsetUmum;

// Rumus fitur Prediksi Pemeliharaan Proyektor (algoritma SMA), semua numpuk di sini
class PrediksiProyektorService
{
    // Batas servis, 80% dari jam maksimal
    // Rumus: threshold_servis = batas_jam_maksimal x 0.8
    public function thresholdServis(AsetUmum $aset): ?float
    {
        return $aset->batas_jam_maksimal ? round($aset->batas_jam_maksimal * 0.8, 2) : null;
    }

    // Cek udah lewat batas servis atau belum
    // Rumus: perlu_pemeliharaan = (total_jam_pakai >= threshold_servis)
    public function perluPemeliharaan(AsetUmum $aset): bool
    {
        $threshold = $this->thresholdServis($aset);

        return $threshold !== null && $aset->total_jam_pakai >= $threshold;
    }

    // Persentase menuju batas servis
    // Rumus: persentase = (total_jam_pakai / threshold_servis) x 100
    public function persentaseMenujuServis(AsetUmum $aset): ?float
    {
        $threshold = $this->thresholdServis($aset);

        if (! $threshold) {
            return null;
        }

        return round(($aset->total_jam_pakai / $threshold) * 100, 1);
    }

    // Status 4 tingkat: normal, perhatian, pemeliharaan, dalam servis
    public function statusPemeliharaan(AsetUmum $aset): string
    {
        if ($aset->status === 'pemeliharaan') {
            return 'dalam_pemeliharaan';
        }

        if ($this->perluPemeliharaan($aset)) {
            return 'perlu_pemeliharaan';
        }

        if (($this->persentaseMenujuServis($aset) ?? 0) >= 60) {
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

    // Estimasi minggu menuju batas servis
    // Rumus: estimasi_minggu = (threshold_servis - total_jam_pakai) / SMA
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

        $sisaJam = $threshold - $aset->total_jam_pakai;

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