<?php

namespace App\Http\Controllers;

use App\Models\AsetKelas;
use App\Models\AsetUmum;
use App\Models\BuktiPengembalian;
use App\Models\Notifikasi;
use App\Models\Peminjaman;
use App\Models\PeminjamanDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PeminjamanController extends Controller
{
    public function create()
    {
        $mahasiswa = Auth::user()->mahasiswa;
        $daftarAlat = AsetUmum::whereNotIn('status', ['rusak', 'pemeliharaan'])->orderBy('nama_alat')->get();
        $daftarRuangan = AsetKelas::orderBy('nama_ruangan')->get();

        return view('peminjaman.create', compact('mahasiswa', 'daftarAlat', 'daftarRuangan'));
    }

    // cek apakah ruangan yang mau dipakai udah kepakai/kepesan di rentang WAKTU yang overlap.
    // $tanggalSelesai boleh null (peminjaman sehari doang, kayak kuliah) -- efeknya dianggap sama
    // kayak $tanggalMulai. Peminjaman diperlakukan sebagai SATU rentang waktu yang jalan terus
    // dari (tanggal_pakai + jam_mulai) sampai (tanggal_selesai + jam_selesai) -- BUKAN jam yang
    // sama diulang tiap hari. Jadi misal booking 28/07 13:38 s.d. 29/07 14:00, ruangannya kepakai
    // terus-menerus dari jam segitu di tanggal itu sampai jam segitu di tanggal satunya, bukan
    // cuma jam 13:38-14:00 doang tiap hari.
    private function ruanganBentrok($asetKelasId, $tanggalMulai, $tanggalSelesai, $jamMulai, $jamSelesai, $kecualiPeminjamanId = null)
    {
        $mulaiRequest = "{$tanggalMulai} {$jamMulai}";
        $selesaiRequest = ($tanggalSelesai ?: $tanggalMulai) . " {$jamSelesai}";

        return Peminjaman::where('aset_kelas_id', $asetKelasId)
            ->whereIn('status', ['menunggu', 'disetujui'])
            ->when($kecualiPeminjamanId, fn ($q) => $q->where('id', '!=', $kecualiPeminjamanId))
            // overlap rentang datetime kontinu: mulai peminjaman lain < selesai punya kita,
            // DAN selesai peminjaman lain > mulai punya kita
            ->whereRaw('TIMESTAMP(tanggal_pakai, jam_mulai) < ?', [$selesaiRequest])
            ->whereRaw('TIMESTAMP(COALESCE(tanggal_selesai, tanggal_pakai), jam_selesai) > ?', [$mulaiRequest])
            ->exists();
    }

    // dipanggil AJAX dari form Ajukan/Edit Peminjaman, biar mahasiswa tau ruangannya bentrok
    // SEBELUM submit -- bukan baru ketahuan pas ditolak sesudah kirim. Aturan bentroknya sama
    // persis kayak yang dipakai pas store()/update() (ruanganBentrok), jadi hasilnya konsisten.
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

    // sisa stok barang yang BENERAN tersedia pada rentang WAKTU tertentu -- bukan cuma ngurangin
    // counter permanen kayak dulu, tapi dihitung ulang tiap kali dari peminjaman yang masih aktif
    // (menunggu/disetujui) dan rentang datetime-nya overlap, persis logic ruanganBentrok() (satu
    // rentang waktu kontinu dari tanggal_pakai+jam_mulai s.d. tanggal_selesai+jam_selesai).
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
                $q->whereIn('status', ['menunggu', 'disetujui'])
                    ->when($kecualiPeminjamanId, fn ($qq) => $qq->where('id', '!=', $kecualiPeminjamanId))
                    ->whereRaw('TIMESTAMP(tanggal_pakai, jam_mulai) < ?', [$selesaiRequest])
                    ->whereRaw('TIMESTAMP(COALESCE(tanggal_selesai, tanggal_pakai), jam_selesai) > ?', [$mulaiRequest]);
            })
            ->sum('jumlah');

        return $alat->jumlah_stok - $terpakai;
    }

    // dipanggil AJAX dari form Ajukan/Edit/Booking Eksternal, biar daftar barang nunjukin sisa
    // stok yang REAL buat tanggal+jam yang lagi dipilih (bukan angka statis jumlah_stok)
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

    // pesan validasi bahasa Indonesia, dipakai bareng di store() & update() biar mahasiswa
    // gampang paham errornya (bawaan Laravel bahasa Inggris)
    private function pesanValidasiPeminjaman()
    {
        return [
            'kelas.required' => 'Kelas wajib diisi.',
            'ormawa.required' => 'Nama ORMAWA wajib diisi.',
            'ormawa.required_if' => 'Nama ORMAWA wajib diisi.',
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

    // kuliah otomatis disetujui (gak lewat approval admin), jadi tetap boleh diedit mahasiswa
    // sendiri selama belum dikembalikan -- buat jaga-jaga kalau pas ambil barang ternyata
    // kondisinya beda (rusak dll) dan admin belum sempat update datanya.
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

        // barang yang udah dipilih di peminjaman ini, buat pre-fill "keranjang" di form edit
        $barangDipilihAwal = $peminjaman->details->map(function ($detail) {
            $alat = $detail->asetUmum;

            return [
                'id' => (string) $detail->aset_umum_id,
                'nama' => $alat->nama_alat . ($alat->nomor_unit ? " ({$alat->nomor_unit})" : ''),
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
        // kuliah tetap sehari doang, cuma organisasi yang boleh multi-hari
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

        foreach ($daftarBarang as $item) {
            $tersedia = $this->stokBarangTersedia($item['aset_umum_id'], $tanggalPakai, $tanggalSelesai, $jamMulai, $jamSelesai, $peminjaman->id);
            if ($tersedia < $item['jumlah']) {
                $alat = AsetUmum::find($item['aset_umum_id']);
                return back()->withErrors([
                    'barang' => "Stok {$alat->nama_alat} tidak mencukupi pada jam tersebut (tersisa {$tersedia})",
                ])->withInput();
            }
        }

        $peminjaman->kelas = $request->kelas;
        $peminjaman->ormawa = $request->ormawa;
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

    // data buat popup "struk" bukti pengajuan, ditunjukin mahasiswa ke Admin TU pas nganter barang
    // (TU kadang lebih dari 1 orang jaga, jadi siapa aja yang lagi jaga bisa langsung tahu detailnya)
    private function buatStruk($mahasiswa, Peminjaman $peminjaman, array $barang)
    {
        return [
            'nama' => $mahasiswa->nama,
            'nim' => $mahasiswa->nim,
            'kelas' => $peminjaman->kelas,
            'ormawa' => $peminjaman->ormawa,
            'kategori' => ucfirst($peminjaman->kategori),
            'status' => ucfirst($peminjaman->status),
            'waktu_mulai' => $peminjaman->created_at->format('d/m/Y H:i'),
            'ruangan' => $peminjaman->aset_kelas_id
                ? $peminjaman->asetKelas->nama_ruangan . ' (' . substr($peminjaman->jam_mulai, 0, 5) . '-' . substr($peminjaman->jam_selesai, 0, 5) . ')'
                : null,
            'barang' => collect($barang)->map(function ($item) {
                $alat = AsetUmum::find($item['aset_umum_id']);
                return ($alat->nama_alat ?? '-') . ' x' . $item['jumlah'];
            })->all(),
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'kategori' => 'required|in:kuliah,organisasi',
            'kelas' => 'required|string|max:50',
            'ormawa' => 'required_if:kategori,organisasi|nullable|string|max:100',
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

        // ruangan opsional (baik kuliah maupun organisasi bisa pilih tanggal bebas ke depan),
        // tapi tanggal+jam SELALU dipakai sekarang -- barang pun dicek bentrok berdasarkan jam,
        // bukan cuma stok, jadi butuh jendela waktu meski gak pilih ruangan sama sekali
        $asetKelasId = $request->aset_kelas_id;
        $daftarBarang = $request->barang ?? [];
        $tanggalPakai = $request->tanggal_pakai;
        // kuliah tetap sehari doang, cuma organisasi yang boleh multi-hari
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

        foreach ($daftarBarang as $item) {
            $tersedia = $this->stokBarangTersedia($item['aset_umum_id'], $tanggalPakai, $tanggalSelesai, $jamMulai, $jamSelesai);
            if ($tersedia < $item['jumlah']) {
                $alat = AsetUmum::find($item['aset_umum_id']);
                return back()->withErrors([
                    'barang' => "Stok {$alat->nama_alat} tidak mencukupi pada jam tersebut (tersisa {$tersedia})",
                ])->withInput();
            }
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
                'link' => route('admin.peminjaman.laporan', ['filter' => 'organisasi'], false),
            ]);
        }

        return redirect()->route('peminjaman.create')
            ->with('success', 'Peminjaman acara organisasi berhasil diajukan, menunggu persetujuan Admin TU.')
            ->with('struk', $this->buatStruk($mahasiswa, $peminjaman, $daftarBarang));
    }

    // mahasiswa balikin barang: upload foto bukti (bisa lebih dari 1), stok balik, status jadi selesai
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

        $peminjaman->update([
            'status' => 'selesai',
            'waktu_kembali' => now(),
        ]);

        return redirect()->route('peminjaman.show', $peminjaman->id)
            ->with('success', 'Barang berhasil dikembalikan.');
    }

    // hitung breakdown status (menunggu/disetujui/ditolak/dibatalkan/selesai) dari 1 koleksi peminjaman,
    // dipakai buat grafik yang berubah-ubah pas admin/pimpinan klik tombol filter kategori
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

    // daftar semua peminjaman (kuliah, organisasi, eksternal) jadi 1 halaman, biar admin gampang
    // filter per kategori (tombol Semua/Kuliah/Organisasi/Eksternal) + lihat grafik distribusinya.
    // Aksi Setujui/Tolak/Batalkan tetap ada, sama kayak di halaman kuliah-index/organisasi-index.
    public function laporan(Request $request)
    {
        $query = Peminjaman::with(['mahasiswa', 'details.asetUmum', 'asetKelas', 'buktiPengembalian']);

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('created_at', '>=', $request->dari_tanggal);
        }

        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('created_at', '<=', $request->sampai_tanggal);
        }

        $daftarPeminjaman = $query->latest()->get();

        $jumlahKuliah = $daftarPeminjaman->where('kategori', 'kuliah')->count();
        $jumlahOrganisasi = $daftarPeminjaman->where('kategori', 'organisasi')->count();
        $jumlahEksternal = $daftarPeminjaman->where('jenis_peminjam', 'eksternal')->count();

        // breakdown status per kategori, buat grafik yang ikut berubah pas tombol filter diklik
        $statusPerKategori = [
            'semua' => $this->hitungStatusPeminjaman($daftarPeminjaman),
            'kuliah' => $this->hitungStatusPeminjaman($daftarPeminjaman->where('kategori', 'kuliah')),
            'organisasi' => $this->hitungStatusPeminjaman($daftarPeminjaman->where('kategori', 'organisasi')),
            'eksternal' => $this->hitungStatusPeminjaman($daftarPeminjaman->where('jenis_peminjam', 'eksternal')),
        ];

        // filter kategori awal (dari link dashboard/notifikasi, misal ?filter=organisasi),
        // biar admin langsung liat yang relevan tanpa perlu klik tab kategori manual dulu
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

    // admin batalin peminjaman (kuliah/organisasi/eksternal) yang udah disetujui, kalau ada kendala
    // di lapangan atau barangnya gak kunjung dibalikin. Barang/ruangan otomatis "bebas" lagi begitu
    // status keluar dari [menunggu, disetujui] -- gak perlu balikin stok manual lagi.
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

        // booking eksternal gak punya akun mahasiswa, jadi gak ada yang dikirimin notifikasi
        if ($peminjaman->mahasiswa) {
            Notifikasi::create([
                'user_id' => $peminjaman->mahasiswa->user_id,
                'pesan' => "Peminjaman {$kategoriPesan} kamu dibatalkan oleh Admin TU." . ($request->catatan_admin ? " Alasan: {$request->catatan_admin}" : ''),
                'link' => route('peminjaman.show', $peminjaman->id, false),
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
            'link' => route('peminjaman.show', $peminjaman->id, false),
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
            'link' => route('peminjaman.show', $peminjaman->id, false),
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

        // siapin data buat grafik jumlah peminjaman per bulan, 6 bulan terakhir
        $labelBulan = [];
        $dataPerBulan = [];
        for ($i = 5; $i >= 0; $i--) {
            $bulan = now()->subMonths($i);
            $labelBulan[] = $bulan->translatedFormat('M Y');
            $dataPerBulan[] = $daftarPeminjaman->filter(function ($p) use ($bulan) {
                return $p->created_at->format('Y-m') === $bulan->format('Y-m');
            })->count();
        }

        // siapin data buat grafik jenis barang paling sering dipinjam
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
        // baris barang yang aset_umum_id-nya kosong (row default yang gak diisi admin) dianggap
        // gak ada, biar booking bisa cuma pinjam ruangan doang tanpa barang
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

        foreach ($daftarBarang as $item) {
            $tersedia = $this->stokBarangTersedia($item['aset_umum_id'], $tanggalPakai, $tanggalSelesai, $jamMulai, $jamSelesai);
            if ($tersedia < $item['jumlah']) {
                $alat = AsetUmum::find($item['aset_umum_id']);
                return back()->withErrors([
                    'barang' => "Stok {$alat->nama_alat} tidak mencukupi pada jam tersebut (tersisa {$tersedia})",
                ])->withInput();
            }
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
