<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Aset Kelas
        </h2>
    </x-slot>

    <div class="container py-4">

        <div class="card">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-door-open-fill"></i> Status Ruangan Kelas</h6>
                <span class="small text-muted" id="label-status-ruangan"><i class="bi bi-arrow-repeat"></i> Update otomatis (sekarang)</span>
            </div>
            <div class="card-body">
                <div class="d-flex gap-2 align-items-end flex-wrap mb-3">
                    <div>
                        <label class="form-label small text-muted mb-1">Cari Ruangan</label>
                        <input type="text" id="cari-ruangan" class="form-control form-control-sm" placeholder="contoh: B101">
                    </div>
                    <div>
                        <label class="form-label small text-muted mb-1">Filter Status</label>
                        <select id="filter-status-ruangan" class="form-select form-select-sm">
                            <option value="">-- Semua --</option>
                            <option value="kosong">Tersedia (Kosong)</option>
                            <option value="dipakai">Dipakai (Terisi)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small text-muted mb-1">Cek Tanggal Lain</label>
                        <input type="date" id="filter-tanggal-ruangan" class="form-control form-control-sm">
                    </div>
                    <button type="button" id="btn-cek-ruangan" class="btn btn-sm btn-primary">
                        <i class="bi bi-search"></i> Cek
                    </button>
                    <button type="button" id="btn-reset-ruangan" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-repeat"></i> Kembali ke Sekarang
                    </button>
                    <div class="ms-auto">
                        <label class="form-label small text-muted mb-1">Tampilkan</label>
                        <select id="entri-per-halaman-ruangan" class="form-select form-select-sm">
                            <option value="5" selected>5 data</option>
                            <option value="10">10 data</option>
                            <option value="25">25 data</option>
                            <option value="50">50 data</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered mb-2">
                        <thead>
                            <tr>
                                <th>Ruangan</th>
                                <th>Status Sekarang</th>
                                <th>Dipakai Oleh</th>
                                <th>Kelas / ORMAWA</th>
                                <th>Jam</th>
                                <th>Jadwal Hari Itu</th>
                            </tr>
                        </thead>
                        <tbody id="tabel-status-ruangan">
                            <tr><td colspan="6" class="text-center text-muted">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="small text-muted" id="info-halaman-ruangan">Menampilkan 0 data</span>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="pagination-ruangan"></ul>
                    </nav>
                </div>
            </div>
        </div>

    </div>

    <script>
        function jamSingkat(jam) {
            return jam ? jam.substring(0, 5) : '-';
        }

        let dataRuanganTerakhir = [];
        let halamanRuanganSekarang = 1;

        function renderTabelRuangan() {
            const kataKunci = document.getElementById('cari-ruangan').value.trim().toLowerCase();
            const statusDipilih = document.getElementById('filter-status-ruangan').value;
            const entriPerHalaman = parseInt(document.getElementById('entri-per-halaman-ruangan').value, 10);
            const tbody = document.getElementById('tabel-status-ruangan');

            const dataTersaring = dataRuanganTerakhir.filter(function (r) {
                const cocokKataKunci = !kataKunci || r.nama_ruangan.toLowerCase().includes(kataKunci);
                const cocokStatus = !statusDipilih ||
                    (statusDipilih === 'kosong' && !r.sedang_dipakai) ||
                    (statusDipilih === 'dipakai' && r.sedang_dipakai);
                return cocokKataKunci && cocokStatus;
            });

            const totalData = dataTersaring.length;
            const totalHalaman = Math.max(1, Math.ceil(totalData / entriPerHalaman));
            if (halamanRuanganSekarang > totalHalaman) halamanRuanganSekarang = totalHalaman;
            if (halamanRuanganSekarang < 1) halamanRuanganSekarang = 1;

            const mulai = (halamanRuanganSekarang - 1) * entriPerHalaman;
            const dataHalaman = dataTersaring.slice(mulai, mulai + entriPerHalaman);

            document.getElementById('info-halaman-ruangan').textContent = totalData === 0
                ? 'Tidak ada data'
                : `Menampilkan ${mulai + 1}-${Math.min(mulai + entriPerHalaman, totalData)} dari ${totalData} data`;
            renderPaginasiRuangan(totalHalaman, halamanRuanganSekarang);

            if (dataHalaman.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Tidak ada ruangan yang cocok.</td></tr>';
                return;
            }

            tbody.innerHTML = dataHalaman.map(function (r) {
                const jadwal = (r.jadwal_hari_ini && r.jadwal_hari_ini.length)
                    ? r.jadwal_hari_ini.map(formatBarisJadwal).join('')
                    : '<span class="text-muted">Kosong sepanjang hari</span>';

                if (r.sedang_dipakai) {
                    return `<tr>
                        <td>${r.nama_ruangan}</td>
                        <td><span class="badge bg-warning">Dipakai</span></td>
                        <td>${r.dipakai_oleh ?? '-'}</td>
                        <td>${labelKelasOrmawa(r.kategori, r.kelas_peminjam, r.ormawa_peminjam)}</td>
                        <td>${jamSingkat(r.jam_mulai)} - ${jamSingkat(r.jam_selesai)}</td>
                        <td class="small">${jadwal}</td>
                    </tr>`;
                }
                return `<tr>
                    <td>${r.nama_ruangan}</td>
                    <td><span class="badge bg-success">Kosong</span></td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td class="small">${jadwal}</td>
                </tr>`;
            }).join('');
        }

        function capitalize(text) {
            return text ? text.charAt(0).toUpperCase() + text.slice(1) : '-';
        }

        // organisasi -> tampilin ORMAWA (bukan kelas, biar gak rancu), kuliah -> tampilin kelas
        function labelKelasOrmawa(kategori, kelas, ormawa) {
            return kategori === 'organisasi' ? (ormawa ?? '-') : (kelas ?? '-');
        }

        // 1 baris jadwal: jam, kelas/ORMAWA/instansi, peminjamnya, sama barang/proyektor yang
        // ikut dibawa (kalau ada) -- biar bisa cek "mau pakai jam sekian, ada kelas kosong gak,
        // proyektornya dipakai gak" tanpa perlu tau jam pastinya dulu
        function formatBarisJadwal(j) {
            const kelasOrmawa = j.kategori === 'organisasi' ? (j.ormawa ?? '-') : (j.kelas ?? '-');
            const multiHari = j.sampai_tanggal ? ` <span class="badge bg-info-subtle text-info-emphasis">s/d ${j.sampai_tanggal}</span>` : '';
            const barang = j.barang ? `<br><span class="text-muted">Barang: ${j.barang}</span>` : '';
            return `<div class="mb-2 pb-1 border-bottom">
                <span class="fw-semibold">${j.jam_mulai}-${j.jam_selesai}</span>
                &middot; ${kelasOrmawa} &middot; oleh ${j.nama}${multiHari}${barang}
            </div>`;
        }


        // render nomor halaman (gaya sama kayak DataTables: Sebelumnya - 1 2 3 - Selanjutnya)
        function renderPaginasiRuangan(totalHalaman, halamanSekarang) {
            const ul = document.getElementById('pagination-ruangan');

            let html = `<li class="page-item ${halamanSekarang <= 1 ? 'disabled' : ''}">
                <button type="button" class="page-link" data-halaman="${halamanSekarang - 1}"><i class="bi bi-chevron-left"></i></button>
            </li>`;

            for (let i = 1; i <= totalHalaman; i++) {
                html += `<li class="page-item ${i === halamanSekarang ? 'active' : ''}">
                    <button type="button" class="page-link" data-halaman="${i}">${i}</button>
                </li>`;
            }

            html += `<li class="page-item ${halamanSekarang >= totalHalaman ? 'disabled' : ''}">
                <button type="button" class="page-link" data-halaman="${halamanSekarang + 1}"><i class="bi bi-chevron-right"></i></button>
            </li>`;

            ul.innerHTML = html;
        }

        document.getElementById('pagination-ruangan').addEventListener('click', function (e) {
            const btn = e.target.closest('.page-link');
            if (!btn || btn.closest('.page-item').classList.contains('disabled')) return;
            halamanRuanganSekarang = parseInt(btn.dataset.halaman, 10);
            renderTabelRuangan();
        });

        document.getElementById('cari-ruangan').addEventListener('input', function () {
            halamanRuanganSekarang = 1;
            renderTabelRuangan();
        });
        document.getElementById('filter-status-ruangan').addEventListener('change', function () {
            halamanRuanganSekarang = 1;
            renderTabelRuangan();
        });
        document.getElementById('entri-per-halaman-ruangan').addEventListener('change', function () {
            halamanRuanganSekarang = 1;
            renderTabelRuangan();
        });

        // status ruangan kelas (lagi dipakai siapa atau kosong). Defaultnya real-time (sekarang),
        // tapi bisa dicek buat tanggal lain lewat filter di atas tabel.
        const inputFilterTanggalRuangan = document.getElementById('filter-tanggal-ruangan');
        const labelStatusRuangan = document.getElementById('label-status-ruangan');
        let modeRealtimeRuangan = true;

        function updateStatusRuangan() {
            let url = '{{ route('status-ruangan') }}';

            if (!modeRealtimeRuangan) {
                const params = new URLSearchParams();
                if (inputFilterTanggalRuangan.value) params.set('tanggal', inputFilterTanggalRuangan.value);
                url += '?' + params.toString();
            }

            fetch(url)
                .then(response => response.json())
                .then(payload => {
                    dataRuanganTerakhir = payload.ruangan;

                    if (modeRealtimeRuangan) {
                        labelStatusRuangan.innerHTML = '<i class="bi bi-arrow-repeat"></i> Update otomatis (sekarang)';
                    } else {
                        const [tahun, bulan, tgl] = payload.tanggal.split('-');
                        labelStatusRuangan.innerHTML = `<i class="bi bi-calendar-check"></i> Status pada ${tgl}/${bulan}/${tahun} jam ${payload.jam}`;
                    }

                    renderTabelRuangan();
                })
                .catch(error => console.log('gagal ambil status ruangan:', error));
        }

        document.getElementById('btn-cek-ruangan').addEventListener('click', function () {
            modeRealtimeRuangan = false;
            updateStatusRuangan();
        });

        document.getElementById('btn-reset-ruangan').addEventListener('click', function () {
            modeRealtimeRuangan = true;
            inputFilterTanggalRuangan.value = '';
            updateStatusRuangan();
        });

        updateStatusRuangan();
        setInterval(updateStatusRuangan, 5000);
    </script>
</x-app-layout>
