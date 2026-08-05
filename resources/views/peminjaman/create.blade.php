<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-bold fs-4 mb-0">Ajukan Peminjaman</h2>
    </x-slot>

    <div class="container-fluid py-4">

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <div class="card">
            <div class="card-body">

                <form action="{{ route('peminjaman.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-4">
                        <div class="col-lg-7">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Nama</label>
                                    <input type="text" class="form-control" value="{{ $mahasiswa->nama }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">NIM</label>
                                    <input type="text" class="form-control" value="{{ $mahasiswa->nim }}" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Kelas</label>
                                    <input type="text" name="kelas" class="form-control" placeholder="contoh: IF-VIII-A" value="{{ old('kelas') }}" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label d-block">Kategori Peminjaman</label>

                                <input type="hidden" name="kategori" id="input-kategori" value="kuliah">

                                <button type="button" id="btn-kategori-kuliah" class="btn btn-success">
                                    <i class="bi bi-mortarboard-fill"></i> Keperluan Kuliah
                                </button>
                                <button type="button" id="btn-kategori-organisasi" class="btn btn-outline-secondary">
                                    <i class="bi bi-people-fill"></i> Acara Organisasi
                                </button>
                            </div>

                            <div id="section-ormawa" class="mb-3 d-none">
                                <label class="form-label">Nama ORMAWA</label>
                                <input type="text" name="ormawa" id="input-ormawa" class="form-control" placeholder="contoh: HMIF" value="{{ old('ormawa') }}">
                            </div>

                            <div id="section-dokumen-izin" class="mb-3 d-none">
                                <label class="form-label">Upload Dokumen Izin (PDF)</label>
                                <input type="file" name="dokumen_izin" id="input-dokumen-izin" class="form-control" accept="application/pdf">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold"><i class="bi bi-calendar-week"></i> Tanggal & Jam Peminjaman</label>
                                <small class="text-muted d-block mb-2">Wajib diisi -- dipakai buat ngecek ruangan/barang bentrok apa nggak di jam yang sama.</small>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-7">
                                        <label class="form-label small text-muted mb-1">Pilih Ruangan <span class="fw-normal">(opsional)</span></label>
                                        <select name="aset_kelas_id" id="input-ruangan" class="form-select">
                                            <option value="">-- Tidak pakai ruangan --</option>
                                            @foreach ($daftarRuangan as $ruangan)
                                                <option value="{{ $ruangan->id }}">{{ $ruangan->nama_ruangan }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5" id="section-tanggal-pakai">
                                        <label class="form-label small text-muted mb-1">Tanggal Pakai</label>
                                        <input type="date" name="tanggal_pakai" id="input-tanggal-pakai" class="form-control" required
                                               min="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}">
                                    </div>
                                </div>
                                <div class="row g-2 mb-2 d-none" id="section-tanggal-selesai">
                                    <div class="col-md-12">
                                        <label class="form-label small text-muted mb-1">Sampai Tanggal <span class="fw-normal">(kalau acaranya beberapa hari, opsional)</span></label>
                                        <input type="date" name="tanggal_selesai" id="input-tanggal-selesai" class="form-control"
                                               min="{{ now()->toDateString() }}">
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">Jam Mulai</label>
                                        <input type="time" name="jam_mulai" id="input-jam-mulai" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">Jam Selesai</label>
                                        <input type="time" name="jam_selesai" id="input-jam-selesai" class="form-control" required>
                                    </div>
                                </div>
                                <div id="peringatan-ruangan-bentrok" class="alert alert-danger py-2 px-3 mt-2 mb-0 small d-none">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    Ruangan ini sudah dipakai/dipesan pada tanggal & jam segitu. Coba pilih ruangan atau jam lain.
                                </div>
                            </div>

                            <hr>

                            <label class="form-label fw-bold">Barang yang Dipilih</label>
                            <div class="table-responsive mb-3">
                                <table class="table table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Barang</th>
                                            <th width="100">Jumlah</th>
                                            <th width="60"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabel-keranjang-barang">
                                        <tr><td colspan="3" class="text-center text-muted">Belum ada barang dipilih.</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div id="input-hidden-barang"></div>

                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-send-fill"></i> Ajukan Peminjaman
                            </button>
                        </div>

                        <div class="col-lg-5">
                            <label class="form-label fw-bold">Pilih Barang <span class="fw-normal text-muted small">(yang tersedia)</span></label>
                            <div class="mb-2">
                                <input type="text" id="cari-barang-pilih" class="form-control form-control-sm" placeholder="Cari barang...">
                            </div>

                            <div class="table-responsive border rounded-3" style="max-height: 520px; overflow-y: auto;">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th>Barang</th>
                                            <th width="70">Stok</th>
                                            <th width="90">Jumlah</th>
                                            <th width="50"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($daftarAlat as $alat)
                                            @php
                                                $namaLengkap = $alat->nama_alat . ($alat->nomor_unit ? " ({$alat->nomor_unit})" : '');
                                                $detail = collect([$alat->nomor_unit, $alat->merek, $alat->kode_aset])->filter()->join(' - ');
                                            @endphp
                                            <tr class="baris-pilih-barang" data-id="{{ $alat->id }}" data-nama="{{ $namaLengkap }}" data-stok="{{ $alat->jumlah_stok }}">
                                                <td>
                                                    <div class="fw-semibold">{{ $alat->nama_alat }}</div>
                                                    @if ($detail)
                                                        <div class="small text-muted">{{ $detail }}</div>
                                                    @endif
                                                    <div class="small text-danger d-none label-barang-penuh">Penuh di jam ini</div>
                                                </td>
                                                <td><span class="badge bg-light text-dark border badge-stok">{{ $alat->jumlah_stok }}</span></td>
                                                <td>
                                                    <input type="number" class="form-control form-control-sm input-jumlah-pilih" min="1" max="{{ $alat->jumlah_stok }}" value="1">
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-success btn-tambah-pilih" data-bs-toggle="tooltip" title="Tambah barang ini">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script>
        // ===== pilih barang gaya tabel: klik "+" buat nambahin ke daftar "Barang yang Dipilih" =====
        let barangDipilih = [];

        function renderKeranjangBarang() {
            const tbody = document.getElementById('tabel-keranjang-barang');

            tbody.innerHTML = barangDipilih.length === 0
                ? '<tr><td colspan="3" class="text-center text-muted">Belum ada barang dipilih.</td></tr>'
                : barangDipilih.map(function (b, idx) {
                    return `<tr>
                        <td>${b.nama}</td>
                        <td>${b.jumlah}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-keranjang" data-idx="${idx}">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </td>
                    </tr>`;
                }).join('');

            document.getElementById('input-hidden-barang').innerHTML = barangDipilih.map(function (b, idx) {
                return `<input type="hidden" name="barang[${idx}][aset_umum_id]" value="${b.id}">
                        <input type="hidden" name="barang[${idx}][jumlah]" value="${b.jumlah}">`;
            }).join('');
        }

        document.querySelectorAll('.btn-tambah-pilih').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const baris = btn.closest('.baris-pilih-barang');
                const id = baris.dataset.id;
                const nama = baris.dataset.nama;
                const stok = parseInt(baris.dataset.stok, 10);
                const inputJumlah = baris.querySelector('.input-jumlah-pilih');
                let jumlah = parseInt(inputJumlah.value, 10) || 1;
                if (jumlah > stok) jumlah = stok;
                if (jumlah < 1) jumlah = 1;
                inputJumlah.value = jumlah;

                const sudahAda = barangDipilih.find(function (b) { return b.id === id; });
                if (sudahAda) {
                    sudahAda.jumlah = Math.min(sudahAda.jumlah + jumlah, stok);
                } else {
                    barangDipilih.push({ id: id, nama: nama, jumlah: jumlah, stok: stok });
                }

                renderKeranjangBarang();
            });
        });

        document.getElementById('tabel-keranjang-barang').addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-hapus-keranjang');
            if (!btn) return;
            barangDipilih.splice(parseInt(btn.dataset.idx, 10), 1);
            renderKeranjangBarang();
        });

        document.getElementById('cari-barang-pilih').addEventListener('input', function () {
            const kataKunci = this.value.trim().toLowerCase();
            document.querySelectorAll('.baris-pilih-barang').forEach(function (baris) {
                baris.style.display = baris.dataset.nama.toLowerCase().includes(kataKunci) ? '' : 'none';
            });
        });

        document.querySelector('form').addEventListener('submit', function (e) {
            const pakaiRuangan = document.getElementById('input-ruangan').value !== '';
            if (barangDipilih.length === 0 && !pakaiRuangan) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Belum ada yang dipilih', text: 'Pilih minimal ruangan atau barang yang mau dipinjam.' });
                return;
            }
            if (typeof bentrokAktif !== 'undefined' && bentrokAktif) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Ruangan bentrok', text: 'Ruangan ini sudah dipakai/dipesan pada tanggal & jam segitu. Pilih ruangan atau jam lain dulu.' });
            }
        });

        // toggle kategori antara kuliah dan organisasi
        const btnKuliah = document.getElementById('btn-kategori-kuliah');
        const btnOrganisasi = document.getElementById('btn-kategori-organisasi');
        const inputKategori = document.getElementById('input-kategori');
        const sectionDokumen = document.getElementById('section-dokumen-izin');
        const inputDokumen = document.getElementById('input-dokumen-izin');
        const sectionOrmawa = document.getElementById('section-ormawa');
        const inputOrmawa = document.getElementById('input-ormawa');
        const sectionTanggalSelesai = document.getElementById('section-tanggal-selesai');
        const inputTanggalSelesai = document.getElementById('input-tanggal-selesai');

        const inputRuangan = document.getElementById('input-ruangan');
        const inputJamMulai = document.getElementById('input-jam-mulai');
        const inputJamSelesai = document.getElementById('input-jam-selesai');

        // cek bentrok ruangan secara real-time (AJAX) begitu ruangan+tanggal+jam udah diisi semua,
        // biar mahasiswa tau dari awal bukan pas udah ditolak sesudah submit
        const peringatanBentrok = document.getElementById('peringatan-ruangan-bentrok');
        let bentrokAktif = false;

        function cekRuanganBentrok() {
            const asetKelasId = inputRuangan.value;
            const tanggalPakai = document.getElementById('input-tanggal-pakai').value;
            const tanggalSelesai = inputTanggalSelesai.value;
            const jamMulai = inputJamMulai.value;
            const jamSelesai = inputJamSelesai.value;

            if (!asetKelasId || !tanggalPakai || !jamMulai || !jamSelesai) {
                peringatanBentrok.classList.add('d-none');
                bentrokAktif = false;
                return;
            }

            const params = new URLSearchParams({
                aset_kelas_id: asetKelasId,
                tanggal_pakai: tanggalPakai,
                jam_mulai: jamMulai,
                jam_selesai: jamSelesai,
            });
            if (tanggalSelesai) params.set('tanggal_selesai', tanggalSelesai);

            fetch('{{ route('peminjaman.cek-ruangan-bentrok') }}?' + params.toString())
                .then(res => res.json())
                .then(data => {
                    bentrokAktif = !!data.bentrok;
                    peringatanBentrok.classList.toggle('d-none', !bentrokAktif);
                })
                .catch(() => {
                    peringatanBentrok.classList.add('d-none');
                    bentrokAktif = false;
                });
        }

        [inputRuangan, document.getElementById('input-tanggal-pakai'), inputTanggalSelesai, inputJamMulai, inputJamSelesai].forEach(function (el) {
            el.addEventListener('change', cekRuanganBentrok);
        });

        document.getElementById('input-tanggal-pakai').addEventListener('change', function () {
            inputTanggalSelesai.min = this.value;
            if (inputTanggalSelesai.value && inputTanggalSelesai.value < this.value) {
                inputTanggalSelesai.value = '';
            }
        });

        // refresh sisa stok tiap barang berdasarkan tanggal+jam yang lagi dipilih -- barang yang
        // penuh di jam itu (tersedia <= 0) di-disable tombol tambahnya, biar gak bisa kepilih
        const inputTanggalPakai = document.getElementById('input-tanggal-pakai');

        function refreshStokBarang() {
            const tanggalPakai = inputTanggalPakai.value;
            const jamMulai = inputJamMulai.value;
            const jamSelesai = inputJamSelesai.value;

            if (!tanggalPakai || !jamMulai || !jamSelesai) return;

            const params = new URLSearchParams({
                tanggal_pakai: tanggalPakai,
                jam_mulai: jamMulai,
                jam_selesai: jamSelesai,
            });
            if (inputTanggalSelesai.value) params.set('tanggal_selesai', inputTanggalSelesai.value);

            fetch('{{ route('peminjaman.cek-stok-barang') }}?' + params.toString())
                .then(res => res.json())
                .then(function (stokPerBarang) {
                    document.querySelectorAll('.baris-pilih-barang').forEach(function (baris) {
                        const id = baris.dataset.id;
                        if (!(id in stokPerBarang)) return;

                        const tersedia = Math.max(0, stokPerBarang[id]);
                        baris.dataset.stok = tersedia;
                        baris.querySelector('.badge-stok').textContent = tersedia;

                        const inputJumlah = baris.querySelector('.input-jumlah-pilih');
                        inputJumlah.max = tersedia;
                        if (parseInt(inputJumlah.value, 10) > tersedia) inputJumlah.value = Math.max(1, tersedia);

                        baris.querySelector('.btn-tambah-pilih').disabled = tersedia <= 0;
                        inputJumlah.disabled = tersedia <= 0;
                        baris.querySelector('.label-barang-penuh').classList.toggle('d-none', tersedia > 0);
                    });
                })
                .catch(() => {});
        }

        [inputTanggalPakai, inputTanggalSelesai, inputJamMulai, inputJamSelesai].forEach(function (el) {
            el.addEventListener('change', refreshStokBarang);
        });

        btnKuliah.addEventListener('click', function () {
            inputKategori.value = 'kuliah';

            btnKuliah.classList.add('btn-success');
            btnKuliah.classList.remove('btn-outline-secondary');
            btnOrganisasi.classList.add('btn-outline-secondary');
            btnOrganisasi.classList.remove('btn-success');

            sectionDokumen.classList.add('d-none');
            inputDokumen.required = false;
            sectionOrmawa.classList.add('d-none');
            inputOrmawa.required = false;
            sectionTanggalSelesai.classList.add('d-none');
            inputTanggalSelesai.value = '';
        });

        btnOrganisasi.addEventListener('click', function () {
            inputKategori.value = 'organisasi';

            btnOrganisasi.classList.add('btn-success');
            btnOrganisasi.classList.remove('btn-outline-secondary');
            btnKuliah.classList.add('btn-outline-secondary');
            btnKuliah.classList.remove('btn-success');

            sectionDokumen.classList.remove('d-none');
            inputDokumen.required = true;
            sectionOrmawa.classList.remove('d-none');
            inputOrmawa.required = true;
            sectionTanggalSelesai.classList.remove('d-none');
        });
    </script>

    @if (session('struk'))
        @php $struk = session('struk'); @endphp
        <div class="modal fade" id="modalStrukPeminjaman" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="bi bi-check-circle-fill"></i> Bukti Pengajuan Peminjaman</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small">
                            <i class="bi bi-info-circle"></i> Tunjukkan tampilan ini ke Admin TU sebagai bukti pengajuan peminjaman kamu.
                        </p>
                        @php
                            $warnaStatusStruk = match(strtolower($struk['status'])) {
                                'disetujui' => 'success',
                                'menunggu' => 'warning',
                                default => 'dark',
                            };
                        @endphp
                        <table class="table table-sm table-borderless mb-2">
                            <tr><th width="100">Nama</th><td>: {{ $struk['nama'] }}</td></tr>
                            <tr><th>NIM</th><td>: {{ $struk['nim'] }}</td></tr>
                            <tr><th>Kelas</th><td>: {{ $struk['kelas'] }}</td></tr>
                            @if (!empty($struk['ormawa']))
                                <tr><th>ORMAWA</th><td>: {{ $struk['ormawa'] }}</td></tr>
                            @endif
                            <tr><th>Kategori</th><td>: {{ $struk['kategori'] }}</td></tr>
                            <tr><th>Status</th><td>: <span class="badge bg-{{ $warnaStatusStruk }}">{{ $struk['status'] }}</span></td></tr>
                            <tr><th>Waktu Mulai</th><td>: {{ $struk['waktu_mulai'] }}</td></tr>
                            @if (!empty($struk['ruangan']))
                                <tr><th>Ruangan</th><td>: {{ $struk['ruangan'] }}</td></tr>
                            @endif
                        </table>
                        <hr>
                        <strong>Barang yang dipinjam:</strong>
                        <ul class="mb-0">
                            @forelse ($struk['barang'] as $b)
                                <li>{{ $b }}</li>
                            @empty
                                <li class="text-muted">Tidak ada barang, cuma pinjam ruangan.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            window.addEventListener('DOMContentLoaded', function () {
                new bootstrap.Modal(document.getElementById('modalStrukPeminjaman')).show();
            });
        </script>
    @endif
</x-app-layout>