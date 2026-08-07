<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Pemeliharaan Proyektor
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
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <p class="text-muted mb-4">
            Prediksi kapan tiap unit proyektor perlu diservis, dihitung pakai rata-rata pemakaian per minggu
            (Simple Moving Average) dari 4 minggu terakhir. Batas servis = 80% dari jam pakai maksimal.
        </p>

        {{-- ringkasan status --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-accent-card accent-success">
                    <div class="stat-accent-icon accent-success"><i class="bi bi-check-circle-fill"></i></div>
                    <div>
                        <div class="stat-accent-value">{{ $ringkasanStatus['normal'] }}</div>
                        <div class="stat-accent-label">Normal</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-accent-card accent-warning">
                    <div class="stat-accent-icon accent-warning"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <div>
                        <div class="stat-accent-value">{{ $ringkasanStatus['perlu_perhatian'] }}</div>
                        <div class="stat-accent-label">Perlu Perhatian</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-accent-card accent-danger">
                    <div class="stat-accent-icon accent-danger"><i class="bi bi-tools"></i></div>
                    <div>
                        <div class="stat-accent-value">{{ $ringkasanStatus['perlu_pemeliharaan'] }}</div>
                        <div class="stat-accent-label">Perlu Pemeliharaan</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- tren pemakaian mingguan + SMA --}}
        <div class="card card-elevated mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">📈 Tren Pemakaian Proyektor per Minggu (SMA)</h6>
                <div class="chart-box" style="height: 280px;">
                    <canvas id="chartTrenProyektor"></canvas>
                </div>
            </div>
        </div>

        <div class="card card-elevated">
            <div class="card-body">
                    <table class="table table-bordered mb-0" id="tabelProyektor">
                        <thead class="table-light">
                            <tr>
                                <th>Unit</th>
                                <th>Merek</th>
                                <th>Total Jam Pakai</th>
                                <th>Menuju Batas Servis</th>
                                <th>Estimasi Servis</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($daftarProyektor as $proyektor)
                                @php
                                    $persentase = min(100, $proyektor->persentase_menuju_servis ?? 0);
                                    $estimasi = $proyektor->estimasiMingguMenujuServis();
                                @endphp
                                <tr class="{{ $proyektor->kelas_baris }}">
                                    <td>#{{ $proyektor->nomor_unit }}</td>
                                    <td>{{ $proyektor->merek ?? '-' }}</td>
                                    <td>{{ $proyektor->total_jam_pakai }} / {{ $proyektor->batas_jam_maksimal }} jam</td>
                                    <td>
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span>{{ $proyektor->persentase_menuju_servis }}%</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-{{ $proyektor->warna_status }}" style="width: {{ $persentase }}%"></div>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($proyektor->status === 'pemeliharaan')
                                            <span class="text-muted">Sedang diservis</span>
                                        @elseif ($estimasi === null)
                                            <span class="text-muted">Belum cukup data</span>
                                        @elseif ($estimasi <= 0)
                                            <span class="text-danger fw-semibold">Sekarang</span>
                                        @else
                                            ~{{ $estimasi }} minggu lagi
                                        @endif
                                    </td>
                                    <td>
                                        @switch($proyektor->status_pemeliharaan)
                                            @case('dalam_pemeliharaan')
                                                <span class="badge bg-secondary">Dalam Pemeliharaan</span>
                                                @break
                                            @case('perlu_pemeliharaan')
                                                <span class="badge bg-danger">Perlu Pemeliharaan</span>
                                                @break
                                            @case('perlu_perhatian')
                                                <span class="badge bg-warning text-dark">Perlu Perhatian</span>
                                                @break
                                            @default
                                                <span class="badge bg-success">Normal</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @if ($proyektor->status === 'pemeliharaan')
                                                <form method="POST" action="{{ route('admin.pemeliharaan-proyektor.selesai-pemeliharaan', $proyektor->id) }}" class="d-inline form-konfirmasi" data-pesan="Tandai {{ $proyektor->nama_lengkap }} selesai pemeliharaan? Jam pakai akan direset ke 0.">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Selesai Pemeliharaan">
                                                        <i class="bi bi-check-circle-fill"></i><span class="d-none d-md-inline"> Selesai Pemeliharaan</span>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.pemeliharaan-proyektor.set-pemeliharaan', $proyektor->id) }}" class="d-inline form-konfirmasi" data-pesan="Tandai {{ $proyektor->nama_lengkap }} sedang pemeliharaan?">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Set Pemeliharaan">
                                                        <i class="bi bi-tools"></i><span class="d-none d-md-inline"> Set Pemeliharaan</span>
                                                    </button>
                                                </form>
                                            @endif

                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalJamPakai{{ $proyektor->id }}" title="Edit Jam Pakai">
                                                <i class="bi bi-pencil-square"></i><span class="d-none d-md-inline"> Edit Jam Pakai</span>
                                            </button>
                                        </div>

                                        <div class="modal fade" id="modalJamPakai{{ $proyektor->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="{{ route('admin.pemeliharaan-proyektor.update-jam-pakai', $proyektor->id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Jam Pakai &mdash; {{ $proyektor->nama_lengkap }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Total Jam Pakai Sekarang</label>
                                                                <input type="number" step="0.01" min="0" name="total_jam_pakai" class="form-control" value="{{ $proyektor->total_jam_pakai }}" required>
                                                                <small class="text-muted">Isi manual kalau proyektor ini udah lama dipakai sebelum sistem ini ada, atau buat koreksi data.</small>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Batas Jam Maksimal (spesifikasi lampu dari pabrik)</label>
                                                                <input type="number" min="1" name="batas_jam_maksimal" class="form-control" value="{{ $proyektor->batas_jam_maksimal }}" required>
                                                                <small class="text-muted">Beda merek/tipe proyektor bisa beda umur lampu (1500-5000 jam).</small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-primary">Simpan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Belum ada data proyektor.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
            </div>
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <script>
        $(function () {
            $('#tabelProyektor').DataTable({
                responsive: true,
                // Aksi dikunci selalu tampil (isinya ada modal per-baris, jangan sampai ikut
                // kelipet). Kolom lain biar DataTables yang atur otomatis ngikut lebar layar --
                // di layar lebar (PC) otomatis semua kolom kekejar muat & tampil lengkap.
                columnDefs: [
                    { className: 'all', targets: -1 },
                ],
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
                    emptyTable: "Belum ada data proyektor.",
                }
            });
        });

        document.querySelectorAll('.form-konfirmasi').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const pesan = form.dataset.pesan;
                Swal.fire({
                    title: 'Konfirmasi',
                    text: pesan,
                    icon: 'warning',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonColor: '#0F6B4C',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, lanjutkan',
                    cancelButtonText: 'Batal',
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });

        window.addEventListener('DOMContentLoaded', function () {
            const modeGelap = document.body.classList.contains('theme-dark');
            Chart.defaults.color = modeGelap ? '#e9ecef' : '#495057';
            Chart.defaults.borderColor = modeGelap ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            Chart.defaults.font.size = window.innerWidth <= 480 ? 9 : (window.innerWidth <= 768 ? 10 : 12);

            const labelMingguProyektor = @json($trenProyektor['label']);
            const dataJamProyektor = @json($trenProyektor['jam']);
            const dataSmaProyektor = @json($trenProyektor['sma']);
            // legend dikecilin manual khusus HP -- di web tetep ukuran normal (bawaan Chart.js)
            const legendKecil = window.innerWidth <= 576;

            new Chart(document.getElementById('chartTrenProyektor'), {
                data: {
                    labels: labelMingguProyektor,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Jam Pakai per Minggu (Semua Unit)',
                            data: dataJamProyektor,
                            backgroundColor: '#a8d5c2',
                            borderRadius: 6,
                            order: 2,
                        },
                        {
                            type: 'line',
                            label: 'SMA (rata-rata 4 minggu)',
                            data: dataSmaProyektor,
                            borderColor: '#0F6B4C',
                            backgroundColor: '#0F6B4C',
                            tension: 0.3,
                            pointRadius: 3,
                            order: 1,
                        },
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: legendKecil ? { boxWidth: 12, font: { size: 10 } } : {},
                        },
                    },
                }
            });
        });
    </script>
</x-app-layout>
