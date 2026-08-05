<?php

namespace App\Http\Controllers;

use App\Models\AsetKelas;
use App\Models\AsetUmum;
use App\Models\Mahasiswa;
use App\Models\Peminjaman;

class PimpinanController extends Controller
{
    // Halaman untuk Pimpinan - Dashboard Pemantauan Aset & Peminjaman (read-only)
    public function dashboard()
    {
        $totalKelas         = AsetKelas::count();
        $totalMahasiswa     = Mahasiswa::count();
        $totalAlat          = AsetUmum::count();

        // status_efektif dihitung ulang dari peminjaman yang aktif SEKARANG (bukan kolom `status`
        // statis, yang cuma keisi manual pas aset dibuat/diedit admin dan gak pernah keupdate
        // otomatis pas ada mahasiswa minjam/balikin alat)
        $daftarAlat = AsetUmum::with('peminjamanDetailAktifSekarang')->latest()->get();
        $alatTersedia       = $daftarAlat->where('status_efektif', 'tersedia')->count();
        $alatDipinjam       = $daftarAlat->where('status_efektif', 'dipinjam')->count();
        $alatRusak          = $daftarAlat->where('status_efektif', 'rusak')->count();
        $alatPemeliharaan   = $daftarAlat->where('status_efektif', 'pemeliharaan')->count();

        // ringkasan peminjaman (Increment 2): total, yang lagi aktif, sama yang beres bulan ini
        $totalPeminjaman = Peminjaman::count();
        $peminjamanAktif = Peminjaman::where('status', 'disetujui')->count();
        $selesaiBulanIni = Peminjaman::where('status', 'selesai')
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
            $dataBulanTren[] = Peminjaman::whereYear('created_at', $bulan->year)
                ->whereMonth('created_at', $bulan->month)
                ->count();
        }

        $peminjamanTerakhir = Peminjaman::with(['mahasiswa', 'details.asetUmum', 'asetKelas'])
            ->latest()
            ->take(5)
            ->get();

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
            'peminjamanTerakhir'
        ));
    }

    // daftar lengkap semua peminjaman (kuliah, organisasi, eksternal) buat Pimpinan, read-only,
    // dibuka dari tombol "Lihat Semua" di dashboard
    // hitung breakdown status (menunggu/disetujui/ditolak/dibatalkan/selesai) dari 1 koleksi peminjaman,
    // dipakai buat grafik yang berubah-ubah pas pimpinan klik tombol filter kategori
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
