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

        // status_efektif dihitung ulang dari peminjaman yang aktif SEKARANG (bukan kolom `status`
        // statis, yang cuma keisi manual pas aset dibuat/diedit admin dan gak pernah keupdate
        // otomatis pas ada mahasiswa minjam/balikin alat)
        $semuaAsetUntukStatus = AsetUmum::with('peminjamanDetailAktifSekarang')->get();
        $statusAset = [
            'tersedia' => $semuaAsetUntukStatus->where('status_efektif', 'tersedia')->count(),
            'dipinjam' => $semuaAsetUntukStatus->where('status_efektif', 'dipinjam')->count(),
            'rusak' => $semuaAsetUntukStatus->where('status_efektif', 'rusak')->count(),
            'pemeliharaan' => $semuaAsetUntukStatus->where('status_efektif', 'pemeliharaan')->count(),
        ];

        $peminjamanMenunggu = Peminjaman::where('kategori', 'organisasi')->where('status', 'menunggu')->count();

        // 5 pengajuan organisasi terbaru yang masih perlu ditindaklanjuti admin
        $daftarMenunggu = Peminjaman::with(['mahasiswa', 'details.asetUmum'])
            ->where('kategori', 'organisasi')
            ->where('status', 'menunggu')
            ->latest()
            ->take(5)
            ->get();

        // booking terbaru (organisasi + eksternal) biar admin langsung keliatan ormawa/pihak
        // eksternal mana aja yang baru booking, tanpa buka menu lain. Kuliah gak diikutin karena
        // auto-ACC & volumenya tinggi, kurang perlu dipantau di ringkasan ini.
        $bookingTerakhir = Peminjaman::where(function ($q) {
                $q->where('kategori', 'organisasi')
                  ->orWhere('jenis_peminjam', 'eksternal');
            })
            ->with(['mahasiswa', 'details.asetUmum', 'asetKelas'])
            ->latest()
            ->take(8)
            ->get();

        // booking terbaru kategori kuliah, dipisah dari organisasi/eksternal karena
        // volumenya beda dan auto-ACC (bukan yang perlu ditindaklanjuti)
        $bookingKuliahTerakhir = Peminjaman::where('kategori', 'kuliah')
            ->with(['mahasiswa', 'details.asetUmum', 'asetKelas'])
            ->latest()
            ->take(8)
            ->get();

        // grafik: jumlah semua peminjaman per bulan, 6 bulan terakhir
        $labelBulan = [];
        $dataPerBulan = [];
        for ($i = 5; $i >= 0; $i--) {
            $bulan = now()->subMonths($i);
            $labelBulan[] = $bulan->translatedFormat('M Y');
            $dataPerBulan[] = Peminjaman::whereYear('created_at', $bulan->year)
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
            'dataPerBulan'
        ));
    }
}
