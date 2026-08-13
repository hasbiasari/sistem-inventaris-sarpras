<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard Pimpinan
        </h2>
    </x-slot>

    <div class="container-fluid py-4">

        {{-- ringkasan peminjaman -- diklik langsung ke menu Peminjaman --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4">
                <a href="{{ route('pimpinan.peminjaman') }}" class="text-decoration-none">
                    <div class="stat-tile tile-green">
                        <i class="bi bi-journal-text stat-tile-icon"></i>
                        <div class="stat-tile-value">{{ $totalPeminjaman }}</div>
                        <div class="stat-tile-label">Total Peminjaman</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-4">
                <a href="{{ route('pimpinan.peminjaman', ['status' => 'disetujui']) }}" class="text-decoration-none">
                    <div class="stat-tile tile-blue">
                        <i class="bi bi-hourglass-split stat-tile-icon"></i>
                        <div class="stat-tile-value">{{ $peminjamanAktif }}</div>
                        <div class="stat-tile-label">Peminjaman Aktif</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-4">
                <a href="{{ route('pimpinan.peminjaman', ['status' => 'selesai']) }}" class="text-decoration-none">
                    <div class="stat-tile tile-amber">
                        <i class="bi bi-check2-circle stat-tile-icon"></i>
                        <div class="stat-tile-value">{{ $selesaiBulanIni }}</div>
                        <div class="stat-tile-label">Selesai Bulan Ini</div>
                    </div>
                </a>
            </div>
        </div>

        {{-- grafik peminjaman --}}
        <div class="row mb-4">
            <div class="col-lg-5 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Kuliah vs Organisasi</h6>
                        <div class="chart-box" style="height: 260px;">
                            <canvas id="chartKategoriPeminjaman"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Tren Peminjaman per Bulan</h6>
                        <div class="chart-box" style="height: 260px;">
                            <canvas id="chartTrenPeminjaman"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- peminjaman terakhir --}}
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h6 class="fw-bold mb-0">Peminjaman Terakhir</h6>
                    <a href="{{ route('pimpinan.peminjaman') }}" class="btn btn-sm btn-success">Lihat Semua</a>
                </div>
                    <table class="table table-bordered mb-0" id="tabelPeminjamanTerakhir">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Peminjam</th>
                                <th>Kategori</th>
                                <th>Barang / Ruangan</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($peminjamanTerakhir as $peminjaman)
                                @php
                                    $warnaBadge = match($peminjaman->status) {
                                        'disetujui' => 'success',
                                        'ditolak' => 'danger',
                                        'menunggu' => 'warning',
                                        'dibatalkan' => 'secondary',
                                        'selesai' => 'info',
                                        default => 'dark',
                                    };
                                    $labelStatus = $peminjaman->status === 'selesai' ? 'Dikembalikan' : ucfirst($peminjaman->status);
                                @endphp
                                <tr>
                                    <td>{{ $peminjaman->nama_peminjam }}</td>
                                    <td>{{ $peminjaman->kategori ? ucfirst($peminjaman->kategori) : '-' }}</td>
                                    <td>
                                        @if ($peminjaman->asetKelas)
                                            {{ $peminjaman->asetKelas->nama_ruangan }}
                                        @endif
                                        @if ($peminjaman->details->count())
                                            <div class="small text-muted">
                                                {{ $peminjaman->details->map(fn($d) => ($d->asetUmum->nama_lengkap ?? '-') . ' x' . $d->jumlah)->join(', ') }}
                                            </div>
                                        @endif
                                        @if (! $peminjaman->asetKelas && ! $peminjaman->details->count())
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $peminjaman->created_at->format('d/m/Y') }}</td>
                                    <td><span class="badge bg-{{ $warnaBadge }}">{{ $labelStatus }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Belum ada peminjaman.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
            </div>
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            const modeGelap = document.body.classList.contains('theme-dark');
            Chart.defaults.color = modeGelap ? '#e9ecef' : '#495057';
            Chart.defaults.borderColor = modeGelap ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            Chart.defaults.font.size = window.innerWidth <= 480 ? 9 : (window.innerWidth <= 768 ? 10 : 12);

            const labelBulanTren = @json($labelBulanTren);
            const dataBulanTren = @json($dataBulanTren);

            new Chart(document.getElementById('chartKategoriPeminjaman'), {
                type: 'doughnut',
                data: {
                    labels: ['Kuliah', 'Organisasi'],
                    datasets: [{
                        data: [{{ $jumlahKuliah }}, {{ $jumlahOrganisasi }}],
                        backgroundColor: ['#0F6B4C', '#2E9E6B'],
                        borderColor: '#fff',
                        borderWidth: 2,
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: { legend: { position: 'bottom' } }
                }
            });

            new Chart(document.getElementById('chartTrenPeminjaman'), {
                type: 'bar',
                data: {
                    labels: labelBulanTren,
                    datasets: [{
                        label: 'Jumlah Peminjaman',
                        data: dataBulanTren,
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

        });

        $(function () {
            // di layar lebar kolomnya lengkap semua (ruangnya cukup), cuma di HP yang ngelipet
            // otomatis ngikut lebar layar -- tabel preview ini gak butuh paging/search sendiri.
            $('#tabelPeminjamanTerakhir').DataTable({
                responsive: true,
                paging: false,
                searching: false,
                info: false,
                ordering: false,
            });
        });
    </script>
</x-app-layout>
