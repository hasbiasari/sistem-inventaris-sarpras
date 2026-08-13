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
            <div class="alert alert-danger" id="alert-error-validasi">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
            <script>
                window.addEventListener('DOMContentLoaded', function () {
                    document.getElementById('alert-error-validasi').scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            </script>
        @endif

        <div class="card">
            <div class="card-body">

                <form id="form-peminjaman" action="{{ route('peminjaman.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf

                    <div class="form-peminjaman-grid">
                        <div class="area-fields">
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
                                    <div class="small text-danger d-none pesan-wajib-diisi">Harap isi bidang ini.</div>
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
                                <div class="small text-danger d-none pesan-wajib-diisi">Harap isi bidang ini.</div>
                            </div>

                            <div id="section-nama-kegiatan" class="mb-3 d-none">
                                <label class="form-label">Nama Kegiatan</label>
                                <input type="text" name="nama_kegiatan" id="input-nama-kegiatan" class="form-control" placeholder="contoh: Rapat Kerja HMIF" value="{{ old('nama_kegiatan') }}">
                                <div class="small text-danger d-none pesan-wajib-diisi">Harap isi bidang ini.</div>
                            </div>

                            <div id="section-h7" class="alert alert-warning py-2 px-3 mb-3 small d-none">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                Pengajuan peminjaman acara organisasi wajib diajukan paling lambat <strong>H-7</strong> (7 hari) sebelum tanggal acara.
                            </div>

                            <div id="section-dokumen-izin" class="mb-3 d-none">
                                <label class="form-label">Upload Dokumen Izin (PDF)</label>
                                <input type="file" name="dokumen_izin" id="input-dokumen-izin" class="form-control" accept="application/pdf">
                                <div class="small text-danger d-none pesan-wajib-diisi">Harap isi bidang ini.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold"><i class="bi bi-calendar-week"></i> Tanggal & Jam Peminjaman</label>
                                
                                <div class="row g-2 mb-2">
                                    <div class="col-md-7">
                                        <label class="form-label small text-muted mb-1">Pilih Ruangan</label>
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
                                        <div class="small text-danger d-none pesan-wajib-diisi">Harap isi bidang ini.</div>
                                    </div>
                                </div>
                                <div class="row g-2 mb-2 d-none" id="section-tanggal-selesai">
                                    <div class="col-md-12">
                                        <label class="form-label small text-muted mb-1">Sampai Tanggal <span class="fw-normal"></span></label>
                                        <input type="date" name="tanggal_selesai" id="input-tanggal-selesai" class="form-control"
                                               min="{{ now()->toDateString() }}">
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">Jam Mulai</label>
                                        <input type="time" name="jam_mulai" id="input-jam-mulai" class="form-control" required>
                                        <div class="small text-danger d-none pesan-wajib-diisi">Harap isi bidang ini.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">Jam Selesai</label>
                                        <input type="time" name="jam_selesai" id="input-jam-selesai" class="form-control" required>
                                        <div class="small text-danger d-none pesan-wajib-diisi">Harap isi bidang ini.</div>
                                    </div>
                                </div>
                                <div id="peringatan-ruangan-bentrok" class="alert alert-danger py-2 px-3 mt-2 mb-0 small d-none">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    Ruangan ini sudah dipakai/dipesan pada tanggal & jam segitu. Coba pilih ruangan atau jam lain.
                                </div>
                            </div>
                        </div>

                        <div class="area-cart">
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

                            <button type="submit" id="btn-submit-peminjaman" class="btn btn-success">
                                <i class="bi bi-send-fill"></i> Ajukan Peminjaman
                            </button>
                        </div>

                        <div class="area-pilih">
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
                                            <th width="120">Jumlah</th>
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
                                                    <div class="d-flex align-items-center gap-1">
                                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-kurang-pilih" tabindex="-1">
                                                            <i class="bi bi-dash-lg"></i>
                                                        </button>
                                                        <span class="jumlah-dipilih-display text-center" style="min-width: 22px;">0</span>
                                                        <button type="button" class="btn btn-sm btn-outline-success btn-tambah-pilih">
                                                            <i class="bi bi-plus-lg"></i>
                                                        </button>
                                                    </div>
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

        function getBarisPilihById(id) {
            return document.querySelector('.baris-pilih-barang[data-id="' + id + '"]');
        }

        // stok asli dari server (belum dikurangi barang yang lagi ada di keranjang)
        function getStokAsli(id) {
            const baris = getBarisPilihById(id);
            return baris ? (parseInt(baris.dataset.stok, 10) || 0) : 0;
        }

        function getJumlahDiKeranjang(id) {
            const item = barangDipilih.find(function (b) { return b.id === id; });
            return item ? item.jumlah : 0;
        }

        // sisa stok = stok asli - yang udah dimasukin ke "Barang yang Dipilih"
        function getSisaStok(id) {
            return Math.max(0, getStokAsli(id) - getJumlahDiKeranjang(id));
        }

        const btnSubmitForm = document.getElementById('btn-submit-peminjaman');

        // disable tombol submit selama masih ada input jumlah (di tabel "Barang yang Dipilih") yang melebihi stok
        function updateTombolSubmit() {
            const adaInvalid = document.querySelector('.input-jumlah-keranjang.is-invalid') !== null;
            btnSubmitForm.disabled = adaInvalid;
        }

        // refresh tampilan stok & stepper di kolom "Pilih Barang"
        function updateTampilanPilihBarang() {
            document.querySelectorAll('.baris-pilih-barang').forEach(function (baris) {
                const id = baris.dataset.id;
                const stokAsli = getStokAsli(id);
                const jumlahDipilih = getJumlahDiKeranjang(id);
                const sisa = getSisaStok(id);

                baris.querySelector('.badge-stok').textContent = sisa;
                baris.querySelector('.jumlah-dipilih-display').textContent = jumlahDipilih;
                baris.querySelector('.btn-tambah-pilih').disabled = sisa <= 0;
                baris.querySelector('.btn-kurang-pilih').disabled = jumlahDipilih <= 0;
                // "Penuh di jam ini" cuma kalau stoknya beneran 0 dari sononya, bukan gara-gara kepake keranjang sendiri
                baris.querySelector('.label-barang-penuh').classList.toggle('d-none', stokAsli > 0);
            });

            updateTombolSubmit();
        }

        function renderKeranjangBarang() {
            const tbody = document.getElementById('tabel-keranjang-barang');

            tbody.innerHTML = barangDipilih.length === 0
                ? '<tr><td colspan="3" class="text-center text-muted">Belum ada barang dipilih.</td></tr>'
                : barangDipilih.map(function (b, idx) {
                    const stokAsli = getStokAsli(b.id);
                    return `<tr>
                        <td>${b.nama}</td>
                        <td>
                            <input type="number" class="form-control form-control-sm input-jumlah-keranjang" data-idx="${idx}" min="1" max="${stokAsli}" value="${b.jumlah}">
                            <div class="small text-danger d-none pesan-jumlah-keranjang-melebihi"></div>
                        </td>
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

            // barang di keranjang ngurangin sisa stok yang tampil di kolom "Pilih Barang"
            updateTampilanPilihBarang();
        }

        // klik "+" -> nambah 1 ke keranjang, stok yang ditampilin otomatis berkurang 1
        document.querySelectorAll('.btn-tambah-pilih').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const baris = btn.closest('.baris-pilih-barang');
                const id = baris.dataset.id;
                const nama = baris.dataset.nama;
                const sisa = getSisaStok(id);

                // jaga-jaga kalau tombol kepencet padahal harusnya udah disabled (misal race condition abis refresh stok)
                if (sisa <= 0) return;

                const sudahAda = barangDipilih.find(function (b) { return b.id === id; });
                if (sudahAda) {
                    sudahAda.jumlah += 1;
                } else {
                    barangDipilih.push({ id: id, nama: nama, jumlah: 1 });
                    Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1200,
                        timerProgressBar: true,
                    }).fire({ icon: 'success', title: `${nama} ditambahkan` });
                }

                renderKeranjangBarang();
            });
        });

        // klik "-" -> kurangin 1 dari keranjang, stok yang ditampilin otomatis balik nambah 1; abis di 0 langsung ilang dari keranjang
        document.querySelectorAll('.btn-kurang-pilih').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const baris = btn.closest('.baris-pilih-barang');
                const id = baris.dataset.id;

                const idx = barangDipilih.findIndex(function (b) { return b.id === id; });
                if (idx === -1) return;

                barangDipilih[idx].jumlah -= 1;
                if (barangDipilih[idx].jumlah <= 0) {
                    barangDipilih.splice(idx, 1);
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

        // validasi jumlah manual di tabel "Barang yang Dipilih" -- gak boleh melebihi stok asli
        function validasiInputJumlahKeranjang(input) {
            const idx = parseInt(input.dataset.idx, 10);
            const item = barangDipilih[idx];
            if (!item) return true;

            const stokAsli = getStokAsli(item.id);
            const jumlah = parseInt(input.value, 10) || 0;
            const melebihi = jumlah > stokAsli;
            const pesanError = input.closest('td').querySelector('.pesan-jumlah-keranjang-melebihi');

            pesanError.textContent = melebihi ? `Jumlah melebihi stok tersedia (sisa: ${stokAsli})` : '';
            pesanError.classList.toggle('d-none', !melebihi);
            input.classList.toggle('is-invalid', melebihi);

            updateTombolSubmit();

            return !melebihi && jumlah >= 1;
        }

        document.getElementById('tabel-keranjang-barang').addEventListener('input', function (e) {
            const input = e.target.closest('.input-jumlah-keranjang');
            if (!input) return;
            validasiInputJumlahKeranjang(input);
        });

        document.getElementById('tabel-keranjang-barang').addEventListener('change', function (e) {
            const input = e.target.closest('.input-jumlah-keranjang');
            if (!input) return;

            const idx = parseInt(input.dataset.idx, 10);
            const item = barangDipilih[idx];
            if (!item) return;

            const stokAsli = getStokAsli(item.id);
            let jumlahBaru = parseInt(input.value, 10) || 1;
            if (jumlahBaru > stokAsli) jumlahBaru = stokAsli;
            if (jumlahBaru < 1) jumlahBaru = 1;

            item.jumlah = jumlahBaru;
            renderKeranjangBarang();
        });

        document.getElementById('cari-barang-pilih').addEventListener('input', function () {
            const kataKunci = this.value.trim().toLowerCase();
            document.querySelectorAll('.baris-pilih-barang').forEach(function (baris) {
                baris.style.display = baris.dataset.nama.toLowerCase().includes(kataKunci) ? '' : 'none';
            });
        });

        // kasih tanda merah (border + keterangan "Harap isi bidang ini") di SEMUA bidang wajib yang masih kosong,
        // bukan cuma yang pertama -- ilang otomatis begitu bidangnya udah diisi bener
        function tandaiSemuaBidangKosong(form) {
            const bidangInvalid = Array.from(form.querySelectorAll(':invalid'));

            bidangInvalid.forEach(function (bidang) {
                bidang.classList.add('border-danger');
                const pesan = bidang.nextElementSibling;
                if (pesan && pesan.classList.contains('pesan-wajib-diisi')) {
                    pesan.classList.remove('d-none');
                }

                const bersihkanKalauUdahBener = function () {
                    if (!bidang.checkValidity()) return;
                    bidang.classList.remove('border-danger');
                    if (pesan) pesan.classList.add('d-none');
                    bidang.removeEventListener('input', bersihkanKalauUdahBener);
                    bidang.removeEventListener('change', bersihkanKalauUdahBener);
                };
                bidang.addEventListener('input', bersihkanKalauUdahBener);
                bidang.addEventListener('change', bersihkanKalauUdahBener);
            });

            return bidangInvalid;
        }

        document.getElementById('form-peminjaman').addEventListener('submit', function (e) {
            // bidang wajib (Kelas, ORMAWA, Nama Kegiatan, dokumen izin, dst) yang kelewat keisi
            // -> auto-scroll + fokus ke yang pertama, sekalian tandain semua yang masih kosong
            if (!this.checkValidity()) {
                e.preventDefault();
                const bidangKosong = tandaiSemuaBidangKosong(this);
                if (bidangKosong[0]) {
                    bidangKosong[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    bidangKosong[0].focus({ preventScroll: true });
                }
                return;
            }

            const pakaiRuangan = document.getElementById('input-ruangan').value !== '';
            if (barangDipilih.length === 0 && !pakaiRuangan) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Belum ada yang dipilih', text: 'Pilih minimal ruangan atau barang yang mau dipinjam.' });
                return;
            }
            if (typeof bentrokAktif !== 'undefined' && bentrokAktif) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Ruangan bentrok', text: 'Ruangan ini sudah dipakai/dipesan pada tanggal & jam segitu. Pilih ruangan atau jam lain dulu.' });
                return;
            }
            // jaga-jaga: cegah submit lewat Enter kalau ada jumlah yang masih melebihi stok (tombolnya sendiri udah didisable)
            if (document.querySelector('.input-jumlah-keranjang.is-invalid')) {
                e.preventDefault();
                Swal.fire({ icon: 'error', title: 'Jumlah tidak valid', text: 'Ada jumlah barang yang melebihi stok tersedia. Perbaiki dulu sebelum mengajukan.' });
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
        const sectionNamaKegiatan = document.getElementById('section-nama-kegiatan');
        const inputNamaKegiatan = document.getElementById('input-nama-kegiatan');
        const sectionH7 = document.getElementById('section-h7');
        const sectionTanggalSelesai = document.getElementById('section-tanggal-selesai');
        const inputTanggalSelesai = document.getElementById('input-tanggal-selesai');

        const inputRuangan = document.getElementById('input-ruangan');
        const inputJamMulai = document.getElementById('input-jam-mulai');
        const inputJamSelesai = document.getElementById('input-jam-selesai');

        // cek bentrok ruangan real-time begitu ruangan+tanggal+jam terisi
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

        // refresh sisa stok tiap barang, disable tombol tambah kalau stoknya habis
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
                    // update stok asli dari server, tampilan (sisa dikurangi keranjang) dihitung ulang di updateTampilanPilihBarang()
                    document.querySelectorAll('.baris-pilih-barang').forEach(function (baris) {
                        const id = baris.dataset.id;
                        if (!(id in stokPerBarang)) return;
                        baris.dataset.stok = Math.max(0, stokPerBarang[id]);
                    });
                    updateTampilanPilihBarang();
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
            sectionNamaKegiatan.classList.add('d-none');
            inputNamaKegiatan.required = false;
            sectionH7.classList.add('d-none');
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
            sectionNamaKegiatan.classList.remove('d-none');
            inputNamaKegiatan.required = true;
            sectionH7.classList.remove('d-none');
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
                            @if (!empty($struk['nama_kegiatan']))
                                <tr><th>Kegiatan</th><td>: {{ $struk['nama_kegiatan'] }}</td></tr>
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