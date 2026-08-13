<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard Admin TU
        </h2>
    </x-slot>

    <div class="container py-4">

        {{-- ringkasan utama --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <a href="{{ route('admin.mahasiswa') }}" class="text-decoration-none">
                    <div class="card"><div class="card-body stat-card-v2">
                        <div class="stat-icon-circle bg-total">👥</div>
                        <div>
                            <div class="stat-angka">{{ $totalMahasiswa }}</div>
                            <div class="stat-label">Total Mahasiswa</div>
                        </div>
                    </div></div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('admin.aset-kelas') }}" class="text-decoration-none">
                    <div class="card"><div class="card-body stat-card-v2">
                        <div class="stat-icon-circle bg-info">🏫</div>
                        <div>
                            <div class="stat-angka">{{ $totalAsetKelas }}</div>
                            <div class="stat-label">Total Aset Kelas</div>
                        </div>
                    </div></div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('admin.aset-umum') }}" class="text-decoration-none">
                    <div class="card"><div class="card-body stat-card-v2">
                        <div class="stat-icon-circle bg-disetujui">🎒</div>
                        <div>
                            <div class="stat-angka">{{ $totalAsetUmum }}</div>
                            <div class="stat-label">Total Aset Umum</div>
                        </div>
                    </div></div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('admin.peminjaman.laporan', ['filter' => 'organisasi', 'status' => 'menunggu']) }}" class="text-decoration-none">
                    <div class="card border-warning-subtle"><div class="card-body stat-card-v2">
                        <div class="stat-icon-circle bg-menunggu">⏳</div>
                        <div>
                            <div class="stat-angka">{{ $peminjamanMenunggu }}</div>
                            <div class="stat-label">Peminjaman Organisasi Menunggu</div>
                        </div>
                    </div></div>
                </a>
            </div>
        </div>

        {{-- breakdown status aset umum -- diklik langsung ke daftar asetnya yg udah kefilter,
             biar gak usah nyari manual satu-satu kalau jumlahnya udah banyak --}}
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <a href="{{ route('admin.aset-umum', ['status' => 'tersedia']) }}" class="text-decoration-none">
                    <div class="alert alert-success mb-0 text-center py-2">
                        Tersedia: <b>{{ $statusAset['tersedia'] }}</b>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('admin.aset-umum', ['status' => 'dipinjam']) }}" class="text-decoration-none">
                    <div class="alert alert-warning mb-0 text-center py-2">
                        Dipinjam: <b>{{ $statusAset['dipinjam'] }}</b>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('admin.aset-umum', ['status' => 'rusak']) }}" class="text-decoration-none">
                    <div class="alert alert-danger mb-0 text-center py-2">
                        Rusak: <b>{{ $statusAset['rusak'] }}</b>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('admin.pemeliharaan-proyektor') }}" class="text-decoration-none">
                    <div class="alert {{ $jumlahProyektorPerluPemeliharaan > 0 ? 'alert-danger' : 'alert-secondary' }} mb-0 text-center py-2">
                        Perlu Pemeliharaan: <b>{{ $jumlahProyektorPerluPemeliharaan }}</b>
                    </div>
                </a>
            </div>
        </div>

        {{-- perlu tindakan --}}
        <div class="card mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="fw-bold mb-0">📋 Perlu Tindakan &mdash; Pengajuan Peminjaman Organisasi</h6>
                <a href="{{ route('admin.peminjaman.laporan', ['filter' => 'organisasi', 'status' => 'menunggu']) }}" class="btn btn-sm btn-outline-primary">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                @forelse ($daftarMenunggu as $peminjaman)
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                        <div>
                            <div class="fw-semibold">{{ $peminjaman->nama_peminjam }}</div>
                            <div class="small text-muted">
                                @foreach ($peminjaman->details as $detail)
                                    {{ $detail->asetUmum->nama_lengkap ?? '-' }} x{{ $detail->jumlah }}@if (!$loop->last), @endif
                                @endforeach
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted mb-1">{{ $peminjaman->created_at->format('d/m/Y H:i') }}</div>
                            <a href="{{ route('admin.peminjaman.laporan', ['filter' => 'organisasi', 'status' => 'menunggu']) }}" class="btn btn-sm btn-warning">Proses</a>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center mb-0 py-3">Tidak ada pengajuan yang perlu diproses saat ini. 🎉</p>
                @endforelse
            </div>
        </div>

        {{-- booking terbaru: organisasi (ormawa mana aja) + eksternal, biar keliatan semua di sini --}}
        <div class="card mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="fw-bold mb-0">🗂️ Booking Terbaru (Organisasi &amp; Eksternal)</h6>
                <a href="{{ route('admin.peminjaman.laporan') }}" class="btn btn-sm btn-outline-primary">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                    <table class="table table-bordered mb-0" id="tabelBookingOrganisasi">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Peminjam</th>
                                <th>ORMAWA / Eksternal</th>
                                <th>Ruangan</th>
                                <th>Barang</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bookingTerakhir as $booking)
                                @php
                                    $warnaBadgeBooking = match($booking->status) {
                                        'disetujui' => 'success',
                                        'ditolak' => 'danger',
                                        'menunggu' => 'warning',
                                        'dibatalkan' => 'secondary',
                                        'selesai' => 'info',
                                        default => 'dark',
                                    };
                                    $labelStatusBooking = $booking->status === 'selesai' ? 'Dikembalikan' : ucfirst($booking->status);
                                @endphp
                                <tr>
                                    <td>{{ $booking->nama_peminjam }}</td>
                                    <td>
                                        @if ($booking->jenis_peminjam === 'eksternal')
                                            <span class="badge bg-dark">Eksternal</span>
                                        @else
                                            {{ $booking->ormawa ?? '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($booking->asetKelas)
                                            {{ $booking->asetKelas->nama_ruangan }}
                                            <div class="small text-muted">
                                                {{ $booking->rentang_waktu }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($booking->details->isNotEmpty())
                                            <span class="small">{{ $booking->details->map(fn ($d) => ($d->asetUmum->nama_lengkap ?? '-') . ' x' . $d->jumlah)->join(', ') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $booking->created_at->format('d/m/Y') }}</td>
                                    <td><span class="badge bg-{{ $warnaBadgeBooking }}">{{ $labelStatusBooking }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada booking organisasi/eksternal.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
            </div>
        </div>

        {{-- booking terbaru kategori kuliah --}}
        <div class="card mb-4">
            <div class="card-header bg-transparent d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="fw-bold mb-0">🎓 Booking Terbaru (Kuliah)</h6>
                <a href="{{ route('admin.peminjaman.laporan', ['filter' => 'kuliah']) }}" class="btn btn-sm btn-outline-primary">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                    <table class="table table-bordered mb-0" id="tabelBookingKuliah">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Peminjam</th>
                                <th>Kelas</th>
                                <th>Ruangan</th>
                                <th>Barang</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bookingKuliahTerakhir as $booking)
                                @php
                                    $warnaBadgeKuliah = match($booking->status) {
                                        'disetujui' => 'success',
                                        'ditolak' => 'danger',
                                        'menunggu' => 'warning',
                                        'dibatalkan' => 'secondary',
                                        'selesai' => 'info',
                                        default => 'dark',
                                    };
                                    $labelStatusKuliah = $booking->status === 'selesai' ? 'Dikembalikan' : ucfirst($booking->status);
                                @endphp
                                <tr>
                                    <td>{{ $booking->nama_peminjam }}</td>
                                    <td>{{ $booking->kelas ?? '-' }}</td>
                                    <td>
                                        @if ($booking->asetKelas)
                                            {{ $booking->asetKelas->nama_ruangan }}
                                            <div class="small text-muted">
                                                {{ $booking->rentang_waktu }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($booking->details->isNotEmpty())
                                            <span class="small">{{ $booking->details->map(fn ($d) => ($d->asetUmum->nama_lengkap ?? '-') . ' x' . $d->jumlah)->join(', ') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $booking->created_at->format('d/m/Y') }}</td>
                                    <td><span class="badge bg-{{ $warnaBadgeKuliah }}">{{ $labelStatusKuliah }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">Belum ada booking kuliah.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
            </div>
        </div>

        {{-- ringkasan pemeliharaan proyektor, detail lengkapnya di menu Pemeliharaan Proyektor --}}
        <a href="{{ route('admin.pemeliharaan-proyektor') }}" class="text-decoration-none">
            <div class="alert {{ $jumlahProyektorPerluPemeliharaan > 0 ? 'alert-danger' : 'alert-success' }} mb-4 d-flex justify-content-between align-items-center">
                <span>🎥 <strong>Pemeliharaan Proyektor</strong></span>
                <span>
                    @if ($jumlahProyektorPerluPemeliharaan > 0)
                        <b>{{ $jumlahProyektorPerluPemeliharaan }}</b> unit perlu pemeliharaan segera &rarr;
                    @else
                        Semua unit dalam kondisi normal &rarr;
                    @endif
                </span>
            </div>
        </a>

        {{-- grafik --}}
        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">📈 Jumlah Peminjaman per Bulan</h6>
                        <div class="chart-box" style="height: 260px;">
                            <canvas id="chartPerBulan"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">📊 Proporsi Status Aset Umum</h6>
                        <div class="chart-box" style="height: 260px;">
                            <canvas id="chartStatusAset"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script>
        $(function () {
            // di layar lebar (PC) DataTables otomatis nampilin semua kolom lengkap (ruangnya cukup).
            // Cuma di HP kolomnya baru ngelipet otomatis ngikut lebar layar yang beneran tersedia.
            const opsiTabelRingkasan = {
                responsive: true,
                paging: false,
                searching: false,
                info: false,
                ordering: false,
            };
            $('#tabelBookingOrganisasi').DataTable(opsiTabelRingkasan);
            $('#tabelBookingKuliah').DataTable(opsiTabelRingkasan);
        });

        const labelBulan = @json($labelBulan);
        const dataPerBulan = @json($dataPerBulan);

        Chart.defaults.font.size = window.innerWidth <= 480 ? 9 : (window.innerWidth <= 768 ? 10 : 12);

        new Chart(document.getElementById('chartPerBulan'), {
            type: 'bar',
            data: {
                labels: labelBulan,
                datasets: [{
                    label: 'Jumlah Peminjaman',
                    data: dataPerBulan,
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

        new Chart(document.getElementById('chartStatusAset'), {
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
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

    </script>
</x-app-layout>
