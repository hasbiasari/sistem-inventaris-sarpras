<?php

namespace App\Http\Controllers;

use App\Imports\MahasiswaImport;
use App\Models\AsetKelas;
use App\Models\AsetUmum;
use App\Models\Mahasiswa;
use App\Models\Peminjaman;
use App\Models\PeminjamanDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class MahasiswaController extends Controller
{
    // Halaman Admin TU - List Data Mahasiswa
   public function index(Request $request)
{
    $keyword = $request->input('cari');

    $mahasiswas = Mahasiswa::with('user')
        ->when($keyword, function ($query, $keyword) {
            $query->where('nim', 'like', "%{$keyword}%")
                  ->orWhere('nama', 'like', "%{$keyword}%")
                  ->orWhereHas('user', function ($q) use ($keyword) {
                      $q->where('email', 'like', "%{$keyword}%");
                  });
        })
        ->orderBy('nama')
        ->get();

    return view('mahasiswa.index', compact('mahasiswas', 'keyword'));
}

    // Form Tambah Mahasiswa Manual
    public function create()
    {
        return view('mahasiswa.create');
    }

    // Simpan Data Mahasiswa Baru (Manual)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nim'   => ['required', 'string', 'max:20', 'unique:mahasiswas,nim'],
            'nama'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
        ]);

        $user = User::create([
            'name'     => $validated['nama'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['nim']),
            'role'     => 'mahasiswa',
        ]);

        Mahasiswa::create([
            'user_id' => $user->id,
            'nim'     => $validated['nim'],
            'nama'    => $validated['nama'],
            'status'  => 'Aktif',
        ]);

        return redirect()->route('admin.mahasiswa')->with('success', 'Mahasiswa berhasil ditambahkan.');
    }

    // Proses upload file excel buat import banyak mahasiswa sekaligus
    public function import(Request $request)
{
    $request->validate([
        'file_excel' => 'required|mimes:xlsx,xls',
    ]);

    $import = new MahasiswaImport;
    Excel::import($import, $request->file('file_excel'));

    $pesan = "Import selesai: {$import->jumlahBaru} data baru ditambahkan, {$import->jumlahUpdate} data diupdate.";

    return redirect()->route('admin.mahasiswa')->with('success', $pesan);
}

    // reset password mahasiswa balik ke default (NIM)
    public function resetPassword(Mahasiswa $mahasiswa)
    {
        $mahasiswa->user->update([
            'password' => Hash::make($mahasiswa->nim),
        ]);

        return redirect()->route('admin.mahasiswa')->with('success', 'Password ' . $mahasiswa->nama . ' berhasil direset ke NIM.');
    }

    // form edit data mahasiswa
    public function edit(Mahasiswa $mahasiswa)
    {
        return view('mahasiswa.edit', compact('mahasiswa'));
    }

    // simpan perubahan data mahasiswa
    public function update(Request $request, Mahasiswa $mahasiswa)
    {
        $validated = $request->validate([
            'nim'   => ['required', 'string', 'max:20', 'unique:mahasiswas,nim,' . $mahasiswa->id],
            'nama'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $mahasiswa->user_id],
        ]);

        $mahasiswa->update([
            'nim'  => $validated['nim'],
            'nama' => $validated['nama'],
        ]);

        $mahasiswa->user->update([
            'name'  => $validated['nama'],
            'email' => $validated['email'],
        ]);

        return redirect()->route('admin.mahasiswa')->with('success', 'Data ' . $mahasiswa->nama . ' berhasil diupdate.');
    }

    // hapus data mahasiswa
    public function destroy(Mahasiswa $mahasiswa)
    {
        $nama = $mahasiswa->nama;
        $mahasiswa->user->delete(); // ini otomatis ikut hapus data mahasiswa juga (cascade)

        return redirect()->route('admin.mahasiswa')->with('success', 'Data ' . $nama . ' berhasil dihapus.');
    }

    // tempelin JADWAL "siapa aja yang minjem hari ini (atau tanggal yang dicek)" ke tiap alat --
    // barang sekarang dijadwalin per jam kayak ruangan, jadi ini nunjukin SEMUA peminjaman yang
    // aktif (bukan cuma 3 terbaru) buat tanggal itu, diurutin dari jam paling pagi
    private function tempelPeminjamTerbaru($koleksiAlat, $tanggal = null)
    {
        $tanggalEfektif = $tanggal ?: now()->toDateString();

        $koleksiAlat->load([
            'peminjamanDetails' => function ($q) use ($tanggalEfektif) {
                $q->whereHas('peminjaman', function ($q2) use ($tanggalEfektif) {
                    $q2->where('status', 'disetujui')
                        ->where('tanggal_pakai', '<=', $tanggalEfektif)
                        ->whereRaw('COALESCE(tanggal_selesai, tanggal_pakai) >= ?', [$tanggalEfektif]);
                })->with(['peminjaman.mahasiswa', 'peminjaman.asetKelas']);
            },
            // dipakai buat status_efektif/jumlah_tersedia_sekarang (badge Tersedia/Dipinjam) --
            // beda dari relasi di atas karena ini presisi jam SEKARANG, bukan cuma "aktif hari ini"
            'peminjamanDetailAktifSekarang',
        ]);

        return $koleksiAlat->each(function ($alat) {
            $riwayat = $alat->peminjamanDetails
                ->sortBy(fn ($detail) => $detail->peminjaman->jam_mulai)
                ->map(function ($detail) {
                    $peminjaman = $detail->peminjaman;

                    return [
                        'nama' => $peminjaman->nama_peminjam,
                        'kelas' => $peminjaman->kelas,
                        'ormawa' => $peminjaman->ormawa,
                        'tanggal_pakai' => $peminjaman->tanggal_pakai?->format('d/m/Y'),
                        'ruangan' => $peminjaman->asetKelas->nama_ruangan ?? null,
                        'jam_mulai' => $peminjaman->jam_mulai ? substr($peminjaman->jam_mulai, 0, 5) : null,
                        'jam_selesai' => $peminjaman->jam_selesai ? substr($peminjaman->jam_selesai, 0, 5) : null,
                    ];
                })->values();

            $alat->riwayat_peminjam = $riwayat;
        });
    }

    // status_efektif dihitung ulang dari peminjaman yang aktif SEKARANG (bukan kolom `status`
    // statis, yang cuma keisi manual pas aset dibuat/diedit admin dan gak pernah keupdate
    // otomatis pas ada mahasiswa minjam/balikin alat). Dipisah jadi method sendiri biar bisa
    // dipanggil ulang dari endpoint polling (dashboardAsetUmumData) tanpa duplikasi query,
    // biar tile/chart di tab Dashboard ikut real-time kayak halaman Aset Umum.
    private function hitungStatusAsetUmum()
    {
        $semuaAsetUntukStatus = AsetUmum::with('peminjamanDetailAktifSekarang')->get();

        $statusAset = [
            'tersedia' => $semuaAsetUntukStatus->where('status_efektif', 'tersedia')->count(),
            'dipinjam' => $semuaAsetUntukStatus->where('status_efektif', 'dipinjam')->count(),
            'rusak' => $semuaAsetUntukStatus->where('status_efektif', 'rusak')->count(),
            'pemeliharaan' => $semuaAsetUntukStatus->where('status_efektif', 'pemeliharaan')->count(),
        ];

        // daftar nama tiap aset umum per status, buat drill-down pas grafik status di-klik
        $daftarAsetUmumPerStatus = $semuaAsetUntukStatus->sortBy('nama_alat')->groupBy('status_efektif')->map(function ($group) {
            return $group->map(fn ($a) => $a->nama_alat . ($a->nomor_unit ? " ({$a->nomor_unit})" : ''))->values();
        });

        return compact('statusAset', 'daftarAsetUmumPerStatus');
    }

    // endpoint polling buat tile & chart status Aset Umum di tab Dashboard, dipanggil AJAX
    // tiap beberapa detik (sama kayak pola status-ruangan buat tab Aset Kelas)
    public function dashboardAsetUmumData()
    {
        return response()->json($this->hitungStatusAsetUmum());
    }

    // Halaman Mahasiswa - Dashboard (ringkasan aset umum, aset kelas/ruangan, & peminjaman sendiri)
    public function dashboard()
    {
        $totalAsetUmum = AsetUmum::count();
        $totalAsetKelas = AsetKelas::count();

        ['statusAset' => $statusAset, 'daftarAsetUmumPerStatus' => $daftarAsetUmumPerStatus] = $this->hitungStatusAsetUmum();

        $mahasiswa = Auth::user()->mahasiswa;
        $statistikSaya = [
            'total' => Peminjaman::milikMahasiswa($mahasiswa->id)->count(),
            'menunggu' => Peminjaman::milikMahasiswa($mahasiswa->id)->where('status', 'menunggu')->count(),
            'disetujui' => Peminjaman::milikMahasiswa($mahasiswa->id)->where('status', 'disetujui')->count(),
            'ditolak' => Peminjaman::milikMahasiswa($mahasiswa->id)->where('status', 'ditolak')->count(),
        ];

        // barang yang PALING SERING DIPINJAM OLEH MAHASISWA INI SENDIRI (bukan se-kampus), buat tab Peminjaman Saya
        $rekapBarang = PeminjamanDetail::whereHas('peminjaman', fn ($q) => $q->milikMahasiswa($mahasiswa->id)->where('status', 'disetujui'))
            ->with('asetUmum')
            ->get()
            ->groupBy(fn ($d) => $d->asetUmum->nama_alat ?? 'Tidak diketahui')
            ->map(fn ($group) => $group->sum('jumlah'))
            ->sortDesc()
            ->take(6);

        // jumlah peminjaman (punya sendiri) per bulan, 6 bulan terakhir, buat grafik tren di tab Peminjaman Saya
        $labelBulanSaya = [];
        $dataBulanSaya = [];
        for ($i = 5; $i >= 0; $i--) {
            $bulan = now()->subMonths($i);
            $labelBulanSaya[] = $bulan->translatedFormat('M Y');
            $dataBulanSaya[] = Peminjaman::milikMahasiswa($mahasiswa->id)
                ->whereYear('created_at', $bulan->year)
                ->whereMonth('created_at', $bulan->month)
                ->count();
        }

        // stok per barang (top 10 terbanyak), buat grafik batang di tab Aset Umum
        $stokPerBarang = AsetUmum::orderByDesc('jumlah_stok')->take(10)->get(['nama_alat', 'jumlah_stok']);

        return view('mahasiswa.dashboard', compact(
            'totalAsetUmum',
            'totalAsetKelas',
            'statusAset',
            'statistikSaya',
            'rekapBarang',
            'labelBulanSaya',
            'dataBulanSaya',
            'stokPerBarang',
            'daftarAsetUmumPerStatus'
        ));
    }

    // Halaman Mahasiswa - Aset Umum
   public function asetUmum()
{
    $semuaAlat = $this->tempelPeminjamTerbaru(AsetUmum::orderBy('nama_alat')->get());

    return view('mahasiswa.aset-umum', compact('semuaAlat'));
}

    // endpoint buat polling data stok terbaru (dipanggil otomatis tiap beberapa detik).
    // bisa dikasih ?tanggal= buat ngecek riwayat peminjam di tanggal tertentu (bukan yang terbaru).
    public function asetUmumData(Request $request)
    {
        $tanggal = $request->input('tanggal');
        $asetUmum = $this->tempelPeminjamTerbaru(AsetUmum::orderBy('nama_alat')->get(), $tanggal);

        return response()->json([
            'tanggal' => $tanggal ?: now()->toDateString(),
            'aset' => $asetUmum,
        ]);
    }
}