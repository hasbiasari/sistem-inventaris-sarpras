<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tambah Mahasiswa
        </h2>
    </x-slot>

    <div class="container py-4">

        <form method="POST" action="{{ route('admin.mahasiswa.store') }}">
            @csrf

            <div class="mb-3">
                <label>NIM</label>
                <input type="text" name="nim" class="form-control" value="{{ old('nim') }}">
                @error('nim') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Nama</label>
                <input type="text" name="nama" class="form-control" value="{{ old('nama') }}">
                @error('nama') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                @error('email') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            {{-- password otomatis dibuat dari NIM, jadi gaperlu diinput manual --}}

            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('admin.mahasiswa') }}" class="btn btn-secondary">Batal</a>

        </form>

    </div>
</x-app-layout>