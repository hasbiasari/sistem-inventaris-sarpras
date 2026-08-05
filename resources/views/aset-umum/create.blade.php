<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tambah Aset
        </h2>
    </x-slot>

    <div class="container py-4">

        <form method="POST" action="{{ route('admin.aset-umum.store') }}">
            @csrf

            <div class="mb-3">
                <label>Nama Alat</label>
                <input type="text" name="nama_alat" class="form-control" value="{{ old('nama_alat') }}" placeholder="contoh: Proyektor">
                @error('nama_alat') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Nomor Unit (khusus alat yang punya nomor, contoh: 4)</label>
                <input type="text" name="nomor_unit" class="form-control" value="{{ old('nomor_unit') }}">
                @error('nomor_unit') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Merek</label>
                <input type="text" name="merek" class="form-control" value="{{ old('merek') }}" placeholder="contoh: Epson EB-X500">
                @error('merek') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Kode Ruangan (khusus untuk alat Kunci, contoh: B202 / Labkom 1)</label>
                <input type="text" name="kode_aset" class="form-control" value="{{ old('kode_aset') }}">
                @error('kode_aset') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Jumlah Stok</label>
                <input type="number" name="jumlah_stok" class="form-control" value="{{ old('jumlah_stok', 0) }}">
                @error('jumlah_stok') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="tersedia" {{ old('status') == 'tersedia' ? 'selected' : '' }}>Tersedia</option>
                    <option value="dipinjam" {{ old('status') == 'dipinjam' ? 'selected' : '' }}>Dipinjam</option>
                    <option value="rusak" {{ old('status') == 'rusak' ? 'selected' : '' }}>Rusak</option>
                    <option value="pemeliharaan" {{ old('status') == 'pemeliharaan' ? 'selected' : '' }}>Pemeliharaan</option>
                </select>
                @error('status') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('admin.aset-umum') }}" class="btn btn-secondary">Batal</a>

        </form>

    </div>
</x-app-layout>
