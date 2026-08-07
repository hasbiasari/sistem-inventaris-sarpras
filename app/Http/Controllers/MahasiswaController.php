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
    // daftar mahasiswa buat Admin TU
    public function index(Request $request)
    {
        $keyword = $request->input('cari');

        // urutan diinget lewat session, biar gak reset ke a-z tiap balik ke halaman ini
        if ($request->has('urutan')) {
            session(['urutan_mahasiswa' => $request->input('urutan')]);
        }
        $urutan = session('urutan_mahasiswa', 'a-z');

        $mahasiswas = Mahasiswa::with('user')
            ->when($keyword, function ($query, $keyword) {
                $query->where('nim', 'like', "%{$keyword}%")
                      ->orWhere('nama', 'like', "%{$keyword}%")
                      ->orWhereHas('user', function ($q) use ($keyword) {
                          $q->where('email', 'like', "%{$keyword}%");
                      });
            })
            ->when($urutan === 'terbaru', function ($query) {
                $query->orderByDesc('created_at');
            }, function ($query) {
                $query->orderBy('nama');
            })
            ->get();

        return view('mahasiswa.index', compact('mahasiswas', 'keyword', 'urutan'));
    }

    // form tambah mahasiswa manual
    public function create()
    {
        return view('mahasiswa.create');
    }

    // simpan mahasiswa baru
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

    // import mahasiswa dari excel
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
        $mahasiswa->user->delete(); // cascade ikut hapus data mahasiswa

        return redirect()->route('admin.mahasiswa')->with('success', 'Data ' . $nama . ' berhasil dihapus.');
    }

    // tempelin jadwal peminjam ke tiap alat, buat tanggal tertentu
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
            // buat badge status_efektif/jumlah_tersedia_sekarang, presisi jam sekarang
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

    // status_efektif dihitung ulang dari peminjaman aktif sekarang, dipakai bareng endpoint polling
    private function hitungStatusAsetUmum()
    {
        $semuaAsetUntukStatus = AsetUmum::with('peminjamanDetailAktifSekarang')->get();

        $statusAset = [
            'tersedia' => $semuaAsetUntukStatus->where('status_efektif', 'tersedia')->count(),
            'dipinjam' => $semuaAsetUntukStatus->where('status_efektif', 'dipinjam')->count(),
            'rusak' => $semuaAsetUntukStatus->where('status_efektif', 'rusak')->count(),
            'pemeliharaan' => $semuaAsetUntukStatus->where('status_efektif', 'pemeliharaan')->count(),
        ];

        // buat drill-down pas grafik status di-klik
        $daftarAsetUmumPerStatus = $semuaAsetUntukStatus->sortBy('nama_alat')->groupBy('status_efektif')->map(function ($group) {
            return $group->map(fn ($a) => $a->nama_lengkap)->values();
        });

        return compact('statusAset', 'daftarAsetUmumPerStatus');
    }

    // endpoint polling status aset umum buat tab Dashboard
    public function dashboardAsetUmumData()
    {
        return response()->json($this->hitungStatusAsetUmum());
    }

    // dashboard mahasiswa: ringkasan aset & peminjaman sendiri
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

        // barang paling sering dipinjam mahasiswa ini, buat tab Peminjaman Saya
        // (disetujui = lagi dipinjam, selesai = udah pernah dipinjam & balik -- dua-duanya tetap "pernah dipinjam")
        $rekapBarang = PeminjamanDetail::whereHas('peminjaman', fn ($q) => $q->milikMahasiswa($mahasiswa->id)->whereIn('status', ['disetujui', 'selesai']))
            ->with('asetUmum')
            ->get()
            ->groupBy(fn ($d) => $d->asetUmum->nama_alat ?? 'Tidak diketahui')
            ->map(fn ($group) => $group->sum('jumlah'))
            ->sortDesc()
            ->take(6);

        // grafik tren peminjaman per bulan, 6 bulan terakhir
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

        // grafik batang stok, top 10 barang
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

    // halaman aset umum buat mahasiswa
    public function asetUmum()
    {
        $semuaAlat = $this->tempelPeminjamTerbaru(AsetUmum::orderBy('nama_alat')->get());

        return view('mahasiswa.aset-umum', compact('semuaAlat'));
    }

    // endpoint polling stok terbaru, opsional ?tanggal=
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