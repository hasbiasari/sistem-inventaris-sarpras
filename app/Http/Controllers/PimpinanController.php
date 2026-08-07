<?php

namespace App\Http\Controllers;

use App\Models\AsetKelas;
use App\Models\AsetUmum;
use App\Models\Mahasiswa;
use App\Models\Peminjaman;

class PimpinanController extends Controller
{
    // dashboard pemantauan aset & peminjaman buat Pimpinan (read-only)
    public function dashboard()
    {
        $totalKelas         = AsetKelas::count();
        $totalMahasiswa     = Mahasiswa::count();
        $totalAlat          = AsetUmum::count();

        // status_efektif dihitung ulang dari peminjaman yang aktif sekarang
        $daftarAlat = AsetUmum::with('peminjamanDetailAktifSekarang')->latest()->get();
        $alatTersedia       = $daftarAlat->where('status_efektif', 'tersedia')->count();
        $alatDipinjam       = $daftarAlat->where('status_efektif', 'dipinjam')->count();
        $alatRusak          = $daftarAlat->where('status_efektif', 'rusak')->count();
        $alatPemeliharaan   = $daftarAlat->where('status_efektif', 'pemeliharaan')->count();

        // ringkasan peminjaman: total, aktif, selesai bulan ini
        $totalPeminjaman = Peminjaman::bukanSimulasi()->count();
        $peminjamanAktif = Peminjaman::where('status', 'disetujui')->count();
        $selesaiBulanIni = Peminjaman::where('status', 'selesai')
            ->bukanSimulasi()
            ->whereYear('waktu_kembali', now()->year)
            ->whereMonth('waktu_kembali', now()->month)
            ->count();

        $jumlahKuliah = Peminjaman::where('kategori', 'kuliah')->count();
        $jumlahOrganisasi = Peminjaman::where('kategori', 'organisasi')->count();

        // tren jumlah peminjaman per bulan, 6 bulan terakhir
        $labelBulanTren = [];
        $dataBulanTren = [];
        for ($i = 5; $i >= 0; $i--) {
            $bulan = now()->subMonths($i);
            $labelBulanTren[] = $bulan->translatedFormat('M Y');
            $dataBulanTren[] = Peminjaman::bukanSimulasi()
                ->whereYear('created_at', $bulan->year)
                ->whereMonth('created_at', $bulan->month)
                ->count();
        }

        $peminjamanTerakhir = Peminjaman::with(['mahasiswa', 'details.asetUmum', 'asetKelas'])
            ->bukanSimulasi()
            ->latest()
            ->take(5)
            ->get();

        // tren pemakaian proyektor per minggu (gabungan semua unit) + overlay SMA rolling 4 minggu,
        // biar keliatan efek "smoothing"-nya dibanding data mentah per minggu
        $trenProyektor = AsetUmum::trenMingguanGabungan(8);
        $labelMingguProyektor = $trenProyektor['label'];
        $dataJamProyektor = $trenProyektor['jam'];
        $dataSmaProyektor = $trenProyektor['sma'];

        return view('pimpinan.dashboard', compact(
            'totalKelas',
            'totalMahasiswa',
            'totalAlat',
            'alatTersedia',
            'alatDipinjam',
            'alatRusak',
            'alatPemeliharaan',
            'daftarAlat',
            'totalPeminjaman',
            'peminjamanAktif',
            'selesaiBulanIni',
            'jumlahKuliah',
            'jumlahOrganisasi',
            'labelBulanTren',
            'dataBulanTren',
            'peminjamanTerakhir',
            'labelMingguProyektor',
            'dataJamProyektor',
            'dataSmaProyektor'
        ));
    }

    // breakdown status peminjaman, dipakai buat grafik filter kategori
    private function hitungStatusPeminjaman($koleksi)
    {
        return [
            'menunggu' => $koleksi->where('status', 'menunggu')->count(),
            'disetujui' => $koleksi->where('status', 'disetujui')->count(),
            'ditolak' => $koleksi->where('status', 'ditolak')->count(),
            'dibatalkan' => $koleksi->where('status', 'dibatalkan')->count(),
            'selesai' => $koleksi->where('status', 'selesai')->count(),
        ];
    }

    public function peminjaman()
    {
        $daftarPeminjaman = Peminjaman::with(['mahasiswa', 'details.asetUmum', 'asetKelas', 'buktiPengembalian'])
            ->bukanSimulasi()
            ->latest()
            ->get();

        $jumlahKuliah = $daftarPeminjaman->where('kategori', 'kuliah')->count();
        $jumlahOrganisasi = $daftarPeminjaman->where('kategori', 'organisasi')->count();
        $jumlahEksternal = $daftarPeminjaman->where('jenis_peminjam', 'eksternal')->count();

        $statusPerKategori = [
            'semua' => $this->hitungStatusPeminjaman($daftarPeminjaman),
            'kuliah' => $this->hitungStatusPeminjaman($daftarPeminjaman->where('kategori', 'kuliah')),
            'organisasi' => $this->hitungStatusPeminjaman($daftarPeminjaman->where('kategori', 'organisasi')),
            'eksternal' => $this->hitungStatusPeminjaman($daftarPeminjaman->where('jenis_peminjam', 'eksternal')),
        ];

        return view('pimpinan.peminjaman', compact(
            'daftarPeminjaman',
            'jumlahKuliah',
            'jumlahOrganisasi',
            'jumlahEksternal',
            'statusPerKategori'
        ));
    }
}
