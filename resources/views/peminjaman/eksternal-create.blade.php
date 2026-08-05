<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Booking Manual Pihak Eksternal
        </h2>
    </x-slot>

    <div class="container py-4">

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

                <form action="{{ route('admin.booking-eksternal.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Nama Peminjam (Pihak Eksternal)</label>
                        <input type="text" name="nama_eksternal" class="form-control" value="{{ old('nama_eksternal') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan/Instansi</label>
                        <textarea name="keterangan_eksternal" class="form-control" rows="2">{{ old('keterangan_eksternal') }}</textarea>
                    </div>

                    <hr>

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
                        <div class="col-md-5">
                            <label class="form-label small text-muted mb-1">Tanggal Pakai</label>
                            <input type="date" name="tanggal_pakai" id="input-tanggal-pakai" class="form-control" required
                                   min="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
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

                    <hr>

                    <label class="form-label fw-bold">Daftar Barang <span class="fw-normal text-muted small">(opsional kalau cuma pinjam ruangan)</span></label>

                    <div class="row g-2 mb-1 d-none d-md-flex">
                        <div class="col-md-7"><small class="text-muted fw-semibold text-uppercase">Barang</small></div>
                        <div class="col-md-3"><small class="text-muted fw-semibold text-uppercase">Jumlah</small></div>
                        <div class="col-md-2"></div>
                    </div>

                    <div id="daftar-barang">
                        <div class="row g-2 align-items-center mb-2 p-2 border rounded-3 baris-barang">
                            <div class="col-md-7">
                                <select name="barang[0][aset_umum_id]" class="form-select select-barang">
                                    <option value="">-- Pilih Barang --</option>
                                    @foreach ($daftarAlat as $alat)
                                        <option value="{{ $alat->id }}" data-id="{{ $alat->id }}" data-nama="{{ $alat->nama_alat }}{{ $alat->nomor_unit ? " ({$alat->nomor_unit})" : '' }}" data-stok="{{ $alat->jumlah_stok }}">
                                            {{ $alat->nama_alat }}
                                            @if ($alat->nomor_unit) ({{ $alat->nomor_unit }}) @endif
                                            - stok: {{ $alat->jumlah_stok }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="barang[0][jumlah]" class="form-control"
                                       placeholder="Jumlah" min="1" value="1">
                            </div>
                            <div class="col-md-2 text-md-end">
                                <button type="button" class="btn btn-outline-danger btn-hapus-barang" data-bs-toggle="tooltip" title="Hapus baris ini">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="button" id="btn-tambah-barang" class="btn btn-outline-success btn-sm mb-3">
                        + Tambah Barang
                    </button>

                    <div>
                        <button type="submit" class="btn btn-success">Simpan Booking</button>
                    </div>

                </form>
            </div>
        </div>

        <div class="text-center mt-3">
            <a href="{{ route('admin.peminjaman.laporan', ['filter' => 'eksternal']) }}" class="btn btn-outline-success">
                <i class="bi bi-clock-history"></i> Lihat Riwayat Booking Eksternal di Laporan Peminjaman
            </a>
        </div>
    </div>

    <template id="template-baris-barang">
        <div class="row g-2 align-items-center mb-2 p-2 border rounded-3 baris-barang">
            <div class="col-md-7">
                <select name="barang[INDEX][aset_umum_id]" class="form-select select-barang">
                    <option value="">-- Pilih Barang --</option>
                    @foreach ($daftarAlat as $alat)
                        <option value="{{ $alat->id }}" data-id="{{ $alat->id }}" data-nama="{{ $alat->nama_alat }}{{ $alat->nomor_unit ? " ({$alat->nomor_unit})" : '' }}" data-stok="{{ $alat->jumlah_stok }}">
                            {{ $alat->nama_alat }}
                            @if ($alat->nomor_unit) ({{ $alat->nomor_unit }}) @endif
                            - stok: {{ $alat->jumlah_stok }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="number" name="barang[INDEX][jumlah]" class="form-control"
                       placeholder="Jumlah" min="1" value="1">
            </div>
            <div class="col-md-2 text-md-end">
                <button type="button" class="btn btn-outline-danger btn-hapus-barang" data-bs-toggle="tooltip" title="Hapus baris ini">
                    <i class="bi bi-trash-fill"></i>
                </button>
            </div>
        </div>
    </template>

    <script>
        let indexBarang = 1;

        document.getElementById('btn-tambah-barang').addEventListener('click', function () {
            const template = document.getElementById('template-baris-barang').innerHTML;
            const barisBaru = template.replaceAll('INDEX', indexBarang);

            document.getElementById('daftar-barang').insertAdjacentHTML('beforeend', barisBaru);
            indexBarang++;
            refreshStokBarang();
        });

        document.getElementById('daftar-barang').addEventListener('click', function (e) {
            if (e.target.classList.contains('btn-hapus-barang')) {
                const jumlahBaris = document.querySelectorAll('.baris-barang').length;
                if (jumlahBaris > 1) {
                    e.target.closest('.baris-barang').remove();
                }
            }
        });

        const inputRuangan = document.getElementById('input-ruangan');
        const inputTanggalPakai = document.getElementById('input-tanggal-pakai');
        const inputTanggalSelesai = document.getElementById('input-tanggal-selesai');
        const inputJamMulai = document.getElementById('input-jam-mulai');
        const inputJamSelesai = document.getElementById('input-jam-selesai');

        // refresh sisa stok tiap barang berdasarkan tanggal+jam yang lagi dipilih -- opsi barang
        // yang penuh di jam itu (tersedia <= 0) di-disable, biar gak bisa kepilih
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
                    document.querySelectorAll('.select-barang option[data-id]').forEach(function (opsi) {
                        const id = opsi.dataset.id;
                        if (!(id in stokPerBarang)) return;

                        const tersedia = Math.max(0, stokPerBarang[id]);
                        opsi.dataset.stok = tersedia;
                        opsi.textContent = opsi.dataset.nama + ' - stok: ' + tersedia;
                        opsi.disabled = tersedia <= 0 && !opsi.selected;
                    });
                })
                .catch(() => {});
        }

        inputTanggalPakai.addEventListener('change', function () {
            inputTanggalSelesai.min = this.value;
            if (inputTanggalSelesai.value && inputTanggalSelesai.value < this.value) {
                inputTanggalSelesai.value = '';
            }
        });

        // cek bentrok ruangan secara real-time (AJAX), sama kayak di form Ajukan Peminjaman mahasiswa
        const peringatanBentrok = document.getElementById('peringatan-ruangan-bentrok');
        let bentrokAktif = false;

        function cekRuanganBentrok() {
            const asetKelasId = inputRuangan.value;
            const tanggalPakai = inputTanggalPakai.value;
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

        [inputRuangan, inputTanggalPakai, inputTanggalSelesai, inputJamMulai, inputJamSelesai].forEach(function (el) {
            el.addEventListener('change', cekRuanganBentrok);
        });

        [inputTanggalPakai, inputTanggalSelesai, inputJamMulai, inputJamSelesai].forEach(function (el) {
            el.addEventListener('change', refreshStokBarang);
        });

        document.querySelector('form').addEventListener('submit', function (e) {
            const pakaiRuangan = inputRuangan.value !== '';
            const adaBarang = Array.from(document.querySelectorAll('#daftar-barang select'))
                .some(function (select) { return select.value !== ''; });

            if (!pakaiRuangan && !adaBarang) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Belum ada yang dipilih', text: 'Pilih minimal ruangan atau barang yang mau dipinjam.' });
                return;
            }
            if (bentrokAktif) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Ruangan bentrok', text: 'Ruangan ini sudah dipakai/dipesan pada tanggal & jam segitu. Pilih ruangan atau jam lain dulu.' });
            }
        });
    </script>
</x-app-layout>