<?php

namespace App\Http\Controllers;

use App\Models\AsetKelas;
use App\Models\Peminjaman;
use Illuminate\Http\Request;

class AsetKelasController extends Controller
{
    // halaman khusus buat admin ngecek jadwal 
    public function jadwalRuangan(Request $request)
    {
        $tanggal = $request->input('tanggal') ?: now()->toDateString();

        $daftarRuangan = AsetKelas::orderBy('nama_ruangan')->get()->map(function ($kelas) use ($tanggal) {
            $jadwal = Peminjaman::where('aset_kelas_id', $kelas->id)
                ->where('tanggal_pakai', '<=', $tanggal)
                ->whereRaw('COALESCE(tanggal_selesai, tanggal_pakai) >= ?', [$tanggal])
                ->whereIn('status', ['menunggu', 'disetujui'])
                ->with(['mahasiswa', 'details.asetUmum'])
                ->orderBy('jam_mulai')
                ->get()
                ->map(function ($p) {
                    return [
                        'jam_mulai' => substr($p->jam_mulai, 0, 5),
                        'jam_selesai' => substr($p->jam_selesai, 0, 5),
                        'nama' => $p->nama_peminjam,
                        'kelas' => $p->kelas,
                        'ormawa' => $p->ormawa,
                        'kategori' => $p->kategori ?? 'eksternal',
                        'status' => $p->status,
                        'sampai_tanggal' => $p->tanggal_selesai?->format('d/m/Y'),
                        'barang' => $p->details->map(fn ($d) => ($d->asetUmum->nama_alat ?? '-') . ($d->asetUmum->nomor_unit ? " ({$d->asetUmum->nomor_unit})" : '') . ' x' . $d->jumlah)->join(', '),
                    ];
                });

            return [
                'id' => $kelas->id,
                'nama_ruangan' => $kelas->nama_ruangan,
                'jadwal' => $jadwal,
            ];
        });

        return view('admin.jadwal-ruangan', compact('daftarRuangan', 'tanggal'));
    }

