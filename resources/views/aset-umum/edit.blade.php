<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Aset
        </h2>
    </x-slot>

    <div class="container py-4">

        @if ($asetUmum->peminjamanDetailAktifSekarang->isNotEmpty())
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill"></i>
                Aset ini lagi dipinjam sama:
                <strong>{{ $asetUmum->peminjamanDetailAktifSekarang->map(fn($d) => $d->peminjaman->nama_peminjam)->join(', ') }}</strong>
                sekarang. Hati-hati kalau mau ubah stok manual di sini, biar gak beda sama catatan peminjamannya.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.aset-umum.update', $asetUmum->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Nama Alat</label>
                <input type="text" name="nama_alat" class="form-control" value="{{ old('nama_alat', $asetUmum->nama_alat) }}">
                @error('nama_alat') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Nomor Unit</label>
                <input type="text" name="nomor_unit" class="form-control" value="{{ old('nomor_unit', $asetUmum->nomor_unit) }}">
                @error('nomor_unit') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Merek</label>
                <input type="text" name="merek" class="form-control" value="{{ old('merek', $asetUmum->merek) }}">
                @error('merek') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Kode Ruangan (khusus untuk alat Kunci, contoh: B202 / Labkom 1)</label>
                <input type="text" name="kode_aset" class="form-control" value="{{ old('kode_aset', $asetUmum->kode_aset) }}">
                @error('kode_aset') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Jumlah Stok</label>
                <input type="number" name="jumlah_stok" class="form-control" value="{{ old('jumlah_stok', $asetUmum->jumlah_stok) }}">
                @error('jumlah_stok') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="tersedia" @selected(old('status', $asetUmum->status) === 'tersedia')>Tersedia</option>
                    <option value="rusak" @selected(old('status', $asetUmum->status) === 'rusak')>Rusak</option>
                    <option value="pemeliharaan" @selected(old('status', $asetUmum->status) === 'pemeliharaan')>Pemeliharaan</option>
                </select>
                <small class="text-muted">Status "Dipinjam" otomatis ngikutin data peminjaman, gak bisa dipilih di sini.</small>
                @error('status') <small class="text-danger d-block">{{ $message }}</small> @enderror
            </div>

            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('admin.aset-umum') }}" class="btn btn-secondary">Batal</a>

        </form>

    </div>
</x-app-layout>
