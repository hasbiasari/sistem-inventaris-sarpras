<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\PemeliharaanProyektorController;
use App\Http\Controllers\AsetUmumController;
use App\Http\Controllers\AsetKelasController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\PimpinanController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardRedirectController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/notifikasi/data', [NotifikasiController::class, 'data'])->name('notifikasi.data');
    Route::post('/notifikasi/tandai-dibaca', [NotifikasiController::class, 'tandaiDibaca'])->name('notifikasi.tandai-dibaca');
    Route::post('/notifikasi/{notifikasi}/tandai-dibaca', [NotifikasiController::class, 'tandaiSatuDibaca'])->name('notifikasi.tandai-satu-dibaca');
    Route::get('/status-ruangan', [AsetKelasController::class, 'statusRuangan'])->name('status-ruangan');
    Route::get('/peminjaman/cek-ruangan-bentrok', [PeminjamanController::class, 'cekRuanganBentrok'])->name('peminjaman.cek-ruangan-bentrok');
    Route::get('/peminjaman/cek-stok-barang', [PeminjamanController::class, 'cekStokBarang'])->name('peminjaman.cek-stok-barang');
});

// khusus admin
Route::middleware(['auth', 'role:admin_tu'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/data-mahasiswa', [MahasiswaController::class, 'index'])->name('admin.mahasiswa');
    Route::get('/admin/data-mahasiswa/create', [MahasiswaController::class, 'create'])->name('admin.mahasiswa.create');
    Route::post('/admin/data-mahasiswa', [MahasiswaController::class, 'store'])->name('admin.mahasiswa.store');
    Route::post('/admin/data-mahasiswa/import', [MahasiswaController::class, 'import'])->name('admin.mahasiswa.import');
    Route::patch('/admin/data-mahasiswa/{mahasiswa}/reset-password', [MahasiswaController::class, 'resetPassword'])->name('admin.mahasiswa.reset');
    Route::get('/admin/data-mahasiswa/{mahasiswa}/edit', [MahasiswaController::class, 'edit'])->name('admin.mahasiswa.edit');
    Route::put('/admin/data-mahasiswa/{mahasiswa}', [MahasiswaController::class, 'update'])->name('admin.mahasiswa.update');
    Route::delete('/admin/data-mahasiswa/{mahasiswa}', [MahasiswaController::class, 'destroy'])->name('admin.mahasiswa.destroy');
    Route::get('/admin/jadwal-ruangan', [AsetKelasController::class, 'jadwalRuangan'])->name('admin.jadwal-ruangan');
    Route::get('/admin/aset-kelas', [AsetKelasController::class, 'index'])->name('admin.aset-kelas');
    Route::get('/admin/aset-kelas/create', [AsetKelasController::class, 'create'])->name('admin.aset-kelas.create');
    Route::post('/admin/aset-kelas', [AsetKelasController::class, 'store'])->name('admin.aset-kelas.store');
    Route::get('/admin/aset-kelas/{asetKela}/edit', [AsetKelasController::class, 'edit'])->name('admin.aset-kelas.edit');
    Route::put('/admin/aset-kelas/{asetKela}', [AsetKelasController::class, 'update'])->name('admin.aset-kelas.update');
    Route::delete('/admin/aset-kelas/{asetKela}', [AsetKelasController::class, 'destroy'])->name('admin.aset-kelas.destroy');
    Route::get('/admin/aset-umum', [AsetUmumController::class, 'index'])->name('admin.aset-umum');
    Route::get('/admin/aset-umum/create', [AsetUmumController::class, 'create'])->name('admin.aset-umum.create');
    Route::post('/admin/aset-umum', [AsetUmumController::class, 'store'])->name('admin.aset-umum.store');
    Route::get('/admin/aset-umum/{asetUmum}/edit', [AsetUmumController::class, 'edit'])->name('admin.aset-umum.edit');
    Route::put('/admin/aset-umum/{asetUmum}', [AsetUmumController::class, 'update'])->name('admin.aset-umum.update');
    Route::delete('/admin/aset-umum/{asetUmum}', [AsetUmumController::class, 'destroy'])->name('admin.aset-umum.destroy');
    Route::post('/admin/pemeliharaan-proyektor/{asetUmum}/set-pemeliharaan', [PemeliharaanProyektorController::class, 'setPemeliharaan'])->name('admin.pemeliharaan-proyektor.set-pemeliharaan');
    Route::post('/admin/pemeliharaan-proyektor/{asetUmum}/selesai-pemeliharaan', [PemeliharaanProyektorController::class, 'selesaiPemeliharaan'])->name('admin.pemeliharaan-proyektor.selesai-pemeliharaan');
    Route::patch('/admin/pemeliharaan-proyektor/{asetUmum}/jam-pakai', [PemeliharaanProyektorController::class, 'updateJamPakai'])->name('admin.pemeliharaan-proyektor.update-jam-pakai');
    Route::patch('/admin/peminjaman-kuliah/{peminjaman}/batalkan', [PeminjamanController::class, 'kuliahBatalkan'])->name('admin.peminjaman.kuliah.batalkan');
    Route::patch('/admin/peminjaman-organisasi/{peminjaman}/setujui', [PeminjamanController::class, 'organisasiSetujui'])->name('admin.peminjaman.organisasi.setujui');
    Route::patch('/admin/peminjaman-organisasi/{peminjaman}/tolak', [PeminjamanController::class, 'organisasiTolak'])->name('admin.peminjaman.organisasi.tolak');
    Route::patch('/admin/peminjaman-organisasi/{peminjaman}/batalkan', [PeminjamanController::class, 'organisasiBatalkan'])->name('admin.peminjaman.organisasi.batalkan');
    Route::get('/admin/booking-eksternal', [PeminjamanController::class, 'eksternalCreate'])->name('admin.booking-eksternal');
    Route::post('/admin/booking-eksternal', [PeminjamanController::class, 'eksternalStore'])->name('admin.booking-eksternal.store');
    Route::patch('/admin/peminjaman-eksternal/{peminjaman}/batalkan', [PeminjamanController::class, 'eksternalBatalkan'])->name('admin.peminjaman.eksternal.batalkan');
});

