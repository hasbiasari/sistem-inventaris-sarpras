# Snippet Kode Penting — Increment 2

## Penentuan Status Otomatis + Validasi Dokumen Izin (Store Peminjaman)
**File:** app/Http/Controllers/PeminjamanController.php, method store()
```php
public function store(Request $request)
{
    $request->validate([
        'kategori' => 'required|in:kuliah,organisasi',
        // ...
        'dokumen_izin' => 'required_if:kategori,organisasi|nullable|file|mimes:pdf|max:5120',
        // ...
    ], $this->pesanValidasiPeminjaman());

    $mahasiswa = Auth::user()->mahasiswa;

    $asetKelasId = $request->aset_kelas_id;
    $daftarBarang = $request->barang ?? [];
    $tanggalPakai = $request->tanggal_pakai;
    // hanya organisasi yang boleh multi-hari
    $tanggalSelesai = $request->kategori === 'organisasi' ? $request->tanggal_selesai : null;
    $jamMulai = $request->jam_mulai;
    $jamSelesai = $request->jam_selesai;

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

        // ...

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

    // ...

    return redirect()->route('peminjaman.create')
        ->with('success', 'Peminjaman acara organisasi berhasil diajukan, menunggu persetujuan Admin TU.')
        ->with('struk', $this->buatStruk($mahasiswa, $peminjaman, $daftarBarang));
}
```

## Cek Bentrok Jadwal Ruangan
**File:** app/Http/Controllers/PeminjamanController.php, method ruanganBentrok() & cekRuanganBentrok()
```php
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
```

## Cek Sisa Stok Barang Real-time
**File:** app/Http/Controllers/PeminjamanController.php, method stokBarangTersedia() & cekStokBarang()
```php
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
```

## Proses Pengembalian Barang (Upload Bukti Foto + Update Jam Pakai Proyektor)
**File:** app/Http/Controllers/PeminjamanController.php, method kembalikan() & tambahJamPakaiProyektor()
```php
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

    return redirect()->route('peminjaman.show', $peminjaman->id)
        ->with('success', 'Barang berhasil dikembalikan.');
}
```

## Approve Pengajuan Organisasi
**File:** app/Http/Controllers/PeminjamanController.php, method organisasiSetujui()
```php
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
```

## Tolak Pengajuan Organisasi
**File:** app/Http/Controllers/PeminjamanController.php, method organisasiTolak()
```php
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
```

## Pembatalan Peminjaman (Kuliah / Organisasi / Eksternal)
**File:** app/Http/Controllers/PeminjamanController.php, method batalkanPeminjamanDisetujui(), kuliahBatalkan(), organisasiBatalkan() & eksternalBatalkan()
```php
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
```

## Simpan Booking Eksternal Manual oleh Admin
**File:** app/Http/Controllers/PeminjamanController.php, method eksternalStore()
```php
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
        // ...
    ], $this->pesanValidasiPeminjaman());

    $asetKelasId = $request->aset_kelas_id;
    $daftarBarang = $request->barang ?? [];
    $tanggalPakai = $request->tanggal_pakai;
    $tanggalSelesai = $request->tanggal_selesai;
    $jamMulai = $request->jam_mulai;
    $jamSelesai = $request->jam_selesai;

    // ... (cek bentrok ruangan & validasi stok, sama seperti store())

    // booking eksternal diinput manual oleh admin, jadi langsung disetujui (gak ada tahap approval)
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

    // ...

    return redirect()->route('admin.booking-eksternal')
        ->with('success', "Booking untuk {$request->nama_eksternal} berhasil disimpan.");
}
```

## Riwayat Peminjaman Mahasiswa yang Login
**File:** app/Http/Controllers/PeminjamanController.php, method riwayat()
```php
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
```

## Validasi Bisa Diedit + Update Peminjaman
**File:** app/Http/Controllers/PeminjamanController.php, method bisaDiedit(), edit() & update()
```php
// kuliah masih boleh diedit sendiri selama belum dikembalikan (auto-approved, gak lewat admin)
private function bisaDiedit(Peminjaman $peminjaman)
{
    return $peminjaman->status === 'menunggu'
        || ($peminjaman->kategori === 'kuliah' && $peminjaman->status === 'disetujui');
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

    // ...
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
        // ...
        'ormawa' => $peminjaman->kategori === 'organisasi' ? 'required|string|max:100' : 'nullable|string|max:100',
        'nama_kegiatan' => $peminjaman->kategori === 'organisasi' ? 'required|string|max:150' : 'nullable|string|max:150',
        // ...
    ], $this->pesanValidasiPeminjaman());

    // hanya organisasi yang boleh multi-hari
    $tanggalSelesai = $peminjaman->kategori === 'organisasi' ? $request->tanggal_selesai : null;

    // ... (cek bentrok ruangan & validasi stok, sama seperti store())
}
```

