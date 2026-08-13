<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Riwayat Peminjaman Saya
        </h2>
    </x-slot>

    <div class="container py-4">

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <a href="{{ route('peminjaman.riwayat') }}" class="text-decoration-none">
                    <div class="card"><div class="card-body stat-card-v2">
                        <div class="stat-icon-circle bg-total">📦</div>
                        <div>
                            <div class="stat-angka">{{ $statistik['total'] }}</div>
                            <div class="stat-label">Total Peminjaman</div>
                        </div>
                    </div></div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('peminjaman.riwayat', ['status' => 'menunggu']) }}" class="text-decoration-none">
                    <div class="card"><div class="card-body stat-card-v2">
                        <div class="stat-icon-circle bg-menunggu">⏳</div>
                        <div>
                            <div class="stat-angka">{{ $statistik['menunggu'] }}</div>
                            <div class="stat-label">Menunggu</div>
                        </div>
                    </div></div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('peminjaman.riwayat', ['status' => 'disetujui']) }}" class="text-decoration-none">
                    <div class="card"><div class="card-body stat-card-v2">
                        <div class="stat-icon-circle bg-disetujui">✅</div>
                        <div>
                            <div class="stat-angka">{{ $statistik['disetujui'] }}</div>
                            <div class="stat-label">Disetujui</div>
                        </div>
                    </div></div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="{{ route('peminjaman.riwayat', ['status' => 'ditolak']) }}" class="text-decoration-none">
                    <div class="card"><div class="card-body stat-card-v2">
                        <div class="stat-icon-circle bg-ditolak">❌</div>
                        <div>
                            <div class="stat-angka">{{ $statistik['ditolak'] }}</div>
                            <div class="stat-label">Ditolak</div>
                        </div>
                    </div></div>
                </a>
            </div>
        </div>

        <div class="row mb-4">
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
                        <h6 class="fw-bold mb-3">🏆 Barang Paling Sering Dipinjam</h6>
                        <div class="chart-box" style="height: 260px;">
                            <canvas id="chartBarang"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h6 class="fw-bold mb-0">Daftar Riwayat</h6>
                </div>

                <div class="alert alert-info d-none d-flex justify-content-between align-items-center" id="alertFilterStatusRiwayat">
                    <span id="pesanFilterStatusRiwayat"></span>
                    <a href="{{ route('peminjaman.riwayat') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Reset Filter
                    </a>
                </div>

                <table class="table table-bordered" id="tabelRiwayat">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Barang (Multiple)</th>
                            <th>Ruangan</th>
                            <th>Kategori</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daftarPeminjaman as $index => $peminjaman)
                            @php
                                $namaBarangGabungan = $peminjaman->details->map(function ($d) {
                                    $alat = $d->asetUmum;
                                    return ($alat->nama_alat ?? '-') . ($alat->nomor_unit ? " ({$alat->nomor_unit})" : '');
                                })->join(', ');
                            @endphp
                            <tr data-status="{{ $peminjaman->status }}">
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $namaBarangGabungan }}</td>
                                <td>
                                    @if ($peminjaman->asetKelas)
                                        {{ $peminjaman->asetKelas->nama_ruangan }}
                                        <div class="small text-muted">{{ $peminjaman->rentang_waktu }}</div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{ ucfirst($peminjaman->kategori ?? '-') }}</td>
                                <td>{{ $peminjaman->created_at->format('d/m/Y') }}</td>
                                <td>
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
                                    <span class="badge bg-{{ $warnaBadge }}">{{ $labelStatus }}</span>
                                </td>
                                <td>
                                    @php
                                        $bisaDiedit = $peminjaman->status === 'menunggu'
                                            || ($peminjaman->kategori === 'kuliah' && $peminjaman->status === 'disetujui');
                                    @endphp
                                    <div class="d-flex gap-1">
                                        @if ($bisaDiedit)
                                            <a href="{{ route('peminjaman.edit', $peminjaman->id) }}" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Edit Peminjaman">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                        @endif

                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalDetail{{ $peminjaman->id }}" title="Lihat Detail">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>

                                        @if ($peminjaman->status === 'disetujui')
                                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalKembalikan{{ $peminjaman->id }}" title="Kembalikan Barang">
                                                <i class="bi bi-box-arrow-in-left"></i>
                                            </button>
                                        @endif
                                    </div>

                                    {{-- popup detail peminjaman --}}
                                    <div class="modal fade" id="modalDetail{{ $peminjaman->id }}" tabindex="-1" data-bs-backdrop="static">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header bg-success text-white">
                                                    <h5 class="modal-title"><i class="bi bi-receipt"></i> Detail Peminjaman</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Nama</div><div>: {{ $mahasiswa->nama }}</div></div>
                                                    <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">NIM</div><div>: {{ $mahasiswa->nim }}</div></div>
                                                    <div class="d-flex mb-1"><div class="fw-semibold" style="width:130px;">Kategori</div><div>: {{ ucfirst($peminjaman->kategori ?? '-') }}</div></div>
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
                                                    <hr>
                                                    <strong>Barang yang dipinjam:</strong>
                                                    <ul class="mb-2">
                                                        @foreach ($peminjaman->details as $detail)
                                                            <li>{{ $detail->asetUmum->nama_alat ?? '-' }}{{ $detail->asetUmum->nomor_unit ? " ({$detail->asetUmum->nomor_unit})" : '' }} x{{ $detail->jumlah }}</li>
                                                        @endforeach
                                                    </ul>
                                                    @if ($peminjaman->dokumen_izin)
                                                        <a href="{{ asset('storage/' . $peminjaman->dokumen_izin) }}" target="_blank" class="btn btn-sm btn-secondary mb-2">
                                                            <i class="bi bi-file-earmark-pdf-fill"></i> Lihat Dokumen Izin
                                                        </a>
                                                    @endif
                                                    @if ($peminjaman->buktiPengembalian->count())
                                                        <div>
                                                            <strong>Bukti Foto Pengembalian:</strong>
                                                            <div class="galeri-bukti-foto mt-1">
                                                                @foreach ($peminjaman->buktiPengembalian as $bukti)
                                                                    <a href="{{ asset('storage/' . $bukti->foto) }}" target="_blank" class="galeri-bukti-foto-item">
                                                                        <img src="{{ asset('storage/' . $bukti->foto) }}" alt="Bukti pengembalian">
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer">
                                                    <a href="{{ route('peminjaman.show', $peminjaman->id) }}" class="btn btn-outline-secondary btn-sm">Buka Halaman Detail</a>
                                                    <button type="button" class="btn btn-success btn-sm" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- popup kembalikan barang --}}
                                    @if ($peminjaman->status === 'disetujui')
                                        <div class="modal fade" id="modalKembalikan{{ $peminjaman->id }}" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <form action="{{ route('peminjaman.kembalikan', $peminjaman->id) }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-success text-white">
                                                            <h5 class="modal-title"><i class="bi bi-box-arrow-in-left"></i> Kembalikan Barang</h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="text-muted small mb-3">Upload foto barang yang dikembalikan sebagai bukti. Boleh lebih dari satu foto, atau seret &amp; lepas foto ke kotak di bawah.</p>
                                                            <div class="upload-foto-wrapper">
                                                                <div class="upload-foto-zone">
                                                                    <button type="button" class="btn-tambah-foto">
                                                                        <i class="bi bi-camera-fill"></i>
                                                                        <span>Tambah Foto</span>
                                                                    </button>
                                                                </div>
                                                                <input type="file" name="foto[]" accept="image/*" multiple class="visually-hidden">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-success">
                                                                <i class="bi bi-check-lg"></i> Konfirmasi Pengembalian
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
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
        const labelBulan = @json($labelBulan);
        const dataPerBulan = @json($dataPerBulan);
        const labelBarang = @json($labelBarang);
        const dataBarang = @json($dataBarang);

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

        if (labelBarang.length > 0) {
            new Chart(document.getElementById('chartBarang'), {
                type: 'doughnut',
                data: {
                    labels: labelBarang,
                    datasets: [{
                        data: dataBarang,
                        backgroundColor: ['#0F6B4C', '#2E9E6B', '#7BC29A', '#B7DFC5', '#D8B34A', '#C97B4A', '#8A5A44'],
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        } else {
            document.getElementById('chartBarang').parentElement.insertAdjacentHTML('beforeend', '<p class="text-muted small mb-0">Belum ada data barang.</p>');
        }

        $(function () {
            const table = $('#tabelRiwayat').DataTable({
                responsive: true,
                columnDefs: [{ className: 'all', targets: -1 }],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    paginate: { previous: "<i class=\"bi bi-chevron-left\"></i>", next: "<i class=\"bi bi-chevron-right\"></i>" },
                    zeroRecords: "Tidak ada data yang cocok",
                    emptyTable: "Belum ada riwayat peminjaman.",
                }
            });

            // datang dari kartu ringkasan di Dashboard (misal ?status=ditolak), tabel langsung kefilter
            // biar gak nyampur -- gak perlu nyari manual satu-satu
            const filterStatusRiwayat = new URLSearchParams(window.location.search).get('status');
            const labelFilterStatusRiwayat = { menunggu: 'Menunggu', disetujui: 'Disetujui', ditolak: 'Ditolak', dibatalkan: 'Dibatalkan', selesai: 'Selesai / Dikembalikan' };
            if (filterStatusRiwayat && labelFilterStatusRiwayat[filterStatusRiwayat]) {
                $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                    return $(table.row(dataIndex).node()).data('status') === filterStatusRiwayat;
                });
                $('#alertFilterStatusRiwayat').removeClass('d-none');
                $('#pesanFilterStatusRiwayat').text('Menampilkan hanya peminjaman berstatus: ' + labelFilterStatusRiwayat[filterStatusRiwayat]);
                table.draw();
            }
        });

        // widget upload foto: klik tombol "+" buat nambahin foto, preview thumbnail, bisa dihapus satu-satu
        function initUploadFoto(wrapper) {
            const zone = wrapper.querySelector('.upload-foto-zone');
            const btnTambah = wrapper.querySelector('.btn-tambah-foto');
            const input = wrapper.querySelector('input[type=file]');
            const form = wrapper.closest('form');
            let files = [];

            function render() {
                zone.querySelectorAll('.foto-thumb').forEach(el => el.remove());
                files.forEach((file, idx) => {
                    const url = URL.createObjectURL(file);
                    const thumb = document.createElement('div');
                    thumb.className = 'foto-thumb position-relative rounded border overflow-hidden';
                    thumb.innerHTML = `
                        <img src="${url}" alt="Pratinjau foto">
                        <button type="button" class="btn-hapus-foto btn btn-sm btn-danger rounded-circle position-absolute top-0 end-0 p-0 d-flex align-items-center justify-content-center"
                                style="width:20px;height:20px;transform:translate(30%,-30%);" data-idx="${idx}">&times;</button>
                    `;
                    zone.insertBefore(thumb, btnTambah);
                });

                const dt = new DataTransfer();
                files.forEach(f => dt.items.add(f));
                input.files = dt.files;
            }

            function tambahFile(fileList) {
                const gambar = Array.from(fileList).filter(f => f.type.startsWith('image/'));
                files = files.concat(gambar);
                render();
            }

            btnTambah.addEventListener('click', () => input.click());

            input.addEventListener('change', function () {
                tambahFile(input.files);
            });

            zone.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-hapus-foto');
                if (!btn) return;
                files.splice(parseInt(btn.dataset.idx, 10), 1);
                render();
            });

            // seret & lepas foto langsung ke kotaknya
            ['dragenter', 'dragover'].forEach(function (nama) {
                zone.addEventListener(nama, function (e) {
                    e.preventDefault();
                    zone.classList.add('drag-over');
                });
            });
            ['dragleave', 'drop'].forEach(function (nama) {
                zone.addEventListener(nama, function (e) {
                    e.preventDefault();
                    zone.classList.remove('drag-over');
                });
            });
            zone.addEventListener('drop', function (e) {
                if (e.dataTransfer?.files?.length) {
                    tambahFile(e.dataTransfer.files);
                }
            });

            if (form) {
                form.addEventListener('submit', function (e) {
                    if (files.length === 0) {
                        e.preventDefault();
                        Swal.fire({ icon: 'warning', title: 'Belum ada foto', text: 'Upload minimal 1 foto bukti pengembalian.' });
                    }
                });
            }
        }

        document.querySelectorAll('.upload-foto-wrapper').forEach(initUploadFoto);
    </script>

    <style>
        .galeri-bukti-foto {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 0.5rem;
            max-width: 420px;
        }

        .galeri-bukti-foto-item {
            display: block;
            aspect-ratio: 1 / 1;
            border-radius: 0.6rem;
            overflow: hidden;
            border: 1px solid #dee2e6;
            transition: transform .15s ease, box-shadow .15s ease;
        }

        .galeri-bukti-foto-item:hover {
            transform: scale(1.03);
            box-shadow: 0 4px 12px rgba(0, 0, 0, .12);
        }

        .galeri-bukti-foto-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .upload-foto-zone {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding: 1rem;
            border: 2px dashed #ced4da;
            border-radius: 0.75rem;
            background: #f8f9fa;
            transition: border-color .15s ease, background-color .15s ease;
        }

        .upload-foto-zone.drag-over {
            border-color: #0F6B4C;
            background: #eaf6f0;
        }

        .btn-tambah-foto {
            width: 90px;
            height: 90px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            border: 2px dashed #adb5bd;
            border-radius: 0.6rem;
            background: #fff;
            color: #6c757d;
            font-size: 0.72rem;
            transition: border-color .15s ease, color .15s ease, background-color .15s ease;
        }

        .btn-tambah-foto:hover {
            border-color: #0F6B4C;
            color: #0F6B4C;
            background: #f4faf7;
        }

        .btn-tambah-foto i {
            font-size: 1.3rem;
        }

        .foto-thumb {
            width: 90px;
            height: 90px;
            flex-shrink: 0;
        }

        .foto-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        @media (max-width: 400px) {
            .btn-tambah-foto, .foto-thumb {
                width: 78px;
                height: 78px;
            }
        }
    </style>
</x-app-layout>