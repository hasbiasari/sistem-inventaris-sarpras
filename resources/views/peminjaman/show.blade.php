<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detail Peminjaman
        </h2>
    </x-slot>

    <div class="container py-4">

        <div class="card">
            <div class="card-body">

                <div class="row mb-3 gy-3">
                    <div class="col-md-4">
                        <div class="small text-muted"><i class="bi bi-tag-fill"></i> Kategori</div>
                        <div class="fw-semibold">{{ ucfirst($peminjaman->kategori) }}</div>
                    </div>
                    @if ($peminjaman->ormawa)
                        <div class="col-md-4">
                            <div class="small text-muted"><i class="bi bi-people-fill"></i> ORMAWA</div>
                            <div class="fw-semibold">{{ $peminjaman->ormawa }}</div>
                        </div>
                    @endif
                    @if ($peminjaman->nama_kegiatan)
                        <div class="col-md-4">
                            <div class="small text-muted"><i class="bi bi-calendar-event-fill"></i> Nama Kegiatan</div>
                            <div class="fw-semibold">{{ $peminjaman->nama_kegiatan }}</div>
                        </div>
                    @endif
                    <div class="col-md-4">
                        <div class="small text-muted"><i class="bi bi-info-circle-fill"></i> Status</div>
                        <div>
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
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted"><i class="bi bi-calendar-event-fill"></i> Waktu Mulai Pinjam</div>
                        <div class="fw-semibold">{{ $peminjaman->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    @if ($peminjaman->waktu_kembali)
                        <div class="col-md-4">
                            <div class="small text-muted"><i class="bi bi-calendar-check-fill"></i> Waktu Kembali</div>
                            <div class="fw-semibold">{{ $peminjaman->waktu_kembali->format('d/m/Y H:i') }}</div>
                        </div>
                    @endif
                </div>

                @if ($peminjaman->status === 'ditolak' && $peminjaman->catatan_admin)
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <strong>Alasan ditolak:</strong> {{ $peminjaman->catatan_admin }}
                    </div>
                @endif

                @if ($peminjaman->dokumen_izin)
                    <div class="mb-3">
                        <a href="{{ asset('storage/' . $peminjaman->dokumen_izin) }}" target="_blank" class="btn btn-sm btn-secondary">
                            <i class="bi bi-file-earmark-pdf-fill"></i> Lihat Dokumen Izin
                        </a>
                    </div>
                @endif

                <hr>

                <label class="form-label fw-bold">Daftar Barang</label>
                <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nama Barang</th>
                            <th>Nomor Unit</th>
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($peminjaman->details as $detail)
                            <tr>
                                <td>{{ $detail->asetUmum->nama_alat }}</td>
                                <td>{{ $detail->asetUmum->nomor_unit ?? '-' }}</td>
                                <td>{{ $detail->jumlah }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>

                @if ($peminjaman->buktiPengembalian->count())
                    <hr>
                    <label class="form-label fw-bold">Bukti Foto Pengembalian</label>
                    <div class="d-flex gap-2 flex-wrap">
                        @foreach ($peminjaman->buktiPengembalian as $bukti)
                            <a href="{{ asset('storage/' . $bukti->foto) }}" target="_blank">
                                <img src="{{ asset('storage/' . $bukti->foto) }}" alt="Bukti pengembalian"
                                     style="width: 100px; height: 100px; object-fit: cover;" class="rounded border">
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="d-flex gap-2 mt-3">
                    @if ($peminjaman->status === 'menunggu' || ($peminjaman->kategori === 'kuliah' && $peminjaman->status === 'disetujui'))
                        <a href="{{ route('peminjaman.edit', $peminjaman->id) }}" class="btn btn-warning btn-sm">
                            <i class="bi bi-pencil-fill"></i> Edit Peminjaman
                        </a>
                    @endif

                    @if ($peminjaman->status === 'disetujui')
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalKembalikan">
                            <i class="bi bi-box-arrow-in-left"></i> Kembalikan Barang
                        </button>
                    @endif

                    <a href="{{ route('peminjaman.riwayat') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>

            </div>
        </div>
    </div>

    @if ($peminjaman->status === 'disetujui')
        <div class="modal fade" id="modalKembalikan" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog">
                <form action="{{ route('peminjaman.kembalikan', $peminjaman->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title"><i class="bi bi-box-arrow-in-left"></i> Kembalikan Barang</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="upload-foto-wrapper">
                                <div class="upload-foto-zone d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn-tambah-foto d-flex align-items-center justify-content-center"
                                            style="width:70px;height:70px;border:2px dashed #ccc;border-radius:8px;background:transparent;">
                                        <i class="bi bi-plus-lg fs-4 text-muted"></i>
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

    <script>
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
                    thumb.className = 'foto-thumb position-relative';
                    thumb.innerHTML = `
                        <img src="${url}" style="width:70px;height:70px;object-fit:cover;" class="rounded border">
                        <button type="button" class="btn-hapus-foto btn btn-sm btn-danger rounded-circle position-absolute top-0 end-0 p-0 d-flex align-items-center justify-content-center"
                                style="width:20px;height:20px;transform:translate(30%,-30%);" data-idx="${idx}">&times;</button>
                    `;
                    zone.insertBefore(thumb, btnTambah);
                });

                const dt = new DataTransfer();
                files.forEach(f => dt.items.add(f));
                input.files = dt.files;
            }

            btnTambah.addEventListener('click', () => input.click());

            input.addEventListener('change', function () {
                files = files.concat(Array.from(input.files));
                render();
            });

            zone.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-hapus-foto');
                if (!btn) return;
                files.splice(parseInt(btn.dataset.idx, 10), 1);
                render();
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
</x-app-layout>
