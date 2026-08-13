<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-bold fs-4 mb-0">Edit Peminjaman</h2>
    </x-slot>

    <div class="container-fluid py-4">

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

                <form id="form-peminjaman" action="{{ route('peminjaman.update', $peminjaman->id) }}" method="POST" enctype="multipart/form-data" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="form-peminjaman-grid">
                        <div class="area-fields">
                            @if ($peminjaman->kategori === 'organisasi')
                                <div class="mb-3">
                                    <label class="form-label">Dokumen Izin Saat Ini</label>
                                    <div>
                                        <a href="{{ asset('storage/' . $peminjaman->dokumen_izin) }}" target="_blank" class="btn btn-sm btn-secondary">
                                            <i class="bi bi-file-earmark-pdf-fill"></i> Lihat Dokumen Lama
                                        </a>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Ganti Dokumen Izin</label>
                                    <input type="file" name="dokumen_izin" class="form-control" accept="application/pdf">
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label">Kelas</label>
                                <input type="text" name="kelas" class="form-control" placeholder="contoh: IF-VIII-A" value="{{ old('kelas', $peminjaman->kelas) }}" required>
                                <div class="small text-danger d-none pesan-wajib-diisi">Harap isi bidang ini.</div>
                            </div>

                            @if ($peminjaman->kategori === 'organisasi')
                                <div class="mb-3">
                                    <label class="form-label">Nama ORMAWA</label>
                                    <input type="text" name="ormawa" class="form-control" placeholder="contoh: HMIF" value="{{ old('ormawa', $peminjaman->ormawa) }}" required>
                                    <div class="small text-danger d-none pesan-wajib-diisi">Harap isi bidang ini.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nama Kegiatan</label>
                                    <input type="text" name="nama_kegiatan" class="form-control" placeholder="contoh: Rapat Kerja HMIF" value="{{ old('nama_kegiatan', $peminjaman->nama_kegiatan) }}" required>
                                    <div class="small text-danger d-none pesan-wajib-diisi">Harap isi bidang ini.</div>
                                </div>
                            @endif

                            <div class="mb-3">
                                <label class="form-label fw-bold"><i class="bi bi-calendar-week"></i> Tanggal & Jam Peminjaman</label>
                                <div class="row g-2 mb-2">
                                    <div class="col-md-7">
                                        <label class="form-label small text-muted mb-1">Pilih Ruangan</label>
                                        <select name="aset_kelas_id" id="input-ruangan" class="form-select">
                                            <option value="">-- Tidak pakai ruangan --</option>
                                            @foreach ($daftarRuangan as $ruangan)
                                                <option value="{{ $ruangan->id }}" @selected($ruangan->id === $peminjaman->aset_kelas_id)>{{ $ruangan->nama_ruangan }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-5" id="section-tanggal-pakai">
                                        <label class="form-label small text-muted mb-1">Tanggal Pakai</label>
                                        <input type="date" name="tanggal_pakai" id="input-tanggal-pakai" class="form-control" required
                                               min="{{ now()->toDateString() }}"
                                               value="{{ $peminjaman->tanggal_pakai?->toDateString() ?? now()->toDateString() }}">
                                        <div class="small text-danger d-none pesan-wajib-diisi">Harap isi bidang ini.</div>
                                    </div>
                                </div>
                                @if ($peminjaman->kategori === 'organisasi')
                                    <div class="row g-2 mb-2">
                                        <div class="col-md-12">
                                            <label class="form-label small text-muted mb-1">Sampai Tanggal <span class="fw-normal"></span></label>
                                            <input type="date" name="tanggal_selesai" id="input-tanggal-selesai" class="form-control"
                                                   min="{{ $peminjaman->tanggal_pakai?->toDateString() ?? now()->toDateString() }}"
                                                   value="{{ $peminjaman->tanggal_selesai?->toDateString() }}">
                                        </div>
                                    </div>
                                @endif
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">Jam Mulai</label>
                                        <input type="time" name="jam_mulai" id="input-jam-mulai" class="form-control" required value="{{ $peminjaman->jam_mulai ? substr($peminjaman->jam_mulai, 0, 5) : '' }}">
                                        <div class="small text-danger d-none pesan-wajib-diisi">Harap isi bidang ini.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted mb-1">Jam Selesai</label>
                                        <input type="time" name="jam_selesai" id="input-jam-selesai" class="form-control" required value="{{ $peminjaman->jam_selesai ? substr($peminjaman->jam_selesai, 0, 5) : '' }}">
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

                            <div>
                                <button type="submit" id="btn-submit-peminjaman" class="btn btn-success">
                                    <i class="bi bi-check-lg"></i> Simpan Perubahan
                                </button>
                                <a href="{{ route('peminjaman.show', $peminjaman->id) }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-lg"></i> Batal
                                </a>
                            </div>
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
        // ===== pilih barang gaya tabel, sama kayak di form Ajukan Peminjaman =====
        let barangDipilih = @json($barangDipilihAwal);

        function getBarisPilihById(id) {
            return document.querySelector('.baris-pilih-barang[data-id="' + id + '"]');
        }

        // stok asli dari server (sudah exclude reservasi peminjaman ini sendiri lewat param "kecuali", belum dikurangi keranjang)
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
            // bidang wajib yang kelewat keisi -> auto-scroll + fokus ke yang pertama, sekalian tandain semua yang masih kosong
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

        renderKeranjangBarang();

        const inputRuangan = document.getElementById('input-ruangan');
        const inputJamMulai = document.getElementById('input-jam-mulai');
        const inputJamSelesai = document.getElementById('input-jam-selesai');

        // cek bentrok ruangan real-time, "kecuali" biar gak bentrok sama diri sendiri
        const peringatanBentrok = document.getElementById('peringatan-ruangan-bentrok');
        let bentrokAktif = false;

        const inputTanggalPakai = document.getElementById('input-tanggal-pakai');
        const inputTanggalSelesai = document.getElementById('input-tanggal-selesai');

        // "Sampai Tanggal" cuma ada di form ini kalau kategorinya organisasi (kuliah tetap sehari)
        if (inputTanggalSelesai) {
            inputTanggalPakai.addEventListener('change', function () {
                inputTanggalSelesai.min = this.value;
                if (inputTanggalSelesai.value && inputTanggalSelesai.value < this.value) {
                    inputTanggalSelesai.value = '';
                }
            });
        }

        function cekRuanganBentrok() {
            const asetKelasId = inputRuangan.value;
            const tanggalPakai = inputTanggalPakai.value;
            const tanggalSelesai = inputTanggalSelesai ? inputTanggalSelesai.value : '';
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
                kecuali: {{ $peminjaman->id }},
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

        [inputRuangan, inputTanggalPakai, inputTanggalSelesai, inputJamMulai, inputJamSelesai].filter(Boolean).forEach(function (el) {
            el.addEventListener('change', cekRuanganBentrok);
        });

        // refresh sisa stok, "kecuali" biar reservasi sendiri gak ikut ngurangin stok
        function refreshStokBarang() {
            const tanggalPakai = inputTanggalPakai.value;
            const jamMulai = inputJamMulai.value;
            const jamSelesai = inputJamSelesai.value;

            if (!tanggalPakai || !jamMulai || !jamSelesai) return;

            const params = new URLSearchParams({
                tanggal_pakai: tanggalPakai,
                jam_mulai: jamMulai,
                jam_selesai: jamSelesai,
                kecuali: {{ $peminjaman->id }},
            });
            if (inputTanggalSelesai && inputTanggalSelesai.value) params.set('tanggal_selesai', inputTanggalSelesai.value);

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

        [inputTanggalPakai, inputTanggalSelesai, inputJamMulai, inputJamSelesai].filter(Boolean).forEach(function (el) {
            el.addEventListener('change', refreshStokBarang);
        });
        refreshStokBarang();
    </script>
</x-app-layout>