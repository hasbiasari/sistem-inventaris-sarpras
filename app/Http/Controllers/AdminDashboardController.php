<?php

namespace App\Http\Controllers;

use App\Models\AsetKelas;
use App\Models\AsetUmum;
use App\Models\Mahasiswa;
use App\Models\Peminjaman;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalMahasiswa = Mahasiswa::count();
        $totalAsetKelas = AsetKelas::count();
        $totalAsetUmum = AsetUmum::count();

        // status_efektif dihitung ulang dari peminjaman yang aktif sekarang
        $semuaAsetUntukStatus = AsetUmum::with('peminjamanDetailAktifSekarang')->get();
        $statusAset = [
            'tersedia' => $semuaAsetUntukStatus->where('status_efektif', 'tersedia')->count(),
            'dipinjam' => $semuaAsetUntukStatus->where('status_efektif', 'dipinjam')->count(),
            'rusak' => $semuaAsetUntukStatus->where('status_efektif', 'rusak')->count(),
            'pemeliharaan' => $semuaAsetUntukStatus->where('status_efektif', 'pemeliharaan')->count(),
        ];

        $peminjamanMenunggu = Peminjaman::where('kategori', 'organisasi')->where('status', 'menunggu')->count();

        // ringkasan pemeliharaan proyektor (detail lengkapnya ada di halaman Pemeliharaan Proyektor)
        $jumlahProyektorPerluPemeliharaan = AsetUmum::where('nama_alat', 'Proyektor')
            ->get()
            ->filter(fn ($alat) => $alat->perlu_pemeliharaan)
            ->count();

        // 5 pengajuan organisasi terbaru yang perlu ditindaklanjuti
        $daftarMenunggu = Peminjaman::with(['mahasiswa', 'details.asetUmum'])
            ->where('kategori', 'organisasi')
            ->where('status', 'menunggu')
            ->latest()
            ->take(5)
            ->get();

        // booking organisasi + eksternal terbaru (kuliah gak diikutin, auto-ACC)
        $bookingTerakhir = Peminjaman::where(function ($q) {
                $q->where('kategori', 'organisasi')
                  ->orWhere('jenis_peminjam', 'eksternal');
            })
            ->bukanSimulasi()
            ->with(['mahasiswa', 'details.asetUmum', 'asetKelas'])
            ->latest()
            ->take(8)
            ->get();

        // booking kuliah terbaru, dipisah karena auto-ACC
        $bookingKuliahTerakhir = Peminjaman::where('kategori', 'kuliah')
            ->with(['mahasiswa', 'details.asetUmum', 'asetKelas'])
            ->latest()
            ->take(8)
            ->get();

        // grafik jumlah peminjaman per bulan, 6 bulan terakhir
        $labelBulan = [];
        $dataPerBulan = [];
        for ($i = 5; $i >= 0; $i--) {
            $bulan = now()->subMonths($i);
            $labelBulan[] = $bulan->translatedFormat('M Y');
            $dataPerBulan[] = Peminjaman::bukanSimulasi()
                ->whereYear('created_at', $bulan->year)
                ->whereMonth('created_at', $bulan->month)
                ->count();
        }

        return view('admin.dashboard', compact(
            'totalMahasiswa',
            'totalAsetKelas',
            'totalAsetUmum',
            'statusAset',
            'peminjamanMenunggu',
            'daftarMenunggu',
            'bookingTerakhir',
            'bookingKuliahTerakhir',
            'labelBulan',
            'dataPerBulan',
            'jumlahProyektorPerluPemeliharaan'
        ));
    }
}
