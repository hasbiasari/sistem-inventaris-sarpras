<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Aset Kelas
        </h2>
    </x-slot>

    <div class="container py-4">

        <form method="POST" action="{{ route('admin.aset-kelas.update', $asetKelas->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label>Nama Ruangan</label>
                <input type="text" name="nama_ruangan" class="form-control" value="{{ old('nama_ruangan', $asetKelas->nama_ruangan) }}">
                @error('nama_ruangan') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Gedung</label>
                <select name="gedung" class="form-select">
                    <option value="" @selected(old('gedung', $asetKelas->gedung) === null || old('gedung', $asetKelas->gedung) === '')>Lainnya</option>
                    <option value="Gedung A" @selected(old('gedung', $asetKelas->gedung) === 'Gedung A')>Gedung A</option>
                    <option value="Gedung B" @selected(old('gedung', $asetKelas->gedung) === 'Gedung B')>Gedung B</option>
                    <option value="Gedung C" @selected(old('gedung', $asetKelas->gedung) === 'Gedung C')>Gedung C</option>
                    <option value="Gedung D" @selected(old('gedung', $asetKelas->gedung) === 'Gedung D')>Gedung D</option>
                </select>
                @error('gedung') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Kapasitas</label>
                <input type="number" name="kapasitas" class="form-control" value="{{ old('kapasitas', $asetKelas->kapasitas) }}">
                @error('kapasitas') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Jumlah Kursi</label>
                <input type="number" name="jumlah_kursi" class="form-control" value="{{ old('jumlah_kursi', $asetKelas->jumlah_kursi) }}">
                @error('jumlah_kursi') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Jumlah Papan Tulis</label>
                <input type="number" name="jumlah_papan_tulis" class="form-control" value="{{ old('jumlah_papan_tulis', $asetKelas->jumlah_papan_tulis) }}">
                @error('jumlah_papan_tulis') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('admin.aset-kelas') }}" class="btn btn-secondary">Batal</a>

        </form>

    </div>
</x-app-layout>