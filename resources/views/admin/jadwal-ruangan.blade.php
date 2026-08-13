<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Lihat Jadwal Ruangan
        </h2>
    </x-slot>

    <div class="container-fluid py-4">

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.jadwal-ruangan') }}" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="{{ $tanggal }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-search"></i> Cek
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('admin.jadwal-ruangan') }}" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-repeat"></i> Hari Ini
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h6 class="fw-bold mb-3" id="judulChartStatus">Status Ruangan &mdash; {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}</h6>
                        <div class="d-flex justify-content-center align-items-center flex-grow-1">
                            <div class="chart-box chart-box-square" style="width: 220px; height: 220px; position: relative;">
                                <canvas id="chartStatusRuangan"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Filter Gedung</h6>
                        <div class="btn-group w-100 flex-wrap mb-4" role="group">
                            <button type="button" class="btn btn-outline-success btn-filter-gedung active" data-filter="semua">
                                <i class="bi bi-grid-3x3-gap-fill me-1"></i> Semua <span class="badge bg-success ms-1">{{ $totalRuangan }}</span>
                            </button>
                            @foreach ($ringkasanGedung as $r)
                                <button type="button" class="btn btn-outline-success btn-filter-gedung" data-filter="{{ $r['gedung'] }}">
                                    <i class="bi bi-building me-1"></i> {{ $r['gedung'] }} <span class="badge bg-success ms-1">{{ $r['total'] }}</span>
                                </button>
                            @endforeach
                        </div>

                        <div class="d-flex flex-column gap-3">
                            @foreach ($ringkasanGedung as $r)
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width:34px;height:34px;background:#0F6B4C1a;">
                                        <i class="bi bi-building" style="color:#0F6B4C;"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span class="fw-semibold">{{ $r['gedung'] }}</span>
                                            <span class="text-muted">{{ $r['ada_jadwal'] }} dari {{ $r['total'] }} ruangan ada jadwal</span>
                                        </div>
                                        <div class="progress" style="height:6px;">
                                            <div class="progress-bar" style="width: {{ $r['total'] > 0 ? round($r['ada_jadwal'] / $r['total'] * 100) : 0 }}%; background:#0F6B4C;"></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                    <table class="table table-bordered" id="tabelJadwalRuangan">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Ruangan</th>
                                <th>Gedung</th>
                                <th>Status</th>
                                <th>Jadwal Hari Ini</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($daftarRuanganUrut as $index => $ruangan)
                                <tr data-gedung="{{ $ruangan['gedung'] }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td class="fw-semibold">{{ $ruangan['nama_ruangan'] }}</td>
                                    <td><span class="badge bg-secondary">{{ $ruangan['gedung'] }}</span></td>
                                    <td>
                                        <span class="badge bg-{{ $ruangan['jadwal']->isEmpty() ? 'success' : 'warning' }}">
                                            {{ $ruangan['jadwal']->isEmpty() ? 'Kosong Sepanjang Hari' : $ruangan['jadwal']->count() . ' Jadwal' }}
                                        </span>
                                    </td>
                                    <td>
                                        @forelse ($ruangan['jadwal']->take(2) as $j)
                                            <div class="small">{{ $j['jam_mulai'] }}-{{ $j['jam_selesai'] }} &middot; {{ ucfirst($j['kategori']) }} &middot; {{ $j['nama'] }}</div>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                        @if ($ruangan['jadwal']->count() > 2)
                                            <div class="small text-muted">+{{ $ruangan['jadwal']->count() - 2 }} lainnya</div>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalRuangan{{ $ruangan['id'] }}" title="Lihat Detail">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                    </td>
                                </tr>

                                <div class="modal fade" id="modalRuangan{{ $ruangan['id'] }}" tabindex="-1" data-bs-backdrop="static">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title"><i class="bi bi-calendar-week"></i> {{ $ruangan['nama_ruangan'] }} <span class="opacity-75">&mdash; {{ $ruangan['gedung'] }}</span></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                @forelse ($ruangan['jadwal'] as $j)
                                                    @php
                                                        $warnaKategori = match($j['kategori']) {
                                                            'kuliah' => 'primary',
                                                            'organisasi' => 'info',
                                                            'eksternal' => 'dark',
                                                            default => 'secondary',
                                                        };
                                                        $warnaStatus = match($j['status']) {
                                                            'disetujui' => 'success',
                                                            'ditolak' => 'danger',
                                                            'menunggu' => 'warning',
                                                            'dibatalkan' => 'secondary',
                                                            'selesai' => 'info',
                                                            default => 'dark',
                                                        };
                                                        $labelStatus = $j['status'] === 'selesai' ? 'Dikembalikan' : ucfirst($j['status']);
                                                    @endphp
                                                    <div class="mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <span class="fw-semibold">{{ $j['jam_mulai'] }} - {{ $j['jam_selesai'] }}</span>
                                                            <span class="badge bg-{{ $warnaKategori }}">{{ ucfirst($j['kategori']) }}</span>
                                                        </div>

                                                        <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Nama Peminjam</div><div>: {{ $j['nama'] }}</div></div>
                                                        <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Kategori</div><div>: {{ ucfirst($j['kategori']) }}</div></div>
                                                        <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Kelas</div><div>: {{ $j['kelas'] ?? '-' }}</div></div>
                                                        @if ($j['ormawa'])
                                                            <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">ORMAWA</div><div>: {{ $j['ormawa'] }}</div></div>
                                                        @endif
                                                        @if ($j['nama_kegiatan'])
                                                            <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Nama Kegiatan</div><div>: {{ $j['nama_kegiatan'] }}</div></div>
                                                        @endif
                                                        @if ($j['keterangan_eksternal'])
                                                            <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Keterangan</div><div>: {{ $j['keterangan_eksternal'] }}</div></div>
                                                        @endif
                                                        <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Status</div><div>: <span class="badge bg-{{ $warnaStatus }}">{{ $labelStatus }}</span></div></div>
                                                        <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Waktu Mulai</div><div>: {{ $j['waktu_mulai'] }}</div></div>
                                                        <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Ruangan</div><div>: {{ $ruangan['nama_ruangan'] }}, {{ $j['rentang_tanggal'] }} ({{ $j['jam_mulai'] }}-{{ $j['jam_selesai'] }})</div></div>
                                                        @if ($j['status'] === 'ditolak' && $j['catatan_admin'])
                                                            <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Alasan Ditolak</div><div>: {{ $j['catatan_admin'] }}</div></div>
                                                        @endif
                                                        @if ($j['status'] === 'dibatalkan' && $j['catatan_admin'])
                                                            <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Alasan Dibatalkan</div><div>: {{ $j['catatan_admin'] }}</div></div>
                                                        @endif

                                                        <hr class="my-2">
                                                        <strong>Barang yang dipinjam:</strong>
                                                        <ul class="mb-2">
                                                            @forelse ($j['barang'] as $namaBarang)
                                                                <li>{{ $namaBarang }}</li>
                                                            @empty
                                                                <li class="text-muted">Tidak ada barang, cuma pinjam ruangan.</li>
                                                            @endforelse
                                                        </ul>
                                                        @if ($j['dokumen_izin'])
                                                            <a href="{{ asset('storage/' . $j['dokumen_izin']) }}" target="_blank" class="btn btn-sm btn-secondary mb-2">
                                                                <i class="bi bi-file-earmark-pdf-fill"></i> Lihat Dokumen Izin
                                                            </a>
                                                        @endif
                                                    </div>
                                                @empty
                                                    <p class="text-muted text-center mb-0">Belum ada jadwal buat ruangan ini.</p>
                                                @endforelse
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
            </div>
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script>
        const tanggalLabel = @json(\Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y'));
        const statusPerGedung = @json($ringkasanGedung->mapWithKeys(fn ($r) => [
            $r['gedung'] => ['ada_jadwal' => $r['ada_jadwal'], 'kosong' => $r['total'] - $r['ada_jadwal']],
        ]));
        const statusSemua = { ada_jadwal: {{ $totalAdaJadwal }}, kosong: {{ $totalKosong }} };

        let chartStatus;

        function renderChartStatus(filter) {
            const data = filter === 'semua' ? statusSemua : (statusPerGedung[filter] || { ada_jadwal: 0, kosong: 0 });

            chartStatus.data.datasets[0].data = [data.ada_jadwal, data.kosong];
            chartStatus.update();

            document.getElementById('judulChartStatus').textContent = filter === 'semua'
                ? 'Status Ruangan — ' + tanggalLabel
                : 'Status Ruangan (' + filter + ') — ' + tanggalLabel;
        }

        window.addEventListener('DOMContentLoaded', function () {
            const modeGelap = document.body.classList.contains('theme-dark');
            Chart.defaults.color = modeGelap ? '#e9ecef' : '#495057';
            Chart.defaults.borderColor = modeGelap ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            Chart.defaults.font.size = window.innerWidth <= 480 ? 9 : (window.innerWidth <= 768 ? 10 : 12);

            chartStatus = new Chart(document.getElementById('chartStatusRuangan'), {
                type: 'doughnut',
                data: {
                    labels: ['Ada Jadwal', 'Kosong Sepanjang Hari'],
                    datasets: [{
                        data: [{{ $totalAdaJadwal }}, {{ $totalKosong }}],
                        backgroundColor: ['#DDA23F', '#0F6B4C'],
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
        });

        $(function () {
            const table = $('#tabelJadwalRuangan').DataTable({
                responsive: true,
                // No, Ruangan, Status, Aksi selalu keliatan di HP -- Gedung & Jadwal Hari Ini
                // yang ngelipet duluan kalau kesempitan (bisa dibuka lewat panah expand ▶).
                columnDefs: [{ className: 'all', targets: [0, 1, 3, -1] }],
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    infoEmpty: "Tidak ada data",
                    paginate: { previous: "Sebelumnya", next: "Selanjutnya" },
                    zeroRecords: "Tidak ada data yang cocok",
                    emptyTable: "Belum ada ruangan.",
                }
            });

            let filterAktif = 'semua';
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (filterAktif === 'semua') return true;
                const baris = table.row(dataIndex).node();
                return $(baris).data('gedung') === filterAktif;
            });

            $('.btn-filter-gedung').on('click', function () {
                $('.btn-filter-gedung').removeClass('active');
                $(this).addClass('active');
                filterAktif = $(this).data('filter');
                table.draw();
                renderChartStatus(filterAktif);
            });
        });
    </script>
</x-app-layout>
