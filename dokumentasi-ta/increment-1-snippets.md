# Snippet Kode Penting — Increment 1

## Redirect Dashboard Sesuai Role
**File:** app/Http/Controllers/DashboardRedirectController.php, method index()
```php
public function index()
{
    $user = Auth::user();

    return match ($user->role) {
        'admin_tu'  => redirect()->route('admin.dashboard'),
        'pimpinan'  => redirect()->route('pimpinan.dashboard'),
        'mahasiswa' => redirect()->route('mahasiswa.dashboard'),
        default     => redirect()->route('login'),
    };
}
```

## Simpan Mahasiswa Baru (Auto-create User + Password Default = NIM)
**File:** app/Http/Controllers/MahasiswaController.php, method store()
```php
public function store(Request $request)
{
    $validated = $request->validate([...]);

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
}
```

## Import Mahasiswa dari Excel
**File:** app/Http/Controllers/MahasiswaController.php, method import()
```php
public function import(Request $request)
{
    $request->validate([
        'file_excel' => 'required|mimes:xlsx,xls',
    ]);

    $import = new MahasiswaImport;
    Excel::import($import, $request->file('file_excel'));

    $pesan = "Import selesai: {$import->jumlahBaru} data baru ditambahkan, {$import->jumlahUpdate} data diupdate.";
}
```

## Reset Password Mahasiswa ke Default (NIM)
**File:** app/Http/Controllers/MahasiswaController.php, method resetPassword()
```php
public function resetPassword(Mahasiswa $mahasiswa)
{
    $mahasiswa->user->update([
        'password' => Hash::make($mahasiswa->nim),
    ]);
}
```

## Simpan Aset Kelas Baru (Default Kapasitas)
**File:** app/Http/Controllers/AsetKelasController.php, method store()
```php
public function store(Request $request)
{
    $validated = $request->validate([...]);

    $validated['kapasitas'] = $validated['kapasitas'] ?? 0;

    AsetKelas::create($validated);
}
```

## Simpan Aset Umum Baru (Aturan Khusus Proyektor)
**File:** app/Http/Controllers/AsetUmumController.php, method store()
```php
public function store(Request $request)
{
    $validated = $request->validate([
        'nama_alat'   => 'required|string|max:255',
        'nomor_unit'  => 'nullable|string|max:50',
        'merek'       => 'nullable|string|max:100',
        'kode_aset'   => 'nullable|string|max:50',
        'jumlah_stok' => 'required|integer|min:0',
        // "dipinjam" sengaja gak termasuk -- itu status otomatis ngikutin peminjaman aktif, bukan manual
        'status'      => 'nullable|in:tersedia,rusak,pemeliharaan',
    ]);

    if ($validated['nama_alat'] === 'Proyektor') {
        $validated['batas_jam_maksimal'] = 2000;
    }

    AsetUmum::create($validated);
}
```

## Query Statistik Dashboard Admin
**File:** app/Http/Controllers/AdminDashboardController.php, method index()
```php
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
}
```

## Polling Status & Stok Aset Umum
**File:** app/Http/Controllers/MahasiswaController.php, method hitungStatusAsetUmum() / dashboardAsetUmumData() / tempelPeminjamTerbaru() / asetUmumData()
```php
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
```

## Login — Redirect Selalu ke Dashboard Sesuai Role (Bukan intended())
**File:** app/Http/Controllers/Auth/AuthenticatedSessionController.php, method store()
```php
public function store(LoginRequest $request): RedirectResponse
{
    $request->authenticate();

    $request->session()->regenerate();

    // selalu ke dashboard sesuai role, jangan pakai intended() -- soalnya polling notifikasi
    // di background bisa kesimpen jadi "intended URL" kalau session expired pas lagi polling,
    // efeknya abis login malah diarahin ke endpoint JSON notifikasi bukan ke dashboard.
    return redirect()->route('dashboard');
    
}
```
