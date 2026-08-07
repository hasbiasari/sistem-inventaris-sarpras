<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Semua Peminjaman
        </h2>
    </x-slot>

    <div class="container-fluid py-4">

        <div class="row mb-4">
            <div class="col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3" id="judulChartDistribusi">Distribusi Kategori</h6>
                        <div class="chart-box" style="height: 220px;">
                            <canvas id="chartDistribusiKategori"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 mb-3">
                @php
                    $totalSemua = max($daftarPeminjaman->count(), 1);
                    $rincianFilter = [
                        ['key' => 'kuliah', 'label' => 'Kuliah', 'icon' => 'bi-mortarboard-fill', 'warna' => '#0F6B4C', 'jumlah' => $jumlahKuliah],
                        ['key' => 'organisasi', 'label' => 'Organisasi', 'icon' => 'bi-journal-check', 'warna' => '#2E9E6B', 'jumlah' => $jumlahOrganisasi],
                        ['key' => 'eksternal', 'label' => 'Eksternal', 'icon' => 'bi-building', 'warna' => '#495057', 'jumlah' => $jumlahEksternal],
                    ];
                @endphp
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Filter Kategori</h6>
                        <div class="btn-group w-100 flex-wrap mb-4" role="group">
                            <button type="button" class="btn btn-outline-success btn-filter-kategori active" data-filter="semua">
                                <i class="bi bi-grid-3x3-gap-fill me-1"></i> Semua <span class="badge bg-success ms-1">{{ $daftarPeminjaman->count() }}</span>
                            </button>
                            @foreach ($rincianFilter as $r)
                                <button type="button" class="btn btn-outline-success btn-filter-kategori" data-filter="{{ $r['key'] }}">
                                    <i class="bi {{ $r['icon'] }} me-1"></i> {{ $r['label'] }} <span class="badge bg-success ms-1">{{ $r['jumlah'] }}</span>
                                </button>
                            @endforeach
                        </div>

                        <div class="d-flex flex-column gap-3">
                            @foreach ($rincianFilter as $r)
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width:34px;height:34px;background:{{ $r['warna'] }}1a;">
                                        <i class="bi {{ $r['icon'] }}" style="color:{{ $r['warna'] }};"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span class="fw-semibold">{{ $r['label'] }}</span>
                                            <span class="text-muted">{{ $r['jumlah'] }} peminjaman</span>
                                        </div>
                                        <div class="progress" style="height:6px;">
                                            <div class="progress-bar" style="width: {{ round($r['jumlah'] / $totalSemua * 100) }}%; background:{{ $r['warna'] }};"></div>
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
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h6 class="fw-bold mb-0">Daftar Peminjaman</h6>
                    <a href="{{ route('pimpinan.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
                    </a>
                </div>

                <table class="table table-bordered" id="tabelPeminjaman">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Peminjam</th>
                            <th>Kategori</th>
                            <th>Kelas / Ormawa</th>
                            <th>Ruangan</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daftarPeminjaman as $index => $peminjaman)
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
                                $kategoriFilter = $peminjaman->jenis_peminjam === 'eksternal' ? 'eksternal' : ($peminjaman->kategori ?? 'lainnya');
                            @endphp
                            <tr data-kategori="{{ $kategoriFilter }}">
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $peminjaman->nama_peminjam }}</td>
                                <td>{{ ucfirst($kategoriFilter) }}</td>
                                <td>{{ $peminjaman->kelas ?? $peminjaman->ormawa ?? '-' }}</td>
                                <td>
                                    @if ($peminjaman->asetKelas)
                                        {{ $peminjaman->asetKelas->nama_ruangan }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ $peminjaman->created_at->format('d/m/Y') }}</td>
                                <td><span class="badge bg-{{ $warnaBadge }}">{{ $labelStatus }}</span></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $peminjaman->id }}">
                                        <i class="bi bi-eye-fill"></i>
                                    </button>

                                    <div class="modal fade" id="modalDetail{{ $peminjaman->id }}" tabindex="-1" data-bs-backdrop="static">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-success text-white">
                                                    <h5 class="modal-title"><i class="bi bi-receipt"></i> Detail Peminjaman</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Nama Peminjam</div><div>: {{ $peminjaman->nama_peminjam }}</div></div>
                                                    <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Kategori</div><div>: {{ ucfirst($kategoriFilter) }}</div></div>
                                                    <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Kelas</div><div>: {{ $peminjaman->kelas ?? '-' }}</div></div>
                                                    @if ($peminjaman->ormawa)
                                                        <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">ORMAWA</div><div>: {{ $peminjaman->ormawa }}</div></div>
                                                    @endif
                                                    <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Status</div><div>: <span class="badge bg-{{ $warnaBadge }}">{{ $labelStatus }}</span></div></div>
                                                    <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Waktu Mulai</div><div>: {{ $peminjaman->created_at->format('d/m/Y H:i') }}</div></div>
                                                    @if ($peminjaman->waktu_kembali)
                                                        <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Waktu Kembali</div><div>: {{ $peminjaman->waktu_kembali->format('d/m/Y H:i') }}</div></div>
                                                    @endif
                                                    @if ($peminjaman->asetKelas)
                                                        <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Ruangan</div><div>: {{ $peminjaman->asetKelas->nama_ruangan }}, {{ $peminjaman->rentang_waktu }}</div></div>
                                                    @endif
                                                    @if ($peminjaman->status === 'ditolak' && $peminjaman->catatan_admin)
                                                        <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Alasan Ditolak</div><div>: {{ $peminjaman->catatan_admin }}</div></div>
                                                    @endif
                                                    @if ($peminjaman->status === 'dibatalkan' && $peminjaman->catatan_admin)
                                                        <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Alasan Dibatalkan</div><div>: {{ $peminjaman->catatan_admin }}</div></div>
                                                    @endif
                                                    <hr>
                                                    <strong>Barang yang dipinjam:</strong>
                                                    <ul class="mb-2">
                                                        @forelse ($peminjaman->details as $detail)
                                                            <li>{{ $detail->asetUmum->nama_lengkap ?? '-' }} x{{ $detail->jumlah }}</li>
                                                        @empty
                                                            <li class="text-muted">Tidak ada barang, cuma pinjam ruangan.</li>
                                                        @endforelse
                                                    </ul>
                                                    @if ($peminjaman->buktiPengembalian->count())
                                                        <div>
                                                            <strong>Bukti Foto Pengembalian:</strong>
                                                            <div class="d-flex gap-2 flex-wrap mt-1">
                                                                @foreach ($peminjaman->buktiPengembalian as $bukti)
                                                                    <a href="{{ asset('storage/' . $bukti->foto) }}" target="_blank">
                                                                        <img src="{{ asset('storage/' . $bukti->foto) }}" alt="Bukti pengembalian"
                                                                             style="width: 80px; height: 80px; object-fit: cover;" class="rounded border">
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-success btn-sm" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script>
        const distribusiKategori = {
            kuliah: {{ $jumlahKuliah }},
            organisasi: {{ $jumlahOrganisasi }},
            eksternal: {{ $jumlahEksternal }},
        };
        const statusPerKategori = @json($statusPerKategori);
        const labelStatus = { menunggu: 'Menunggu', disetujui: 'Disetujui', ditolak: 'Ditolak', dibatalkan: 'Dibatalkan', selesai: 'Selesai' };
        const warnaStatus = { menunggu: '#DDA23F', disetujui: '#0F6B4C', ditolak: '#CD5A5A', dibatalkan: '#6C757D', selesai: '#3F8FD1' };

        let chartDistribusi;

        function renderChartDistribusi(filter) {
            let labels, data, warna;

            if (filter === 'semua') {
                labels = ['Kuliah', 'Organisasi', 'Eksternal'];
                data = [distribusiKategori.kuliah, distribusiKategori.organisasi, distribusiKategori.eksternal];
                warna = ['#0F6B4C', '#2E9E6B', '#495057'];
            } else {
                const rincianStatus = statusPerKategori[filter] || {};
                labels = [];
                data = [];
                warna = [];
                Object.keys(labelStatus).forEach(function (kunci) {
                    if (rincianStatus[kunci] > 0) {
                        labels.push(labelStatus[kunci]);
                        data.push(rincianStatus[kunci]);
                        warna.push(warnaStatus[kunci]);
                    }
                });
                if (labels.length === 0) {
                    labels = ['Belum ada data'];
                    data = [1];
                    warna = ['#dee2e6'];
                }
            }

            chartDistribusi.data.labels = labels;
            chartDistribusi.data.datasets[0].data = data;
            chartDistribusi.data.datasets[0].backgroundColor = warna;
            chartDistribusi.update();

            document.getElementById('judulChartDistribusi').textContent = filter === 'semua'
                ? 'Distribusi Kategori'
                : 'Distribusi Status - ' + filter.charAt(0).toUpperCase() + filter.slice(1);
        }

        window.addEventListener('DOMContentLoaded', function () {
            const modeGelap = document.body.classList.contains('theme-dark');
            Chart.defaults.color = modeGelap ? '#e9ecef' : '#495057';
            Chart.defaults.borderColor = modeGelap ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            Chart.defaults.font.size = window.innerWidth <= 480 ? 9 : (window.innerWidth <= 768 ? 10 : 12);

            chartDistribusi = new Chart(document.getElementById('chartDistribusiKategori'), {
                type: 'doughnut',
                data: {
                    labels: ['Kuliah', 'Organisasi', 'Eksternal'],
                    datasets: [{
                        data: [distribusiKategori.kuliah, distribusiKategori.organisasi, distribusiKategori.eksternal],
                        backgroundColor: ['#0F6B4C', '#2E9E6B', '#495057'],
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
            const table = $('#tabelPeminjaman').DataTable({
                responsive: true,
                columnDefs: [{ className: 'all', targets: -1 }],
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
                    emptyTable: "Belum ada peminjaman.",
                }
            });

            let filterAktif = 'semua';
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (filterAktif === 'semua') return true;
                const baris = table.row(dataIndex).node();
                return $(baris).data('kategori') === filterAktif;
            });

            $('.btn-filter-kategori').on('click', function () {
                $('.btn-filter-kategori').removeClass('active');
                $(this).addClass('active');
                filterAktif = $(this).data('filter');
                table.draw();
                renderChartDistribusi(filterAktif);
            });
        });
    </script>
</x-app-layout>