// khusus mahasiswa
Route::middleware(['auth', 'role:mahasiswa'])->group(function () {
    Route::get('/mahasiswa/dashboard', [MahasiswaController::class, 'dashboard'])->name('mahasiswa.dashboard');
    Route::get('/mahasiswa/dashboard/aset-umum-data', [MahasiswaController::class, 'dashboardAsetUmumData'])->name('mahasiswa.dashboard.aset-umum-data');
    Route::get('/aset-umum', [MahasiswaController::class, 'asetUmum'])->name('mahasiswa.aset-umum');
    Route::get('/aset-kelas', [AsetKelasController::class, 'mahasiswaIndex'])->name('mahasiswa.aset-kelas');
    Route::get('/aset-umum/data', [MahasiswaController::class, 'asetUmumData'])->name('mahasiswa.aset-umum.data');
    Route::get('/peminjaman/create', [PeminjamanController::class, 'create'])->name('peminjaman.create');
    Route::post('/peminjaman', [PeminjamanController::class, 'store'])->name('peminjaman.store');
    Route::get('/peminjaman/{peminjaman}', [PeminjamanController::class, 'show'])->name('peminjaman.show');
    Route::get('/peminjaman/{peminjaman}/edit', [PeminjamanController::class, 'edit'])->name('peminjaman.edit');
    Route::put('/peminjaman/{peminjaman}', [PeminjamanController::class, 'update'])->name('peminjaman.update');
    Route::post('/peminjaman/{peminjaman}/kembalikan', [PeminjamanController::class, 'kembalikan'])->name('peminjaman.kembalikan');
    Route::get('/peminjaman-saya/riwayat', [PeminjamanController::class, 'riwayat'])->name('peminjaman.riwayat');
});

// khusus pimpinan
Route::middleware(['auth', 'role:pimpinan'])->group(function () {
    Route::get('/pimpinan/dashboard', [PimpinanController::class, 'dashboard'])->name('pimpinan.dashboard');
    Route::get('/pimpinan/peminjaman', [PimpinanController::class, 'peminjaman'])->name('pimpinan.peminjaman');
});

// diakses admin TU & pimpinan berdua -- view + export doang, semua route mutasi (set/selesai
Route::middleware(['auth', 'role:admin_tu,pimpinan'])->group(function () {
    Route::get('/admin/pemeliharaan-proyektor', [PemeliharaanProyektorController::class, 'index'])->name('admin.pemeliharaan-proyektor');
    Route::get('/admin/pemeliharaan-proyektor/export-pdf', [PemeliharaanProyektorController::class, 'exportPdf'])->name('admin.pemeliharaan-proyektor.export-pdf');
    Route::get('/admin/pemeliharaan-proyektor/export-excel', [PemeliharaanProyektorController::class, 'exportExcel'])->name('admin.pemeliharaan-proyektor.export-excel');
    Route::get('/admin/laporan-peminjaman', [PeminjamanController::class, 'laporan'])->name('admin.peminjaman.laporan');
    Route::get('/admin/laporan-peminjaman/export-pdf', [PeminjamanController::class, 'laporanExportPdf'])->name('admin.peminjaman.laporan.export-pdf');
    Route::get('/admin/laporan-peminjaman/export-excel', [PeminjamanController::class, 'laporanExportExcel'])->name('admin.peminjaman.laporan.export-excel');
});

require __DIR__.'/auth.php';