<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;

class PimpinanController extends Controller
{
    // dashboard pemantauan peminjaman buat Pimpinan (read-only) -- pemantauan aset & proyektor
    // udah ada menu sendiri (Pemeliharaan Proyektor, dst), jadi gak diduplikasi di sini
    public function dashboard()
    {
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

        return view('pimpinan.dashboard', compact(
            'totalPeminjaman',
            'peminjamanAktif',
            'selesaiBulanIni',
            'jumlahKuliah',
            'jumlahOrganisasi',
            'labelBulanTren',
            'dataBulanTren',
            'peminjamanTerakhir'
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