## Filter Laporan Peminjaman (Tanggal & Status per Kategori)
**File:** app/Http/Controllers/PeminjamanController.php, method laporan(), ambilDataLaporanPeminjaman() & hitungStatusPeminjaman()
```php
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

    // ...
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
```

## Accessor Peminjaman (Nama Peminjam, Rentang Tanggal/Waktu, Durasi Jam)
**File:** app/Models/Peminjaman.php
```php
public function getNamaPeminjamAttribute()
{
    if ($this->jenis_peminjam === 'eksternal') {
        return $this->nama_eksternal;
    }

    return $this->mahasiswa->nama ?? '-';
}

// format tanggal buat ditampilin
public function getRentangTanggalAttribute()
{
    if (! $this->tanggal_pakai) {
        return '-';
    }

    if ($this->tanggal_selesai && ! $this->tanggal_selesai->isSameDay($this->tanggal_pakai)) {
        return $this->tanggal_pakai->format('d/m/Y') . ' - ' . $this->tanggal_selesai->format('d/m/Y');
    }

    return $this->tanggal_pakai->format('d/m/Y');
}

// format tanggal+jam gabungan buat ditampilin
public function getRentangWaktuAttribute()
{
    if (! $this->tanggal_pakai || ! $this->jam_mulai || ! $this->jam_selesai) {
        return '-';
    }

    $jamMulai = substr($this->jam_mulai, 0, 5);
    $jamSelesai = substr($this->jam_selesai, 0, 5);

    if ($this->tanggal_selesai && ! $this->tanggal_selesai->isSameDay($this->tanggal_pakai)) {
        return $this->tanggal_pakai->format('d/m/Y') . " {$jamMulai} s.d. " . $this->tanggal_selesai->format('d/m/Y') . " {$jamSelesai}";
    }

    return $this->tanggal_pakai->format('d/m/Y') . " ({$jamMulai}-{$jamSelesai})";
}

// durasi pemakaian dalam jam (desimal), dari jam_mulai s.d. jam_selesai (ikut tanggal_selesai kalau multi-hari)
public function getDurasiJamAttribute()
{
    if (! $this->tanggal_pakai || ! $this->jam_mulai || ! $this->jam_selesai) {
        return 0;
    }

    $mulai = Carbon::parse($this->tanggal_pakai->format('Y-m-d') . ' ' . $this->jam_mulai);
    $tanggalSelesai = $this->tanggal_selesai ?? $this->tanggal_pakai;
    $selesai = Carbon::parse($tanggalSelesai->format('Y-m-d') . ' ' . $this->jam_selesai);

    return $mulai->diffInMinutes($selesai) / 60;
}
```

## Scope Peminjaman (Milik Mahasiswa & Bukan Data Simulasi)
**File:** app/Models/Peminjaman.php
```php
// penanda nama_eksternal buat data dummy histori pemakaian proyektor (lihat PemakaianProyektorSeeder) --
// dipakai biar data simulasi ini gak ikut kehitung sebagai aktivitas peminjaman asli di laporan/dashboard
const PENANDA_SIMULASI_SMA = 'Simulasi SMA';

// scope filter peminjaman punya 1 mahasiswa
public function scopeMilikMahasiswa($query, $mahasiswaId)
{
    return $query->where('mahasiswa_id', $mahasiswaId);
}

// buang data dummy seeder simulasi SMA dari laporan/aktivitas peminjaman asli --
// nama_eksternal nullable, jadi harus whereNull juga (!= gak nangkep NULL di SQL)
public function scopeBukanSimulasi($query)
{
    return $query->where(function ($q) {
        $q->whereNull('nama_eksternal')->orWhere('nama_eksternal', '!=', self::PENANDA_SIMULASI_SMA);
    });
}
```