    // status tiap ruangan: lagi kepakai siapa atau kosong, di tanggal & jam tertentu
    // (default: sekarang - dipakai buat status real-time. Bisa dikasih ?tanggal=&jam= buat ngecek waktu lain)
    public function statusRuangan(Request $request)
    {
        $tanggal = $request->input('tanggal') ?: now()->toDateString();
        // kalau tanggal dikasih tapi jam kosong (mode "cek jadwal 1 hari penuh", bukan cek detik ini),
        // jam di-default ke 00:00 biar "sedang_dipakai" gak nyasar kebetulan ketiban jam sekarang di
        // dunia nyata -- info lengkapnya tetap ada lewat jadwal_hari_ini yang independen dari jam.
        $jam = $request->filled('jam')
            ? $request->input('jam')
            : ($request->filled('tanggal') ? '00:00' : now()->format('H:i'));

        $ruangan = AsetKelas::orderBy('nama_ruangan')->get()->map(function ($kelas) use ($tanggal, $jam) {
            $titikWaktu = "{$tanggal} {$jam}";

            $sedangDipakai = Peminjaman::where('aset_kelas_id', $kelas->id)
                // peminjaman multi-hari (organisasi/eksternal) dianggap SATU rentang waktu yang
                // jalan terus dari (tanggal_pakai+jam_mulai) s.d. (tanggal_selesai+jam_selesai) --
                // bukan jam yang sama berulang tiap hari
                ->whereRaw('TIMESTAMP(tanggal_pakai, jam_mulai) <= ?', [$titikWaktu])
                ->whereRaw('TIMESTAMP(COALESCE(tanggal_selesai, tanggal_pakai), jam_selesai) >= ?', [$titikWaktu])
                ->where(function ($q) use ($tanggal, $jam) {
                    // masih dipinjam (belum dikembalikan) -> dianggap masih dipakai selama masih
                    // dalam rentang jam rencana. Kalau udah dikembalikan (misal dosen gak jadi
                    // masuk/selesai lebih cepat), baru dianggap "masih dipakai" di jam sekian
                    // kalau emang jam kembalinya lebih telat dari jam yang lagi dicek.
                    $q->where('status', 'disetujui')
                      ->orWhere(function ($q2) use ($tanggal, $jam) {
                          $q2->where('status', 'selesai')
                             ->whereDate('waktu_kembali', $tanggal)
                             ->whereTime('waktu_kembali', '>', $jam);
                      });
                })
                ->with(['mahasiswa', 'details.asetUmum'])
                ->first();

            $daftarBarang = $sedangDipakai
                ? $sedangDipakai->details->map(fn ($d) => $d->asetUmum->nama_alat . ' x' . $d->jumlah)->join(', ')
                : null;

            // jadwal SATU HARI PENUH buat ruangan ini (bukan cuma yang lagi kepakai detik ini),
            // biar mahasiswa bisa liat "nanti jam sekian ruangannya udah dipesen" walau sekarang masih kosong.
            // Yang lagi kepakai SEKARANG udah ditampilin di kolom Status/Jam, jadi gak usah didobelin di sini.
            $jadwalHariIni = Peminjaman::where('aset_kelas_id', $kelas->id)
                ->where('tanggal_pakai', '<=', $tanggal)
                ->whereRaw('COALESCE(tanggal_selesai, tanggal_pakai) >= ?', [$tanggal])
                ->whereIn('status', ['menunggu', 'disetujui'])
                ->when($sedangDipakai, fn ($q) => $q->where('id', '!=', $sedangDipakai->id))
                ->with(['mahasiswa', 'details.asetUmum'])
                ->orderBy('jam_mulai')
                ->get()
                ->map(fn ($p) => [
                    'jam_mulai' => substr($p->jam_mulai, 0, 5),
                    'jam_selesai' => substr($p->jam_selesai, 0, 5),
                    // pakai accessor nama_peminjam biar aman buat booking eksternal juga
                    // (gak punya relasi mahasiswa, cuma nama_eksternal)
                    'nama' => $p->nama_peminjam,
                    'kelas' => $p->kelas,
                    'ormawa' => $p->ormawa,
                    'kategori' => $p->kategori ?? 'eksternal',
                    // ditampilin kalau peminjaman ini multi-hari, biar keliatan bukan cuma hari ini doang
                    'sampai_tanggal' => $p->tanggal_selesai?->format('d/m/Y'),
                    // barang/proyektor yang ikut dibawa bareng peminjaman ruangan ini (kalau ada)
                    'barang' => $p->details->isNotEmpty()
                        ? $p->details->map(fn ($d) => ($d->asetUmum->nama_alat ?? '-') . ($d->asetUmum->nomor_unit ? " ({$d->asetUmum->nomor_unit})" : '') . ' x' . $d->jumlah)->join(', ')
                        : null,
                    'status' => $p->status,
                ])
                ->values();

            return [
                'id' => $kelas->id,
                'nama_ruangan' => $kelas->nama_ruangan,
                'sedang_dipakai' => (bool) $sedangDipakai,
                'dipakai_oleh' => $sedangDipakai->nama_peminjam ?? null,
                'kelas_peminjam' => $sedangDipakai->kelas ?? null,
                'ormawa_peminjam' => $sedangDipakai->ormawa ?? null,
                'kategori' => $sedangDipakai->kategori ?? ($sedangDipakai ? 'eksternal' : null),
                'barang' => $daftarBarang,
                'jam_mulai' => $sedangDipakai->jam_mulai ?? null,
                'jam_selesai' => $sedangDipakai->jam_selesai ?? null,
                'jadwal_hari_ini' => $jadwalHariIni,
            ];
        });

        return response()->json([
            'tanggal' => $tanggal,
            'jam' => $jam,
            'ruangan' => $ruangan,
        ]);
    }
    // Halaman Mahasiswa - Aset Kelas (detail status tiap ruangan, real-time)
    public function mahasiswaIndex()
    {
        return view('mahasiswa.aset-kelas');
    }

    // tampilin semua data aset kelas
    public function index(Request $request)
{
    $keyword = $request->input('cari');

    $asetKelas = AsetKelas::when($keyword, function ($query, $keyword) {
            $query->where('nama_ruangan', 'like', "%{$keyword}%");
        })
        ->orderBy('nama_ruangan')
        ->get();

    return view('aset-kelas.index', compact('asetKelas', 'keyword'));
}

    // form tambah aset kelas
    public function create()
    {
        return view('aset-kelas.create');
    }

    // simpan aset kelas baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_ruangan'       => 'required|string|max:255',
            'kapasitas'          => 'required|integer|min:1',
            'jumlah_kursi'       => 'required|integer|min:0',
            'jumlah_papan_tulis' => 'required|integer|min:0',
        ]);

        AsetKelas::create($validated);

        return redirect()->route('admin.aset-kelas')->with('success', 'Aset kelas berhasil ditambahkan.');
    }

    // form edit aset kelas
    public function edit(AsetKelas $asetKela)
    {
        return view('aset-kelas.edit', ['asetKelas' => $asetKela]);
    }

    // update aset kelas
    public function update(Request $request, AsetKelas $asetKela)
    {
        $validated = $request->validate([
            'nama_ruangan'       => 'required|string|max:255',
            'kapasitas'          => 'required|integer|min:1',
            'jumlah_kursi'       => 'required|integer|min:0',
            'jumlah_papan_tulis' => 'required|integer|min:0',
        ]);

        $asetKela->update($validated);

        return redirect()->route('admin.aset-kelas')->with('success', 'Aset kelas berhasil diupdate.');
    }

    // hapus aset kelas
    public function destroy(AsetKelas $asetKela)
    {
        $asetKela->delete();

        return redirect()->route('admin.aset-kelas')->with('success', 'Aset kelas berhasil dihapus.');
    }
}