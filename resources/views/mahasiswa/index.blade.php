<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Manajemen Data Mahasiswa
        </h2>
    </x-slot>

    <div class="container py-4">

        {{-- notif kalau berhasil tambah/import data --}}
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- kalau ada error pas import excel --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

     {{-- tombol aksi --}}
<div class="page-toolbar">
    <div class="toolbar-actions">
        <a href="{{ route('admin.mahasiswa.create') }}" class="btn btn-primary">
            [+] Tambah Mahasiswa
        </a>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalImport">
            [+] Import via Excel
        </button>
    </div>

    <div class="toolbar-search">
        <form method="GET" action="{{ route('admin.mahasiswa') }}" class="mb-0">
            <div class="input-group">
                <input type="text" name="cari" class="form-control" placeholder="Cari NIM / Nama / Email..." value="{{ $keyword ?? '' }}">
                <select name="urutan" class="form-select" style="max-width: 160px;">
                    <option value="a-z" @selected(($urutan ?? 'a-z') === 'a-z')>Urutan A-Z</option>
                    <option value="terbaru" @selected(($urutan ?? '') === 'terbaru')>Urutan Terbaru</option>
                </select>
                <button type="submit" class="btn btn-outline-secondary">Cari</button>
            </div>
        </form>
    </div>
</div>

        {{-- tabel data mahasiswa --}}
        <table class="table table-bordered" id="tabelMahasiswa">
            <thead>
                <tr>
                    <th>No</th>
                    <th>NIM</th>
                    <th>Nama Mahasiswa</th>
                    <th>Email</th>
                 <th>Status</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>
    @foreach ($mahasiswas as $index => $mhs)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $mhs->nim }}</td>
            <td>{{ $mhs->nama }}</td>
            <td>{{ $mhs->user->email }}</td>
            <td>{{ $mhs->status }}</td>
           <td>
    <div class="btn-group gap-1">
        <a href="{{ route('admin.mahasiswa.edit', $mhs->id) }}" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Edit">
            <i class="bi bi-pencil-fill"></i>
        </a>

        <form method="POST" action="{{ route('admin.mahasiswa.reset', $mhs->id) }}" class="d-inline form-konfirmasi" data-pesan="Yakin reset password {{ $mhs->nama }} ke NIM?">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Reset Password">
                <i class="bi bi-key-fill"></i>
            </button>
        </form>

        <form method="POST" action="{{ route('admin.mahasiswa.destroy', $mhs->id) }}" class="d-inline form-konfirmasi" data-pesan="Yakin hapus data {{ $mhs->nama }}? Data yang dihapus gak bisa dibalikin lagi." data-danger="1">
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

    {{-- modal buat upload file excel --}}
    <div class="modal fade" id="modalImport" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.mahasiswa.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Import Data Mahasiswa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted">Kolom wajib di excel: <b>nim, nama, email</b></p>
                        <input type="file" name="file_excel" class="form-control" accept=".xlsx,.xls">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // pasang konfirmasi SweetAlert2 ke semua form yang ada class form-konfirmasi
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

    $('#tabelMahasiswa').DataTable({
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
            emptyTable: "Belum ada data mahasiswa.",
        }
    });
</script>

</x-app-layout>