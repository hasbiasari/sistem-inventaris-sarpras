<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tambah Aset Kelas
        </h2>
    </x-slot>

    <div class="container py-4">

        <form method="POST" action="{{ route('admin.aset-kelas.store') }}">
            @csrf

            <div class="mb-3">
                <label>Nama Ruangan</label>
                <input type="text" name="nama_ruangan" class="form-control" value="{{ old('nama_ruangan') }}">
                @error('nama_ruangan') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Kapasitas</label>
                <input type="number" name="kapasitas" class="form-control" value="{{ old('kapasitas') }}">
                @error('kapasitas') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Jumlah Kursi</label>
                <input type="number" name="jumlah_kursi" class="form-control" value="{{ old('jumlah_kursi', 0) }}">
                @error('jumlah_kursi') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Jumlah Papan Tulis</label>
                <input type="number" name="jumlah_papan_tulis" class="form-control" value="{{ old('jumlah_papan_tulis', 0) }}">
                @error('jumlah_papan_tulis') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('admin.aset-kelas') }}" class="btn btn-secondary">Batal</a>

        </form>

    </div>
</x-app-layout>