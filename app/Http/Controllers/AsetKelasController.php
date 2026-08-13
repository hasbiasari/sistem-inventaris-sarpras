<?php

namespace App\Http\Controllers;

use App\Models\AsetKelas;
use App\Models\Peminjaman;
use Illuminate\Http\Request;

class AsetKelasController extends Controller
{
    // jadwal ruangan buat admin
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
                        'nama_kegiatan' => $p->nama_kegiatan,
                        'keterangan_eksternal' => $p->keterangan_eksternal,
                        'kategori' => $p->kategori ?? 'eksternal',
                        'status' => $p->status,
                        'catatan_admin' => $p->catatan_admin,
                        'dokumen_izin' => $p->dokumen_izin,
                        'waktu_mulai' => $p->created_at->format('d/m/Y H:i'),
                        'rentang_tanggal' => $p->rentang_tanggal,
                        'sampai_tanggal' => $p->tanggal_selesai?->format('d/m/Y'),
                        'barang' => $p->details->map(fn ($d) => ($d->asetUmum->nama_lengkap ?? '-') . ' x' . $d->jumlah)->values()->all(),
                    ];
                });

            return [
                'id' => $kelas->id,
                'nama_ruangan' => $kelas->nama_ruangan,
                'gedung' => $kelas->gedung ?? 'Lainnya',
                'jadwal' => $jadwal,
            ];
        });

        // urutan tampil: Gedung A-D dulu, ruangan tanpa gedung ("Lainnya") di paling akhir
        $urutanGedung = ['Gedung A', 'Gedung B', 'Gedung C', 'Gedung D', 'Lainnya'];
        $ruanganPerGedung = $daftarRuangan->groupBy('gedung')->sortBy(function ($grup, $gedung) use ($urutanGedung) {
            $posisi = array_search($gedung, $urutanGedung);

            return $posisi === false ? count($urutanGedung) : $posisi;
        });

        // urutan ruangan buat tabel: ngikutin urutan gedung, alfabetis di dalam tiap gedung
        $daftarRuanganUrut = $ruanganPerGedung->flatten(1);

        // ringkasan per gedung buat progress bar & kartu grafik
        $ringkasanGedung = $ruanganPerGedung->map(function ($ruanganGedung, $gedung) {
            $total = $ruanganGedung->count();
            $adaJadwal = $ruanganGedung->filter(fn ($r) => $r['jadwal']->isNotEmpty())->count();

            return [
                'gedung' => $gedung,
                'total' => $total,
                'ada_jadwal' => $adaJadwal,
            ];
        })->values();

        $totalRuangan = $daftarRuangan->count();
        $totalAdaJadwal = $daftarRuangan->filter(fn ($r) => $r['jadwal']->isNotEmpty())->count();
        $totalKosong = $totalRuangan - $totalAdaJadwal;

        return view('admin.jadwal-ruangan', compact(
            'daftarRuanganUrut',
            'ruanganPerGedung',
            'ringkasanGedung',
            'totalRuangan',
            'totalAdaJadwal',
            'totalKosong',
            'tanggal'
        ));
    }

    // status real-time tiap ruangan (kosong/kepakai), opsional ?tanggal=&jam=
    public function statusRuangan(Request $request)
    {
        $tanggal = $request->input('tanggal') ?: now()->toDateString();
        // tanggal ada tapi jam kosong -> default 00:00 (mode cek jadwal 1 hari penuh)
        $jam = $request->filled('jam')
            ? $request->input('jam')
            : ($request->filled('tanggal') ? '00:00' : now()->format('H:i'));

        $ruangan = AsetKelas::orderBy('nama_ruangan')->get()->map(function ($kelas) use ($tanggal, $jam) {
            $titikWaktu = "{$tanggal} {$jam}";

            $sedangDipakai = Peminjaman::where('aset_kelas_id', $kelas->id)
                // rentang waktu kontinu (multi-hari dihitung nyambung, bukan jam yang sama tiap hari)
                ->whereRaw('TIMESTAMP(tanggal_pakai, jam_mulai) <= ?', [$titikWaktu])
                ->whereRaw('TIMESTAMP(COALESCE(tanggal_selesai, tanggal_pakai), jam_selesai) >= ?', [$titikWaktu])
                ->where(function ($q) use ($tanggal, $jam) {
                    // udah dikembalikan tapi lebih telat dari jam yang dicek -> masih dianggap dipakai
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
                ? $sedangDipakai->details->map(fn ($d) => $d->asetUmum->nama_lengkap . ' x' . $d->jumlah)->join(', ')
                : null;

            // jadwal 1 hari penuh, di luar yang lagi kepakai sekarang (sudah ada di kolom Status/Jam)
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
                    'nama' => $p->nama_peminjam,
                    'kelas' => $p->kelas,
                    'ormawa' => $p->ormawa,
                    'kategori' => $p->kategori ?? 'eksternal',
                    'sampai_tanggal' => $p->tanggal_selesai?->format('d/m/Y'),
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

    // status ruangan buat mahasiswa
    public function mahasiswaIndex()
    {
        return view('mahasiswa.aset-kelas');
    }

    // tampilin semua data aset kelas
    public function index(Request $request)
    {
        $keyword = $request->input('cari');

        // urutan diinget lewat session, biar gak reset ke a-z tiap balik ke halaman ini
        if ($request->has('urutan')) {
            session(['urutan_aset_kelas' => $request->input('urutan')]);
        }
        $urutan = session('urutan_aset_kelas', 'a-z');

        $asetKelas = AsetKelas::when($keyword, function ($query, $keyword) {
                $query->where('nama_ruangan', 'like', "%{$keyword}%");
            })
            ->when($urutan === 'terbaru', function ($query) {
                $query->orderByDesc('created_at');
            }, function ($query) {
                $query->orderBy('nama_ruangan');
            })
            ->get();

        return view('aset-kelas.index', compact('asetKelas', 'keyword', 'urutan'));
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
            'gedung'             => 'nullable|string|max:50',
            'kapasitas'          => 'nullable|integer|min:0',
            'jumlah_kursi'       => 'required|integer|min:0',
            'jumlah_papan_tulis' => 'required|integer|min:0',
        ]);

        $validated['kapasitas'] = $validated['kapasitas'] ?? 0;

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
            'gedung'             => 'nullable|string|max:50',
            'kapasitas'          => 'nullable|integer|min:0',
            'jumlah_kursi'       => 'required|integer|min:0',
            'jumlah_papan_tulis' => 'required|integer|min:0',
        ]);

        $validated['kapasitas'] = $validated['kapasitas'] ?? 0;

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