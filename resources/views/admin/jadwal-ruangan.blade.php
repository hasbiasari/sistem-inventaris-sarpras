<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Lihat Jadwal Ruangan
        </h2>
    </x-slot>

    <div class="container-fluid py-4">

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.jadwal-ruangan') }}" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="{{ $tanggal }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-search"></i> Cek
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('admin.jadwal-ruangan') }}" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-repeat"></i> Hari Ini
                        </a>
                    </div>
                </form>
                <small class="text-muted d-block mt-2">
                    <i class="bi bi-info-circle"></i> Nampilin jadwal SATU HARI PENUH buat tiap ruangan pada tanggal di atas -- biar keliatan jam berapa aja yang udah dipesen dan barang/proyektor apa yang ikut dibawa, tanpa perlu nebak jam pastinya dulu.
                </small>
            </div>
        </div>

        <h6 class="fw-bold mb-3">
            <i class="bi bi-calendar-week"></i> Jadwal Ruangan &mdash; {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}
        </h6>

        <div class="row g-3">
            @foreach ($daftarRuangan as $ruangan)
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <span class="fw-bold">{{ $ruangan['nama_ruangan'] }}</span>
                            <span class="badge bg-{{ $ruangan['jadwal']->isEmpty() ? 'success' : 'warning' }}">
                                {{ $ruangan['jadwal']->isEmpty() ? 'Kosong Sepanjang Hari' : $ruangan['jadwal']->count() . ' Jadwal' }}
                            </span>
                        </div>
                        <div class="card-body">
                            @forelse ($ruangan['jadwal'] as $j)
                                @php
                                    $kelasOrmawa = $j['kategori'] === 'organisasi' ? ($j['ormawa'] ?? '-') : ($j['kelas'] ?? '-');
                                    $warnaKategori = match($j['kategori']) {
                                        'kuliah' => 'primary',
                                        'organisasi' => 'info',
                                        'eksternal' => 'dark',
                                        default => 'secondary',
                                    };
                                @endphp
                                <div class="mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <span class="fw-semibold">{{ $j['jam_mulai'] }} - {{ $j['jam_selesai'] }}</span>
                                        <span class="badge bg-{{ $warnaKategori }}">{{ ucfirst($j['kategori']) }}</span>
                                    </div>
                                    <div class="small">{{ $kelasOrmawa }} &middot; oleh {{ $j['nama'] }}</div>
                                    @if ($j['status'] === 'menunggu')
                                        <span class="badge bg-warning text-dark small">Menunggu Persetujuan</span>
                                    @endif
                                    @if ($j['sampai_tanggal'])
                                        <div class="small text-muted">Sampai {{ $j['sampai_tanggal'] }}</div>
                                    @endif
                                    @if ($j['barang'])
                                        <div class="small text-muted">Barang: {{ $j['barang'] }}</div>
                                    @endif
                                </div>
                            @empty
                                <p class="text-muted text-center mb-0">Belum ada jadwal buat ruangan ini.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</x-app-layout>
