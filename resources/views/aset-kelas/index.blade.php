<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Manajemen Aset Kelas
        </h2>
    </x-slot>

    <div class="container py-4">

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="page-toolbar">
            <div class="toolbar-actions">
                <a href="{{ route('admin.aset-kelas.create') }}" class="btn btn-primary">
                    [+] Tambah Aset Kelas
                </a>
            </div>

            <div class="toolbar-search">
                <form method="GET" action="{{ route('admin.aset-kelas') }}" class="mb-0">
                    <div class="input-group">
                        <input type="text" name="cari" class="form-control" placeholder="Cari Nama Ruangan..." value="{{ $keyword ?? '' }}">
                        <select name="urutan" class="form-select" style="max-width: 160px;">
                            <option value="a-z" @selected(($urutan ?? 'a-z') === 'a-z')>Urutan A-Z</option>
                            <option value="terbaru" @selected(($urutan ?? '') === 'terbaru')>Urutan Terbaru</option>
                        </select>
                        <button type="submit" class="btn btn-outline-secondary">Cari</button>
                    </div>
                </form>
            </div>
        </div>

        <table class="table table-bordered" id="tabelAsetKelas">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Ruangan</th>
                    <th>Gedung</th>
                    <th>Kapasitas</th>
                    <th>Jumlah Kursi</th>
                    <th>Jumlah Papan Tulis</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($asetKelas as $index => $kelas)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $kelas->nama_ruangan }}</td>
                        <td>
                            <span class="badge bg-secondary">{{ $kelas->gedung ?? 'Lainnya' }}</span>
                        </td>
                        <td>{{ $kelas->kapasitas }}</td>
                        <td>{{ $kelas->jumlah_kursi }}</td>
                        <td>{{ $kelas->jumlah_papan_tulis }}</td>
                        <td>
                            <div class="btn-group gap-1">
                                <a href="{{ route('admin.aset-kelas.edit', $kelas->id) }}" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Edit">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>

                                <form method="POST" action="{{ route('admin.aset-kelas.destroy', $kelas->id) }}" class="d-inline form-konfirmasi" data-pesan="Yakin hapus data ruangan {{ $kelas->nama_ruangan }}?" data-danger="1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Hapus">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>

    <script>
        document.querySelectorAll('.form-konfirmasi').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const pesan = form.dataset.pesan;
                const danger = form.dataset.danger === '1';
                Swal.fire({
                    title: 'Konfirmasi',
                    text: pesan,
                    icon: 'warning',
                    showCancelButton: true,
                    reverseButtons: true,
                    confirmButtonColor: danger ? '#c94a4a' : '#0F6B4C',
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

        $(function () {
            $('#tabelAsetKelas').DataTable({
                responsive: true,
                columnDefs: [{ className: 'all', targets: -1 }],
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    infoEmpty: "Tidak ada data",
                    paginate: { previous: "Sebelumnya", next: "Selanjutnya" },
                    zeroRecords: "Tidak ada data yang cocok",
                    emptyTable: "Belum ada data aset kelas.",
                }
            });
        });
    </script>
</x-app-layout>