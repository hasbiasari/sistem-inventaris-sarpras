<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
    </x-slot>

    <div class="container py-4">

        <div class="dash-tab-switcher mb-4">
            <button type="button" class="dash-tab active" data-tab="aset-umum">
                <i class="bi bi-box-seam"></i> Aset Umum
            </button>
            <button type="button" class="dash-tab" data-tab="aset-kelas">
                <i class="bi bi-building"></i> Aset Kelas
            </button>
            <button type="button" class="dash-tab" data-tab="peminjaman-saya">
                <i class="bi bi-clock-history"></i> Peminjaman Saya
            </button>
        </div>

        {{-- ================= PANEL: ASET UMUM ================= --}}
        <div class="dash-panel" data-panel="aset-umum">
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <div class="stat-tile tile-green">
                        <i class="bi bi-box-seam stat-tile-icon"></i>
                        <div class="stat-tile-value">{{ $totalAsetUmum }}</div>
                        <div class="stat-tile-label">Total Aset Umum</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-tile tile-blue">
                        <i class="bi bi-check-circle stat-tile-icon"></i>
                        <div class="stat-tile-value" id="tile-aset-tersedia">{{ $statusAset['tersedia'] }}</div>
                        <div class="stat-tile-label">Tersedia</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-tile tile-amber">
                        <i class="bi bi-hourglass-split stat-tile-icon"></i>
                        <div class="stat-tile-value" id="tile-aset-dipinjam">{{ $statusAset['dipinjam'] }}</div>
                        <div class="stat-tile-label">Sedang Dipinjam</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-5 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="fw-bold mb-1"><i class="bi bi-pie-chart-fill"></i> Proporsi Status Aset Umum</h6>
                            <div class="small text-muted mb-2">Update otomatis tiap 5 detik &middot; klik bagian grafik untuk lihat daftarnya</div>
                            <div class="chart-box" style="height: 260px;">
                                <canvas id="chartStatusAset"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart-line-fill"></i> Stok Barang Terbanyak</h6>
                            <div class="chart-box" style="height: 260px;">
                                <canvas id="chartStokBarang"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-muted small mb-0">
                <i class="bi bi-info-circle"></i> Buat lihat detail tiap barang (siapa lagi pinjam, dll), buka menu
                <a href="{{ route('mahasiswa.aset-umum') }}">Aset Umum</a>.
            </p>
        </div>

        {{-- ================= PANEL: ASET KELAS ================= --}}
        <div class="dash-panel d-none" data-panel="aset-kelas">
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <div class="stat-tile tile-green">
                        <i class="bi bi-building stat-tile-icon"></i>
                        <div class="stat-tile-value">{{ $totalAsetKelas }}</div>
                        <div class="stat-tile-label">Total Ruangan Kelas</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-tile tile-blue">
                        <i class="bi bi-door-open stat-tile-icon"></i>
                        <div class="stat-tile-value" id="tile-ruangan-kosong">-</div>
                        <div class="stat-tile-label">Kosong Sekarang</div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-tile tile-amber">
                        <i class="bi bi-door-closed stat-tile-icon"></i>
                        <div class="stat-tile-value" id="tile-ruangan-terisi">-</div>
                        <div class="stat-tile-label">Terisi Sekarang</div>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="fw-bold mb-1"><i class="bi bi-pie-chart-fill"></i> Ruangan Kosong vs Terisi</h6>
                            <div class="small text-muted mb-2">Update otomatis tiap 5 detik &middot; klik grafik buat lihat daftarnya</div>
                            <div class="chart-box" style="height: 260px;">
                                <canvas id="chartRuangan"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-muted small mb-0">
                <i class="bi bi-info-circle"></i> Buat lihat detail tiap ruangan (siapa pakai, dari jam berapa), buka menu
                <a href="{{ route('mahasiswa.aset-kelas') }}">Aset Kelas</a>.
            </p>
        </div>

        {{-- ================= PANEL: PEMINJAMAN SAYA ================= --}}
        <div class="dash-panel d-none" data-panel="peminjaman-saya">
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="stat-tile tile-green">
                        <i class="bi bi-journal-text stat-tile-icon"></i>
                        <div class="stat-tile-value">{{ $statistikSaya['total'] }}</div>
                        <div class="stat-tile-label">Total Peminjaman</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <a href="{{ route('peminjaman.riwayat') }}" class="text-decoration-none">
                        <div class="stat-tile tile-amber">
                            <i class="bi bi-hourglass-split stat-tile-icon"></i>
                            <div class="stat-tile-value">{{ $statistikSaya['menunggu'] }}</div>
                            <div class="stat-tile-label">Menunggu</div>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-tile tile-blue">
                        <i class="bi bi-check-circle stat-tile-icon"></i>
                        <div class="stat-tile-value">{{ $statistikSaya['disetujui'] }}</div>
                        <div class="stat-tile-label">Disetujui</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-tile tile-red">
                        <i class="bi bi-x-circle stat-tile-icon"></i>
                        <div class="stat-tile-value">{{ $statistikSaya['ditolak'] }}</div>
                        <div class="stat-tile-label">Ditolak</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-7 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="bi bi-graph-up"></i> Tren Peminjaman Saya (6 Bulan Terakhir)</h6>
                            <div class="chart-box" style="height: 260px;">
                                <canvas id="chartTrenSaya"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="bi bi-trophy-fill"></i> Barang yang Sering Saya Pinjam</h6>
                            <div class="chart-box" style="height: 260px;">
                                <canvas id="chartBarangSaya"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-muted small mb-0">
                <i class="bi bi-info-circle"></i> Buat lihat riwayat lengkap, buka menu
                <a href="{{ route('peminjaman.riwayat') }}">Riwayat Peminjaman</a>.
            </p>
        </div>

    </div>

    {{-- modal drill-down: daftar aset umum per status --}}
    <div class="modal fade" id="modalDrilldownAset" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulDrilldownAset">Daftar Aset Umum</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group" id="isiDrilldownAset"></ul>
                </div>
            </div>
        </div>
    </div>

    {{-- modal drill-down: daftar ruangan kosong/terisi --}}
    <div class="modal fade" id="modalDrilldownRuangan" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="judulDrilldownRuangan">Daftar Ruangan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group" id="isiDrilldownRuangan"></ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script>
      window.addEventListener('DOMContentLoaded', function () {
        // teks Chart.js diputihin biar kebaca di mode gelap
        if (document.body.classList.contains('theme-dark')) {
            Chart.defaults.color = '#e9ecef';
            Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
        }
        // ukuran font chart ngikutin lebar layar -- berlaku di light & dark mode
        Chart.defaults.font.size = window.innerWidth <= 480 ? 9 : (window.innerWidth <= 768 ? 10 : 12);

        // ===== tab switcher =====
        const tabButtons = document.querySelectorAll('.dash-tab');
        const panels = document.querySelectorAll('.dash-panel');
        const chartInitDone = { 'aset-umum': false, 'aset-kelas': false, 'peminjaman-saya': false };

        function pindahTab(namaTab) {
            tabButtons.forEach(btn => btn.classList.toggle('active', btn.dataset.tab === namaTab));
            panels.forEach(panel => panel.classList.toggle('d-none', panel.dataset.panel !== namaTab));

            if (!chartInitDone[namaTab]) {
                chartInitDone[namaTab] = true;
                if (namaTab === 'aset-kelas') initChartAsetKelas();
                if (namaTab === 'peminjaman-saya') initChartPeminjamanSaya();
            }
        }

        tabButtons.forEach(btn => btn.addEventListener('click', () => pindahTab(btn.dataset.tab)));

        // ===== modal drill-down =====
        // diganti tiap polling biar drill-down ikut data terbaru
        let daftarAsetPerStatus = @json($daftarAsetUmumPerStatus);
        const modalDrilldownAset = new bootstrap.Modal(document.getElementById('modalDrilldownAset'));
        const modalDrilldownRuangan = new bootstrap.Modal(document.getElementById('modalDrilldownRuangan'));

        function tampilkanDrilldownAset(status, judul) {
            const daftar = daftarAsetPerStatus[status] ?? [];
            document.getElementById('judulDrilldownAset').textContent = judul;
            const isi = document.getElementById('isiDrilldownAset');
            isi.innerHTML = daftar.length
                ? daftar.map(nama => `<li class="list-group-item">${nama}</li>`).join('')
                : '<li class="list-group-item text-muted">Tidak ada.</li>';
            modalDrilldownAset.show();
        }

        // ===== PANEL: ASET UMUM (dimuat langsung, ini tab default) =====
        const chartStatusAset = new Chart(document.getElementById('chartStatusAset'), {
            type: 'doughnut',
            data: {
                labels: ['Tersedia', 'Dipinjam', 'Rusak', 'Pemeliharaan'],
                datasets: [{
                    data: [
                        {{ $statusAset['tersedia'] }},
                        {{ $statusAset['dipinjam'] }},
                        {{ $statusAset['rusak'] }},
                        {{ $statusAset['pemeliharaan'] }}
                    ],
                    backgroundColor: ['#2e9e6b', '#d8a34a', '#c94a4a', '#6c757d'],
                    borderColor: '#fff',
                    borderWidth: 2,
                }]
            },
            options: {
                cutout: '68%',
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                onClick: (evt, elements, chart) => {
                    if (!elements.length) return;
                    const kunciStatus = ['tersedia', 'dipinjam', 'rusak', 'pemeliharaan'];
                    const idx = elements[0].index;
                    tampilkanDrilldownAset(kunciStatus[idx], 'Aset Umum - ' + chart.data.labels[idx]);
                }
            }
        });

        // polling status aset umum tiap 5 detik biar tile & chart real-time
        function updateStatusAsetUmum() {
            fetch('{{ route('mahasiswa.dashboard.aset-umum-data') }}')
                .then(response => response.json())
                .then(payload => {
                    const s = payload.statusAset;
                    document.getElementById('tile-aset-tersedia').textContent = s.tersedia;
                    document.getElementById('tile-aset-dipinjam').textContent = s.dipinjam;
                    chartStatusAset.data.datasets[0].data = [s.tersedia, s.dipinjam, s.rusak, s.pemeliharaan];
                    chartStatusAset.update();
                    daftarAsetPerStatus = payload.daftarAsetUmumPerStatus;
                })
                .catch(error => console.log('gagal ambil status aset umum:', error));
        }

        setInterval(updateStatusAsetUmum, 1000);

        const labelStok = @json($stokPerBarang->pluck('nama_alat'));
        const dataStok = @json($stokPerBarang->pluck('jumlah_stok'));

        new Chart(document.getElementById('chartStokBarang'), {
            type: 'bar',
            data: {
                labels: labelStok,
                datasets: [{
                    label: 'Stok',
                    data: dataStok,
                    backgroundColor: '#0F6B4C',
                    borderRadius: 4,
                    maxBarThickness: 28,
                }]
            },
            options: {
                indexAxis: 'y',
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });

        // ===== PANEL: ASET KELAS (dimuat begitu tab diklik pertama kali, biar canvas gak ke-render 0x0) =====
        let dataRuanganTerakhir = [];
        let chartRuangan = null;

        function initChartAsetKelas() {
            chartRuangan = new Chart(document.getElementById('chartRuangan'), {
                type: 'doughnut',
                data: {
                    labels: ['Kosong', 'Terisi'],
                    datasets: [{
                        data: [0, 0],
                        backgroundColor: ['#2e9e6b', '#d8a34a'],
                        borderColor: '#fff',
                        borderWidth: 2,
                    }]
                },
                options: {
                    cutout: '68%',
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    onClick: (evt, elements, chart) => {
                        if (!elements.length) return;
                        const idx = elements[0].index;
                        const kosong = dataRuanganTerakhir.filter(r => !r.sedang_dipakai);
                        const terisi = dataRuanganTerakhir.filter(r => r.sedang_dipakai);
                        const daftar = idx === 0 ? kosong : terisi;

                        document.getElementById('judulDrilldownRuangan').textContent = 'Ruangan - ' + chart.data.labels[idx];
                        const isi = document.getElementById('isiDrilldownRuangan');
                        isi.innerHTML = daftar.length
                            ? daftar.map(r => {
                                const detail = r.sedang_dipakai
                                    ? `<div class="small text-muted">${r.dipakai_oleh ?? '-'} (${r.kelas_peminjam ?? '-'}) ${(r.jam_mulai ?? '').substring(0,5)}-${(r.jam_selesai ?? '').substring(0,5)}</div>`
                                    : '';
                                return `<li class="list-group-item">${r.nama_ruangan}${detail}</li>`;
                            }).join('')
                            : '<li class="list-group-item text-muted">Tidak ada.</li>';
                        modalDrilldownRuangan.show();
                    }
                }
            });

            perbaruiTampilanRuangan();
        }

        function perbaruiTampilanRuangan() {
            if (!chartRuangan) return;
            const jumlahTerisi = dataRuanganTerakhir.filter(r => r.sedang_dipakai).length;
            const jumlahKosong = dataRuanganTerakhir.length - jumlahTerisi;
            chartRuangan.data.datasets[0].data = [jumlahKosong, jumlahTerisi];
            chartRuangan.update();
            document.getElementById('tile-ruangan-kosong').textContent = jumlahKosong;
            document.getElementById('tile-ruangan-terisi').textContent = jumlahTerisi;
        }

        function updateStatusRuangan() {
            fetch('{{ route('status-ruangan') }}')
                .then(response => response.json())
                .then(payload => {
                    dataRuanganTerakhir = payload.ruangan;
                    perbaruiTampilanRuangan();
                })
                .catch(error => console.log('gagal ambil status ruangan:', error));
        }

        // isi tile dari awal, chart baru dibikin pas tab diklik
        updateStatusRuangan();
        setInterval(updateStatusRuangan, 1000);

        // ===== PANEL: PEMINJAMAN SAYA (dimuat begitu tab diklik pertama kali) =====
        function initChartPeminjamanSaya() {
            const labelBulan = @json($labelBulanSaya);
            const dataBulan = @json($dataBulanSaya);

            new Chart(document.getElementById('chartTrenSaya'), {
                type: 'bar',
                data: {
                    labels: labelBulan,
                    datasets: [{
                        label: 'Jumlah Peminjaman',
                        data: dataBulan,
                        backgroundColor: '#0F6B4C',
                        borderRadius: 6,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
                    plugins: { legend: { display: false } }
                }
            });

            const labelBarangSaya = @json($rekapBarang->keys());
            const dataBarangSaya = @json($rekapBarang->values());

            if (labelBarangSaya.length > 0) {
                new Chart(document.getElementById('chartBarangSaya'), {
                    type: 'doughnut',
                    data: {
                        labels: labelBarangSaya,
                        datasets: [{
                            data: dataBarangSaya,
                            backgroundColor: ['#0F6B4C', '#2E9E6B', '#7BC29A', '#B7DFC5', '#D8B34A', '#C97B4A'],
                            borderColor: '#fff',
                            borderWidth: 2,
                        }]
                    },
                    options: {
                        cutout: '68%',
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            } else {
                document.getElementById('chartBarangSaya').parentElement.insertAdjacentHTML('beforeend', '<p class="text-muted small mb-0">Belum ada data peminjaman.</p>');
            }
        }
      });
    </script>
</x-app-layout>
