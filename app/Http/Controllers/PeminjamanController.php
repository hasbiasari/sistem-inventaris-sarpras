<?php

namespace App\Http\Controllers;

use App\Models\AsetKelas;
use App\Models\AsetUmum;
use App\Models\BuktiPengembalian;
use App\Models\Notifikasi;
use App\Models\Peminjaman;
use App\Models\PeminjamanDetail;
use App\Models\User;
use App\Exports\PeminjamanExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class PeminjamanController extends Controller
{
    public function create()
    {
        $mahasiswa = Auth::user()->mahasiswa;
        $daftarAlat = AsetUmum::whereNotIn('status', ['rusak', 'pemeliharaan'])->orderBy('nama_alat')->get();
        $daftarRuangan = AsetKelas::orderBy('nama_ruangan')->get();

        return view('peminjaman.create', compact('mahasiswa', 'daftarAlat', 'daftarRuangan'));
    }

    // cek ruangan bentrok jadwal, rentang waktu kontinu (bukan jam yang sama diulang tiap hari)
    private function ruanganBentrok($asetKelasId, $tanggalMulai, $tanggalSelesai, $jamMulai, $jamSelesai, $kecualiPeminjamanId = null)
    {
        $mulaiRequest = "{$tanggalMulai} {$jamMulai}";
        $selesaiRequest = ($tanggalSelesai ?: $tanggalMulai) . " {$jamSelesai}";

        return Peminjaman::where('aset_kelas_id', $asetKelasId)
            ->whereIn('status', ['menunggu', 'disetujui'])
            ->when($kecualiPeminjamanId, fn ($q) => $q->where('id', '!=', $kecualiPeminjamanId))
            // overlap: mulai peminjaman lain < selesai kita, selesai peminjaman lain > mulai kita
            ->whereRaw('TIMESTAMP(tanggal_pakai, jam_mulai) < ?', [$selesaiRequest])
            ->whereRaw('TIMESTAMP(COALESCE(tanggal_selesai, tanggal_pakai), jam_selesai) > ?', [$mulaiRequest])
            ->exists();
    }

    // cek bentrok ruangan via AJAX, sebelum form disubmit
    public function cekRuanganBentrok(Request $request)
    {
        $request->validate([
            'aset_kelas_id' => 'required|exists:aset_kelas,id',
            'tanggal_pakai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_pakai',
            'jam_mulai' => 'required|date_format:H:i,H:i:s',
            'jam_selesai' => 'required|date_format:H:i,H:i:s',
        ]);

        $bentrok = $this->ruanganBentrok(
            $request->aset_kelas_id,
            $request->tanggal_pakai,
            $request->tanggal_selesai,
            $request->jam_mulai,
            $request->jam_selesai,
            $request->kecuali
        );

        return response()->json(['bentrok' => $bentrok]);
    }

    // sisa stok barang yang beneran tersedia di rentang waktu tertentu (dihitung ulang tiap panggil)
    private function stokBarangTersedia($asetUmumId, $tanggalMulai, $tanggalSelesai, $jamMulai, $jamSelesai, $kecualiPeminjamanId = null)
    {
        $alat = AsetUmum::find($asetUmumId);

        if (! $alat || in_array($alat->status, ['rusak', 'pemeliharaan'])) {
            return 0;
        }

        $mulaiRequest = "{$tanggalMulai} {$jamMulai}";
        $selesaiRequest = ($tanggalSelesai ?: $tanggalMulai) . " {$jamSelesai}";

        $terpakai = PeminjamanDetail::where('aset_umum_id', $asetUmumId)
            ->whereHas('peminjaman', function ($q) use ($mulaiRequest, $selesaiRequest, $kecualiPeminjamanId) {
                $q->when($kecualiPeminjamanId, fn ($qq) => $qq->where('id', '!=', $kecualiPeminjamanId))
                    ->where(function ($q2) use ($mulaiRequest, $selesaiRequest) {
                        // menunggu: overlap normal berdasarkan jadwal yang diajukan
                        $q2->where(function ($qMenunggu) use ($mulaiRequest, $selesaiRequest) {
                            $qMenunggu->where('status', 'menunggu')
                                ->whereRaw('TIMESTAMP(tanggal_pakai, jam_mulai) < ?', [$selesaiRequest])
                                ->whereRaw('TIMESTAMP(COALESCE(tanggal_selesai, tanggal_pakai), jam_selesai) > ?', [$mulaiRequest]);
                        })
                        // disetujui & belum dikembalikan: dianggap kepake terus dari jam mulai,
                        // gak peduli udah lewat jadwal jam selesai apa belum (belum tentu udah balik)
                        ->orWhere(function ($qDisetujui) use ($selesaiRequest) {
                            $qDisetujui->where('status', 'disetujui')
                                ->whereRaw('TIMESTAMP(tanggal_pakai, jam_mulai) < ?', [$selesaiRequest]);
                        });
                    });
            })
            ->sum('jumlah');

        return $alat->jumlah_stok - $terpakai;
    }

    // cek sisa stok barang via AJAX buat tanggal+jam yang dipilih
    public function cekStokBarang(Request $request)
    {
        $request->validate([
            'tanggal_pakai' => 'required|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_pakai',
            'jam_mulai' => 'required|date_format:H:i,H:i:s',
            'jam_selesai' => 'required|date_format:H:i,H:i:s',
        ]);

        $stok = AsetUmum::whereNotIn('status', ['rusak', 'pemeliharaan'])
            ->get()
            ->mapWithKeys(function ($alat) use ($request) {
                return [$alat->id => $this->stokBarangTersedia(
                    $alat->id,
                    $request->tanggal_pakai,
                    $request->tanggal_selesai,
                    $request->jam_mulai,
                    $request->jam_selesai,
                    $request->kecuali
                )];
            });

        return response()->json($stok);
    }

    // pastiin total jumlah yang diminta gak ngelebihin stok -- dijumlahin dulu per aset_umum_id
    // soalnya barang yang sama bisa muncul di beberapa baris (misal request hasil manipulasi manual, bypass merge di JS)
    private function validasiStokBarang(array $daftarBarang, $tanggalPakai, $tanggalSelesai, $jamMulai, $jamSelesai, $kecualiPeminjamanId = null)
    {
        $totalPerBarang = collect($daftarBarang)
            ->groupBy('aset_umum_id')
            ->map(fn ($items) => $items->sum('jumlah'));

        foreach ($totalPerBarang as $asetUmumId => $totalDiminta) {
            $tersedia = $this->stokBarangTersedia($asetUmumId, $tanggalPakai, $tanggalSelesai, $jamMulai, $jamSelesai, $kecualiPeminjamanId);
            if ($tersedia < $totalDiminta) {
                $alat = AsetUmum::find($asetUmumId);
                return "Jumlah {$alat->nama_alat} melebihi stok tersedia (sisa: {$tersedia}).";
            }
        }

        return null;
    }

    // pesan validasi bahasa Indonesia buat store() & update()
    private function pesanValidasiPeminjaman()
    {
        return [
            'kelas.required' => 'Kelas wajib diisi.',
            'ormawa.required' => 'Nama ORMAWA wajib diisi.',
            'ormawa.required_if' => 'Nama ORMAWA wajib diisi.',
            'nama_kegiatan.required' => 'Nama Kegiatan wajib diisi.',
            'nama_kegiatan.required_if' => 'Nama Kegiatan wajib diisi.',
            'barang.*.aset_umum_id.required' => 'Barang tidak boleh kosong.',
            'barang.*.aset_umum_id.exists' => 'Barang yang dipilih tidak valid.',
            'barang.*.jumlah.required' => 'Jumlah barang wajib diisi.',
            'barang.*.jumlah.integer' => 'Jumlah barang harus berupa angka.',
            'barang.*.jumlah.min' => 'Jumlah barang minimal 1.',
            'dokumen_izin.required_if' => 'Dokumen izin wajib diupload.',
            'dokumen_izin.file' => 'Dokumen izin harus berupa file.',
            'dokumen_izin.mimes' => 'Dokumen izin harus berformat PDF.',
            'dokumen_izin.max' => 'Ukuran dokumen izin maksimal 5 MB.',
            'aset_kelas_id.exists' => 'Ruangan yang dipilih tidak valid.',
            'tanggal_pakai.required' => 'Tanggal pakai wajib diisi.',
            'tanggal_pakai.date' => 'Format tanggal tidak valid.',
            'tanggal_pakai.after_or_equal' => 'Tanggal pakai tidak boleh sebelum hari ini.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_mulai.date_format' => 'Format jam mulai tidak valid.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.date_format' => 'Format jam selesai tidak valid.',
            'jam_selesai.after' => 'Jam selesai harus lebih besar (setelah) jam mulai.',
        ];
    }

    // kuliah masih boleh diedit sendiri selama belum dikembalikan (auto-approved, gak lewat admin)
    private function bisaDiedit(Peminjaman $peminjaman)
    {
        return $peminjaman->status === 'menunggu'
            || ($peminjaman->kategori === 'kuliah' && $peminjaman->status === 'disetujui');
    }

    public function show(Peminjaman $peminjaman)
    {
        if ($peminjaman->mahasiswa_id !== Auth::user()->mahasiswa->id) {
            abort(403);
        }

        $peminjaman->load(['details.asetUmum', 'buktiPengembalian']);

        return view('peminjaman.show', compact('peminjaman'));
    }

    public function edit(Peminjaman $peminjaman)
    {
        if ($peminjaman->mahasiswa_id !== Auth::user()->mahasiswa->id) {
            abort(403);
        }

        if (! $this->bisaDiedit($peminjaman)) {
            return redirect()->route('peminjaman.show', $peminjaman->id)
                ->with('error', 'Peminjaman ini sudah diproses, tidak bisa diubah lagi.');
        }

        $peminjaman->load('details');
        $daftarAlat = AsetUmum::whereNotIn('status', ['rusak', 'pemeliharaan'])->orderBy('nama_alat')->get();
        $daftarRuangan = AsetKelas::orderBy('nama_ruangan')->get();

        // pre-fill keranjang barang di form edit
        $barangDipilihAwal = $peminjaman->details->map(function ($detail) {
            $alat = $detail->asetUmum;

            return [
                'id' => (string) $detail->aset_umum_id,
                'nama' => $alat->nama_lengkap,
                'jumlah' => $detail->jumlah,
                'stok' => $alat->jumlah_stok,
            ];
        })->values();

        return view('peminjaman.edit', compact('peminjaman', 'daftarAlat', 'daftarRuangan', 'barangDipilihAwal'));
    }

    public function update(Request $request, Peminjaman $peminjaman)
    {
        if ($peminjaman->mahasiswa_id !== Auth::user()->mahasiswa->id) {
            abort(403);
        }

        if (! $this->bisaDiedit($peminjaman)) {
            return redirect()->route('peminjaman.show', $peminjaman->id)
                ->with('error', 'Peminjaman ini sudah diproses, tidak bisa diubah lagi.');
        }

        $request->validate([
            'kelas' => 'required|string|max:50',
            'ormawa' => $peminjaman->kategori === 'organisasi' ? 'required|string|max:100' : 'nullable|string|max:100',
            'nama_kegiatan' => $peminjaman->kategori === 'organisasi' ? 'required|string|max:150' : 'nullable|string|max:150',
            'barang' => 'nullable|array',
            'barang.*.aset_umum_id' => 'required|exists:aset_umums,id',
            'barang.*.jumlah' => 'required|integer|min:1',
            'dokumen_izin' => 'nullable|file|mimes:pdf|max:5120',
            'aset_kelas_id' => 'nullable|exists:aset_kelas,id',
            'tanggal_pakai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_pakai',
            'jam_mulai' => 'required|date_format:H:i,H:i:s',
            'jam_selesai' => 'required|date_format:H:i,H:i:s|after:jam_mulai',
        ], $this->pesanValidasiPeminjaman());

        $asetKelasId = $request->aset_kelas_id;
        $daftarBarang = $request->barang ?? [];
        $tanggalPakai = $request->tanggal_pakai;
        // hanya organisasi yang boleh multi-hari
        $tanggalSelesai = $peminjaman->kategori === 'organisasi' ? $request->tanggal_selesai : null;
        $jamMulai = $request->jam_mulai;
        $jamSelesai = $request->jam_selesai;

        if (! $asetKelasId && empty($daftarBarang)) {
            return back()->withErrors([
                'barang' => 'Pilih minimal ruangan atau barang yang mau dipinjam.',
            ])->withInput();
        }

        if ($asetKelasId && $this->ruanganBentrok($asetKelasId, $tanggalPakai, $tanggalSelesai, $jamMulai, $jamSelesai, $peminjaman->id)) {
            return back()->withErrors([
                'aset_kelas_id' => 'Ruangan ini sudah dipakai/dipesan pada jam tersebut.',
            ])->withInput();
        }

        if ($pesanErrorStok = $this->validasiStokBarang($daftarBarang, $tanggalPakai, $tanggalSelesai, $jamMulai, $jamSelesai, $peminjaman->id)) {
            return back()->withErrors(['barang' => $pesanErrorStok])->withInput();
        }

        $peminjaman->kelas = $request->kelas;
        $peminjaman->ormawa = $request->ormawa;
        $peminjaman->nama_kegiatan = $request->nama_kegiatan;
        $peminjaman->aset_kelas_id = $asetKelasId;
        $peminjaman->tanggal_pakai = $tanggalPakai;
        $peminjaman->tanggal_selesai = $tanggalSelesai;
        $peminjaman->jam_mulai = $jamMulai;
        $peminjaman->jam_selesai = $jamSelesai;

        if ($request->hasFile('dokumen_izin')) {
            $peminjaman->dokumen_izin = $request->file('dokumen_izin')->store('dokumen-izin', 'public');
        }

        $peminjaman->save();

        $peminjaman->details()->delete();

        foreach ($daftarBarang as $item) {
            PeminjamanDetail::create([
                'peminjaman_id' => $peminjaman->id,
                'aset_umum_id' => $item['aset_umum_id'],
                'jumlah' => $item['jumlah'],
            ]);
        }

        return redirect()->route('peminjaman.show', $peminjaman->id)
            ->with('success', 'Peminjaman berhasil diperbarui.');
    }

    // data struk bukti pengajuan, ditunjukin ke Admin TU pas nganter barang
    private function buatStruk($mahasiswa, Peminjaman $peminjaman, array $barang)
    {
        return [
            'nama' => $mahasiswa->nama,
            'nim' => $mahasiswa->nim,
            'kelas' => $peminjaman->kelas,
            'ormawa' => $peminjaman->ormawa,
            'nama_kegiatan' => $peminjaman->nama_kegiatan,
            'kategori' => ucfirst($peminjaman->kategori),
            'status' => ucfirst($peminjaman->status),
            'waktu_mulai' => $peminjaman->created_at->format('d/m/Y H:i'),
            'ruangan' => $peminjaman->aset_kelas_id
                ? $peminjaman->asetKelas->nama_ruangan . ' (' . substr($peminjaman->jam_mulai, 0, 5) . '-' . substr($peminjaman->jam_selesai, 0, 5) . ')'
                : null,
            'barang' => collect($barang)->map(function ($item) {
                $alat = AsetUmum::find($item['aset_umum_id']);
                return ($alat->nama_lengkap ?? '-') . ' x' . $item['jumlah'];
            })->all(),
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'kategori' => 'required|in:kuliah,organisasi',
            'kelas' => 'required|string|max:50',
            'ormawa' => 'required_if:kategori,organisasi|nullable|string|max:100',
            'nama_kegiatan' => 'required_if:kategori,organisasi|nullable|string|max:150',
            'barang' => 'nullable|array',
            'barang.*.aset_umum_id' => 'required|exists:aset_umums,id',
            'barang.*.jumlah' => 'required|integer|min:1',
            'dokumen_izin' => 'required_if:kategori,organisasi|nullable|file|mimes:pdf|max:5120',
            'aset_kelas_id' => 'nullable|exists:aset_kelas,id',
            'tanggal_pakai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_pakai',
            'jam_mulai' => 'required|date_format:H:i,H:i:s',
            'jam_selesai' => 'required|date_format:H:i,H:i:s|after:jam_mulai',
        ], $this->pesanValidasiPeminjaman());

        $mahasiswa = Auth::user()->mahasiswa;

        $asetKelasId = $request->aset_kelas_id;
        $daftarBarang = $request->barang ?? [];
        $tanggalPakai = $request->tanggal_pakai;
        // hanya organisasi yang boleh multi-hari
        $tanggalSelesai = $request->kategori === 'organisasi' ? $request->tanggal_selesai : null;
        $jamMulai = $request->jam_mulai;
        $jamSelesai = $request->jam_selesai;

        if (! $asetKelasId && empty($daftarBarang)) {
            return back()->withErrors([
                'barang' => 'Pilih minimal ruangan atau barang yang mau dipinjam.',
            ])->withInput();
        }

        if ($asetKelasId && $this->ruanganBentrok($asetKelasId, $tanggalPakai, $tanggalSelesai, $jamMulai, $jamSelesai)) {
            return back()->withErrors([
                'aset_kelas_id' => 'Ruangan ini sudah dipakai/dipesan pada jam tersebut.',
            ])->withInput();
        }

        if ($pesanErrorStok = $this->validasiStokBarang($daftarBarang, $tanggalPakai, $tanggalSelesai, $jamMulai, $jamSelesai)) {
            return back()->withErrors(['barang' => $pesanErrorStok])->withInput();
        }

        if ($request->kategori === 'kuliah') {
            $peminjaman = Peminjaman::create([
                'jenis_peminjam' => 'mahasiswa',
                'mahasiswa_id' => $mahasiswa->id,
                'kelas' => $request->kelas,
                'kategori' => 'kuliah',
                'status' => 'disetujui',
                'aset_kelas_id' => $asetKelasId,
                'tanggal_pakai' => $tanggalPakai,
                'tanggal_selesai' => $tanggalSelesai,
                'jam_mulai' => $jamMulai,
                'jam_selesai' => $jamSelesai,
            ]);

            foreach ($daftarBarang as $item) {
                PeminjamanDetail::create([
                    'peminjaman_id' => $peminjaman->id,
                    'aset_umum_id' => $item['aset_umum_id'],
                    'jumlah' => $item['jumlah'],
                ]);
            }

            $adminList = User::where('role', 'admin_tu')->get();
            foreach ($adminList as $admin) {
                Notifikasi::create([
                    'user_id' => $admin->id,
                    'pesan' => "Pengajuan peminjaman kuliah baru dari {$mahasiswa->nama}",
                    'link' => route('admin.peminjaman.laporan', ['filter' => 'kuliah', 'highlight' => $peminjaman->id]),
                ]);
            }

            return redirect()->route('peminjaman.create')
                ->with('success', 'Peminjaman berhasil diajukan dan otomatis disetujui!')
                ->with('struk', $this->buatStruk($mahasiswa, $peminjaman, $daftarBarang));
        }

        $pathDokumen = $request->file('dokumen_izin')->store('dokumen-izin', 'public');

        $peminjaman = Peminjaman::create([
            'jenis_peminjam' => 'mahasiswa',
            'mahasiswa_id' => $mahasiswa->id,
            'kelas' => $request->kelas,
            'ormawa' => $request->ormawa,
            'nama_kegiatan' => $request->nama_kegiatan,
            'kategori' => 'organisasi',
            'status' => 'menunggu',
            'dokumen_izin' => $pathDokumen,
            'aset_kelas_id' => $asetKelasId,
            'tanggal_pakai' => $tanggalPakai,
            'tanggal_selesai' => $tanggalSelesai,
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $jamSelesai,
        ]);

        foreach ($daftarBarang as $item) {
            PeminjamanDetail::create([
                'peminjaman_id' => $peminjaman->id,
                'aset_umum_id' => $item['aset_umum_id'],
                'jumlah' => $item['jumlah'],
            ]);
        }

        $adminList = User::where('role', 'admin_tu')->get();
        foreach ($adminList as $admin) {
            Notifikasi::create([
                'user_id' => $admin->id,
                'pesan' => "Pengajuan peminjaman organisasi baru dari {$mahasiswa->nama}",
                'link' => route('admin.peminjaman.laporan', ['filter' => 'organisasi', 'highlight' => $peminjaman->id]),
            ]);
        }

        return redirect()->route('peminjaman.create')
            ->with('success', 'Peminjaman acara organisasi berhasil diajukan, menunggu persetujuan Admin TU.')
            ->with('struk', $this->buatStruk($mahasiswa, $peminjaman, $daftarBarang));
    }

    // tambahin jam pemakaian ke tiap unit proyektor yang ada di peminjaman ini
    private function tambahJamPakaiProyektor(Peminjaman $peminjaman)
    {
        foreach ($peminjaman->details as $detail) {
            $alat = $detail->asetUmum;

            if ($alat && $alat->nama_alat === 'Proyektor') {
                $alat->increment('total_jam_pakai', $peminjaman->durasi_jam * $detail->jumlah);
            }
        }
    }

    // proses pengembalian barang: upload foto bukti, status jadi selesai
    public function kembalikan(Request $request, Peminjaman $peminjaman)
    {
        if ($peminjaman->mahasiswa_id !== Auth::user()->mahasiswa->id) {
            abort(403);
        }

        if ($peminjaman->status !== 'disetujui') {
            return back()->with('error', 'Peminjaman ini tidak bisa dikembalikan.');
        }

        $request->validate([
            'foto' => 'required|array|min:1',
            'foto.*' => 'image|mimes:jpg,jpeg,png|max:4096',
        ]);

        foreach ($request->file('foto') as $foto) {
            BuktiPengembalian::create([
                'peminjaman_id' => $peminjaman->id,
                'foto' => $foto->store('bukti-pengembalian', 'public'),
            ]);
        }

        $peminjaman->load('details.asetUmum');
        $this->tambahJamPakaiProyektor($peminjaman);

        $peminjaman->update([
            'status' => 'selesai',
            'waktu_kembali' => now(),
        ]);

        // kasih tau admin barangnya udah balik, biar gak perlu ngecek manual satu-satu
        $adminList = User::where('role', 'admin_tu')->get();
        foreach ($adminList as $admin) {
            Notifikasi::create([
                'user_id' => $admin->id,
                'pesan' => "{$peminjaman->mahasiswa->nama} telah mengembalikan barang dari peminjaman {$peminjaman->kategori}.",
                'link' => route('admin.peminjaman.laporan', ['filter' => $peminjaman->kategori, 'highlight' => $peminjaman->id]),
            ]);
        }

        return redirect()->route('peminjaman.show', $peminjaman->id)
            ->with('success', 'Barang berhasil dikembalikan.');
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

    // laporan semua peminjaman, bisa difilter per kategori
    public function laporan(Request $request)
    {
        $daftarPeminjaman = $this->ambilDataLaporanPeminjaman($request);

        $jumlahKuliah = $daftarPeminjaman->where('kategori', 'kuliah')->count();
        $jumlahOrganisasi = $daftarPeminjaman->where('kategori', 'organisasi')->count();
        $jumlahEksternal = $daftarPeminjaman->where('jenis_peminjam', 'eksternal')->count();

        // breakdown status per kategori buat grafik filter
        $statusPerKategori = [
            'semua' => $this->hitungStatusPeminjaman($daftarPeminjaman),
            'kuliah' => $this->hitungStatusPeminjaman($daftarPeminjaman->where('kategori', 'kuliah')),
            'organisasi' => $this->hitungStatusPeminjaman($daftarPeminjaman->where('kategori', 'organisasi')),
            'eksternal' => $this->hitungStatusPeminjaman($daftarPeminjaman->where('jenis_peminjam', 'eksternal')),
        ];

        // filter kategori awal, misal dari link ?filter=organisasi
        $filterAwal = in_array($request->input('filter'), ['kuliah', 'organisasi', 'eksternal'])
            ? $request->input('filter')
            : 'semua';

        return view('peminjaman.laporan', compact(
            'daftarPeminjaman',
            'jumlahKuliah',
            'jumlahOrganisasi',
            'jumlahEksternal',
            'statusPerKategori',
            'filterAwal'
        ));
    }

    // query dasar laporan peminjaman (filter tanggal) -- dipakai bareng sama laporan() & export PDF/Excel
    private function ambilDataLaporanPeminjaman(Request $request)
    {
        $query = Peminjaman::with(['mahasiswa', 'details.asetUmum', 'asetKelas', 'buktiPengembalian'])->bukanSimulasi();

        // filter berdasarkan tanggal pakai (bukan created_at), overlap sama rentang tanggal_pakai..tanggal_selesai
        if ($request->filled('dari_tanggal')) {
            $query->whereRaw('COALESCE(tanggal_selesai, tanggal_pakai) >= ?', [$request->dari_tanggal]);
        }

        if ($request->filled('sampai_tanggal')) {
            $query->where('tanggal_pakai', '<=', $request->sampai_tanggal);
        }

        return $query->latest()->get();
    }

    // laporan peminjaman ngikutin filter kategori & tanggal yang lagi aktif -- dipakai bareng export PDF & Excel
    private function dataLaporanPeminjamanTerfilter(Request $request): array
    {
        $daftarPeminjaman = $this->ambilDataLaporanPeminjaman($request);

        $filterKategori = in_array($request->input('filter'), ['kuliah', 'organisasi', 'eksternal'])
            ? $request->input('filter')
            : 'semua';

        if ($filterKategori !== 'semua') {
            $daftarPeminjaman = $daftarPeminjaman->filter(function ($p) use ($filterKategori) {
                return $filterKategori === 'eksternal'
                    ? $p->jenis_peminjam === 'eksternal'
                    : $p->kategori === $filterKategori;
            })->values();
        }

        $labelFilter = match ($filterKategori) {
            'kuliah' => 'Kategori Kuliah',
            'organisasi' => 'Kategori Organisasi',
            'eksternal' => 'Kategori Eksternal',
            default => 'Semua Kategori',
        };

        if ($request->filled('dari_tanggal') || $request->filled('sampai_tanggal')) {
            $dari = $request->filled('dari_tanggal') ? \Carbon\Carbon::parse($request->dari_tanggal)->format('d/m/Y') : '...';
            $sampai = $request->filled('sampai_tanggal') ? \Carbon\Carbon::parse($request->sampai_tanggal)->format('d/m/Y') : '...';
            $labelFilter .= ", {$dari} s.d. {$sampai}";
        }

        $ringkasanStatus = $this->hitungStatusPeminjaman($daftarPeminjaman);

        return [$daftarPeminjaman, $labelFilter, $ringkasanStatus];
    }

    // export laporan peminjaman ke PDF, ngikutin filter kategori & tanggal yang lagi aktif di halaman
    public function laporanExportPdf(Request $request)
    {
        [$daftarPeminjaman, $labelFilter, $ringkasanStatus] = $this->dataLaporanPeminjamanTerfilter($request);
        $judulLaporan = 'Laporan Peminjaman';

        $pdf = Pdf::loadView('peminjaman.laporan-pdf', compact('daftarPeminjaman', 'ringkasanStatus', 'judulLaporan', 'labelFilter'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('laporan-peminjaman-' . now()->format('Y-m-d') . '.pdf');
    }

    // export laporan peminjaman ke Excel, filter sama persis kayak export PDF
    public function laporanExportExcel(Request $request)
    {
        [$daftarPeminjaman, $labelFilter, $ringkasanStatus] = $this->dataLaporanPeminjamanTerfilter($request);

        return Excel::download(
            new PeminjamanExport($daftarPeminjaman, $labelFilter, $ringkasanStatus),
            'laporan-peminjaman-' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    // admin batalin peminjaman yang udah disetujui
    private function batalkanPeminjamanDisetujui(Request $request, Peminjaman $peminjaman, string $kategoriPesan)
    {
        if ($peminjaman->status !== 'disetujui') {
            return back()->with('error', 'Peminjaman ini tidak bisa dibatalkan.');
        }

        $request->validate([
            'catatan_admin' => 'nullable|string|max:255',
        ]);

        $peminjaman->update([
            'status' => 'dibatalkan',
            'catatan_admin' => $request->catatan_admin,
        ]);

        // eksternal gak punya akun mahasiswa, jadi gak ada notifikasi
        if ($peminjaman->mahasiswa) {
            Notifikasi::create([
                'user_id' => $peminjaman->mahasiswa->user_id,
                'pesan' => "Peminjaman {$kategoriPesan} kamu dibatalkan oleh Admin TU." . ($request->catatan_admin ? " Alasan: {$request->catatan_admin}" : ''),
                'link' => route('peminjaman.show', $peminjaman->id),
            ]);
        }

        return back()->with('success', "Peminjaman atas nama {$peminjaman->nama_peminjam} berhasil dibatalkan.");
    }

    public function kuliahBatalkan(Request $request, Peminjaman $peminjaman)
    {
        if ($peminjaman->kategori !== 'kuliah') {
            abort(403);
        }

        return $this->batalkanPeminjamanDisetujui($request, $peminjaman, 'kuliah');
    }

    public function organisasiBatalkan(Request $request, Peminjaman $peminjaman)
    {
        if ($peminjaman->kategori !== 'organisasi') {
            abort(403);
        }

        return $this->batalkanPeminjamanDisetujui($request, $peminjaman, 'organisasi');
    }

    public function eksternalBatalkan(Request $request, Peminjaman $peminjaman)
    {
        if ($peminjaman->jenis_peminjam !== 'eksternal') {
            abort(403);
        }

        return $this->batalkanPeminjamanDisetujui($request, $peminjaman, 'eksternal');
    }

    public function organisasiSetujui(Peminjaman $peminjaman)
    {
        foreach ($peminjaman->details as $detail) {
            $alat = $detail->asetUmum;
            $tersedia = $this->stokBarangTersedia(
                $detail->aset_umum_id,
                $peminjaman->tanggal_pakai->toDateString(),
                $peminjaman->tanggal_selesai?->toDateString(),
                $peminjaman->jam_mulai,
                $peminjaman->jam_selesai,
                $peminjaman->id
            );

            if ($tersedia < $detail->jumlah) {
                return back()->withErrors([
                    'stok' => "Stok {$alat->nama_alat} tidak mencukupi pada jam tersebut (tersisa {$tersedia}), tidak bisa disetujui.",
                ]);
            }
        }

        $peminjaman->update(['status' => 'disetujui']);

        Notifikasi::create([
            'user_id' => $peminjaman->mahasiswa->user_id,
            'pesan' => 'Peminjaman acara organisasi kamu telah disetujui.',
            'link' => route('peminjaman.show', $peminjaman->id),
        ]);

        return back()->with('success', "Peminjaman atas nama {$peminjaman->mahasiswa->nama} berhasil disetujui.");
    }

    public function organisasiTolak(Request $request, Peminjaman $peminjaman)
    {
        $request->validate([
            'catatan_admin' => 'nullable|string|max:255',
        ]);

        $peminjaman->update([
            'status' => 'ditolak',
            'catatan_admin' => $request->catatan_admin,
        ]);

        Notifikasi::create([
            'user_id' => $peminjaman->mahasiswa->user_id,
            'pesan' => 'Peminjaman acara organisasi kamu ditolak.' . ($request->catatan_admin ? " Alasan: {$request->catatan_admin}" : ''),
            'link' => route('peminjaman.show', $peminjaman->id),
        ]);

        return back()->with('success', "Peminjaman atas nama {$peminjaman->mahasiswa->nama} ditolak.");
    }

    public function riwayat()
    {
        $mahasiswa = Auth::user()->mahasiswa;

        $daftarPeminjaman = Peminjaman::with(['details.asetUmum', 'asetKelas', 'buktiPengembalian'])
            ->milikMahasiswa($mahasiswa->id)
            ->latest()
            ->get();

        // grafik jumlah peminjaman per bulan, 6 bulan terakhir
        $labelBulan = [];
        $dataPerBulan = [];
        for ($i = 5; $i >= 0; $i--) {
            $bulan = now()->subMonths($i);
            $labelBulan[] = $bulan->translatedFormat('M Y');
            $dataPerBulan[] = $daftarPeminjaman->filter(function ($p) use ($bulan) {
                return $p->created_at->format('Y-m') === $bulan->format('Y-m');
            })->count();
        }

        // grafik barang paling sering dipinjam
        $rekapBarang = [];
        foreach ($daftarPeminjaman as $peminjaman) {
            foreach ($peminjaman->details as $detail) {
                $namaBarang = $detail->asetUmum->nama_alat ?? 'Tidak diketahui';
                $rekapBarang[$namaBarang] = ($rekapBarang[$namaBarang] ?? 0) + $detail->jumlah;
            }
        }
        arsort($rekapBarang);

        $statistik = [
            'total' => $daftarPeminjaman->count(),
            'menunggu' => $daftarPeminjaman->where('status', 'menunggu')->count(),
            'disetujui' => $daftarPeminjaman->where('status', 'disetujui')->count(),
            'ditolak' => $daftarPeminjaman->where('status', 'ditolak')->count(),
        ];

        return view('peminjaman.riwayat', [
            'mahasiswa' => $mahasiswa,
            'daftarPeminjaman' => $daftarPeminjaman,
            'labelBulan' => $labelBulan,
            'dataPerBulan' => $dataPerBulan,
            'labelBarang' => array_keys($rekapBarang),
            'dataBarang' => array_values($rekapBarang),
            'statistik' => $statistik,
        ]);
    }

    public function eksternalCreate()
    {
        $daftarAlat = AsetUmum::whereNotIn('status', ['rusak', 'pemeliharaan'])->orderBy('nama_alat')->get();
        $daftarRuangan = AsetKelas::orderBy('nama_ruangan')->get();

        return view('peminjaman.eksternal-create', compact('daftarAlat', 'daftarRuangan'));
    }

    public function eksternalStore(Request $request)
    {
        // buang baris barang yang aset_umum_id-nya kosong
        $request->merge([
            'barang' => collect($request->input('barang', []))
                ->filter(fn ($item) => ! empty($item['aset_umum_id']))
                ->values()
                ->all(),
        ]);

        $request->validate([
            'nama_eksternal' => 'required|string|max:255',
            'keterangan_eksternal' => 'nullable|string|max:255',
            'barang' => 'nullable|array',
            'barang.*.aset_umum_id' => 'required|exists:aset_umums,id',
            'barang.*.jumlah' => 'required|integer|min:1',
            'aset_kelas_id' => 'nullable|exists:aset_kelas,id',
            'tanggal_pakai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_pakai',
            'jam_mulai' => 'required|date_format:H:i,H:i:s',
            'jam_selesai' => 'required|date_format:H:i,H:i:s|after:jam_mulai',
        ], $this->pesanValidasiPeminjaman());

        $asetKelasId = $request->aset_kelas_id;
        $daftarBarang = $request->barang ?? [];
        $tanggalPakai = $request->tanggal_pakai;
        $tanggalSelesai = $request->tanggal_selesai;
        $jamMulai = $request->jam_mulai;
        $jamSelesai = $request->jam_selesai;

        if (! $asetKelasId && empty($daftarBarang)) {
            return back()->withErrors([
                'barang' => 'Pilih minimal ruangan atau barang yang mau dipinjam.',
            ])->withInput();
        }

        if ($asetKelasId && $this->ruanganBentrok($asetKelasId, $tanggalPakai, $tanggalSelesai, $jamMulai, $jamSelesai)) {
            return back()->withErrors([
                'aset_kelas_id' => 'Ruangan ini sudah dipakai/dipesan pada jam tersebut.',
            ])->withInput();
        }

        if ($pesanErrorStok = $this->validasiStokBarang($daftarBarang, $tanggalPakai, $tanggalSelesai, $jamMulai, $jamSelesai)) {
            return back()->withErrors(['barang' => $pesanErrorStok])->withInput();
        }

        $peminjaman = Peminjaman::create([
            'jenis_peminjam' => 'eksternal',
            'nama_eksternal' => $request->nama_eksternal,
            'keterangan_eksternal' => $request->keterangan_eksternal,
            'status' => 'disetujui',
            'aset_kelas_id' => $asetKelasId,
            'tanggal_pakai' => $tanggalPakai,
            'tanggal_selesai' => $tanggalSelesai,
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $jamSelesai,
        ]);

        foreach ($daftarBarang as $item) {
            PeminjamanDetail::create([
                'peminjaman_id' => $peminjaman->id,
                'aset_umum_id' => $item['aset_umum_id'],
                'jumlah' => $item['jumlah'],
            ]);
        }

        return redirect()->route('admin.booking-eksternal')
            ->with('success', "Booking untuk {$request->nama_eksternal} berhasil disimpan.");
    }
}
