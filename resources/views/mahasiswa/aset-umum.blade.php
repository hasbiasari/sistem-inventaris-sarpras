<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Aset Umum
        </h2>
    </x-slot>

    <div class="container py-4">

        <div class="card">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-box-seam"></i> Daftar Aset Umum</h6>
                <span class="small text-muted" id="label-status-aset"><i class="bi bi-arrow-repeat"></i> Update otomatis (sekarang)</span>
            </div>
            <div class="card-body">
                <div class="d-flex gap-2 align-items-end flex-wrap mb-3">
                    <div>
                        <label class="form-label small text-muted mb-1">Cari Aset</label>
                        <input type="text" id="cari-aset" class="form-control form-control-sm" placeholder="contoh: Proyektor">
                    </div>
                    <div>
                        <label class="form-label small text-muted mb-1">Filter Status</label>
                        <select id="filter-status-aset" class="form-select form-select-sm">
                            <option value="">-- Semua --</option>
                            <option value="tersedia">Tersedia (Kosong)</option>
                            <option value="dipinjam">Dipinjam (Terisi)</option>
                            <option value="rusak">Rusak</option>
                            <option value="pemeliharaan">Pemeliharaan</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label small text-muted mb-1">Cek Tanggal Lain</label>
                        <input type="date" id="filter-tanggal-aset" class="form-control form-control-sm">
                    </div>
                    <button type="button" id="btn-cek-aset" class="btn btn-sm btn-primary">
                        <i class="bi bi-search"></i> Cek
                    </button>
                    <button type="button" id="btn-reset-aset" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-repeat"></i> Kembali ke Sekarang
                    </button>
                    <div class="ms-auto">
                        <label class="form-label small text-muted mb-1">Tampilkan</label>
                        <select id="entri-per-halaman" class="form-select form-select-sm">
                            <option value="5" selected>5 data</option>
                            <option value="10">10 data</option>
                            <option value="25">25 data</option>
                            <option value="50">50 data</option>
                        </select>
                    </div>
                </div>

                <small class="text-muted d-block mb-2">
                    <i class="bi bi-info-circle"></i> Kolom Dipinjam Oleh/Kelas/Ruangan/Jam nunjukin jadwal peminjaman yang aktif hari ini (atau di tanggal yang dicek) -- bisa lebih dari satu kalau jamnya beda-beda.
                </small>

                <div class="table-responsive d-none d-md-block">
                    <table class="table table-bordered mb-2">
                        <thead>
                            <tr>
                                <th>Nama Aset</th>
                                <th>Detail</th>
                                <th>Status</th>
                                <th>Dipinjam Oleh</th>
                                <th>Kelas</th>
                                <th>Ruangan</th>
                                <th>Tanggal & Jam</th>
                            </tr>
                        </thead>
                        <tbody id="tabel-aset-umum">
                            <tr><td colspan="7" class="text-center text-muted">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>

                {{-- versi kartu khusus HP: kolomnya sama persis kayak tabel, cuma disusun ke bawah biar gak perlu digeser --}}
                <div class="d-md-none mb-2" id="kartu-aset-umum">
                    <div class="text-center text-muted py-3">Memuat data...</div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="small text-muted" id="info-halaman-aset">Menampilkan 0 data</span>
                    <nav>
                        <ul class="pagination pagination-sm mb-0" id="pagination-aset"></ul>
                    </nav>
                </div>
            </div>
        </div>

    </div>

    <script>
        function warnaBadgeJs(status) {
            return { tersedia: 'success', dipinjam: 'warning', rusak: 'danger', pemeliharaan: 'secondary' }[status] ?? 'dark';
        }

        function capitalize(text) {
            return text.charAt(0).toUpperCase() + text.slice(1);
        }

        let dataAsetTerakhir = @json($semuaAlat);
        let halamanAsetSekarang = 1;

        function renderTabelAset() {
            const kataKunci = document.getElementById('cari-aset').value.trim().toLowerCase();
            const statusDipilih = document.getElementById('filter-status-aset').value;
            const entriPerHalaman = parseInt(document.getElementById('entri-per-halaman').value, 10);
            const tbody = document.getElementById('tabel-aset-umum');
            const kartu = document.getElementById('kartu-aset-umum');

            const dataTersaring = dataAsetTerakhir.filter(function (a) {
                const cocokKataKunci = !kataKunci ||
                    a.nama_alat.toLowerCase().includes(kataKunci) ||
                    (a.nomor_unit ?? '').toLowerCase().includes(kataKunci) ||
                    (a.merek ?? '').toLowerCase().includes(kataKunci) ||
                    (a.kode_aset ?? '').toLowerCase().includes(kataKunci);
                const cocokStatus = !statusDipilih || a.status_efektif === statusDipilih;
                return cocokKataKunci && cocokStatus;
            });

            const totalData = dataTersaring.length;
            const totalHalaman = Math.max(1, Math.ceil(totalData / entriPerHalaman));
            if (halamanAsetSekarang > totalHalaman) halamanAsetSekarang = totalHalaman;
            if (halamanAsetSekarang < 1) halamanAsetSekarang = 1;

            const mulai = (halamanAsetSekarang - 1) * entriPerHalaman;
            const dataHalaman = dataTersaring.slice(mulai, mulai + entriPerHalaman);

            document.getElementById('info-halaman-aset').textContent = totalData === 0
                ? 'Tidak ada data'
                : `Menampilkan ${mulai + 1}-${Math.min(mulai + entriPerHalaman, totalData)} dari ${totalData} data`;
            renderPaginasiAset(totalHalaman, halamanAsetSekarang);

            if (dataHalaman.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Tidak ada aset yang cocok.</td></tr>';
                kartu.innerHTML = '<div class="text-center text-muted py-3">Tidak ada aset yang cocok.</div>';
                return;
            }

            // 1 alat bisa punya beberapa slot jadwal, tiap baris 1 peminjam
            function daftarBaris(jadwal, formatter) {
                return jadwal.length ? jadwal.map(formatter).join('<br>') : '-';
            }

            const barisAset = dataHalaman.map(function (a) {
                const detail = [a.nomor_unit, a.merek, a.kode_aset].filter(Boolean).join(' - ') || '-';
                const jadwal = a.riwayat_peminjam || [];

                const kolomNama = daftarBaris(jadwal, r => r.ormawa ? `${r.nama} (${r.ormawa})` : r.nama);
                const kolomKelas = daftarBaris(jadwal, r => r.kelas || '-');
                const kolomRuangan = daftarBaris(jadwal, r => r.ruangan || '-');
                const kolomTanggalJam = daftarBaris(jadwal, r => r.tanggal_pakai
                    ? `${r.tanggal_pakai} (${r.jam_mulai ?? '-'} - ${r.jam_selesai ?? '-'})`
                    : '-');

                return { a, detail, badge: warnaBadgeJs(a.status_efektif), kolomNama, kolomKelas, kolomRuangan, kolomTanggalJam };
            });

            tbody.innerHTML = barisAset.map(({ a, detail, badge, kolomNama, kolomKelas, kolomRuangan, kolomTanggalJam }) => `<tr>
                    <td>${a.nama_alat}</td>
                    <td>${detail}</td>
                    <td><span class="badge bg-${badge}">${capitalize(a.status_efektif)}</span></td>
                    <td>${kolomNama}</td>
                    <td>${kolomKelas}</td>
                    <td>${kolomRuangan}</td>
                    <td>${kolomTanggalJam}</td>
                </tr>`).join('');

            kartu.innerHTML = barisAset.map(({ a, detail, badge, kolomNama, kolomKelas, kolomRuangan, kolomTanggalJam }) => `
                <div class="card card-elevated mb-2">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="fw-semibold">${a.nama_alat}</div>
                            <span class="badge bg-${badge} flex-shrink-0">${capitalize(a.status_efektif)}</span>
                        </div>
                        <div class="small text-muted mb-2">${detail}</div>
                        <div class="small"><span class="text-muted">Dipinjam Oleh:</span> ${kolomNama}</div>
                        <div class="small"><span class="text-muted">Kelas:</span> ${kolomKelas}</div>
                        <div class="small"><span class="text-muted">Ruangan:</span> ${kolomRuangan}</div>
                        <div class="small"><span class="text-muted">Tanggal & Jam:</span> ${kolomTanggalJam}</div>
                    </div>
                </div>`).join('');
        }

        // render nomor halaman (gaya sama kayak DataTables: Sebelumnya - 1 2 3 - Selanjutnya)
        function renderPaginasiAset(totalHalaman, halamanSekarang) {
            const ul = document.getElementById('pagination-aset');

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

        document.getElementById('pagination-aset').addEventListener('click', function (e) {
            const btn = e.target.closest('.page-link');
            if (!btn || btn.closest('.page-item').classList.contains('disabled')) return;
            halamanAsetSekarang = parseInt(btn.dataset.halaman, 10);
            renderTabelAset();
        });

        document.getElementById('cari-aset').addEventListener('input', function () {
            halamanAsetSekarang = 1;
            renderTabelAset();
        });
        document.getElementById('filter-status-aset').addEventListener('change', function () {
            halamanAsetSekarang = 1;
            renderTabelAset();
        });
        document.getElementById('entri-per-halaman').addEventListener('change', function () {
            halamanAsetSekarang = 1;
            renderTabelAset();
        });

        renderTabelAset();

        // polling data tiap 5 detik, bisa difilter ke tanggal lain
        const inputFilterTanggalAset = document.getElementById('filter-tanggal-aset');
        const labelStatusAset = document.getElementById('label-status-aset');
        let modeRealtimeAset = true;

        function updateStokAlat() {
            let url = '{{ route('mahasiswa.aset-umum.data') }}';

            if (!modeRealtimeAset) {
                const params = new URLSearchParams();
                if (inputFilterTanggalAset.value) params.set('tanggal', inputFilterTanggalAset.value);
                url += '?' + params.toString();
            }

            fetch(url)
                .then(response => response.json())
                .then(payload => {
                    dataAsetTerakhir = payload.aset;

                    if (modeRealtimeAset) {
                        labelStatusAset.innerHTML = '<i class="bi bi-arrow-repeat"></i> Update otomatis (sekarang)';
                    } else {
                        const [tahun, bulan, tgl] = payload.tanggal.split('-');
                        labelStatusAset.innerHTML = `<i class="bi bi-calendar-check"></i> Riwayat pada ${tgl}/${bulan}/${tahun}`;
                    }

                    renderTabelAset();
                })
                .catch(error => console.log('gagal ambil data stok:', error));
        }

        document.getElementById('btn-cek-aset').addEventListener('click', function () {
            modeRealtimeAset = false;
            updateStokAlat();
        });

        document.getElementById('btn-reset-aset').addEventListener('click', function () {
            modeRealtimeAset = true;
            inputFilterTanggalAset.value = '';
            updateStokAlat();
        });

        setInterval(updateStokAlat, 1000);
    </script>
</x-app-layout>
