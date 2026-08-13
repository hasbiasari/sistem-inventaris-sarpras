@extends('laporan.pdf.layout')

@section('konten')
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Peminjam</th>
                <th>Kategori</th>
                <th>Kelas/Ormawa/Instansi</th>
                <th>Ruangan</th>
                <th>Barang</th>
                <th>Tanggal</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($daftarPeminjaman as $i => $p)
                @php
                    $kategoriTampil = $p->jenis_peminjam === 'eksternal' ? 'Eksternal' : ucfirst($p->kategori ?? '-');
                    $keteranganTambahan = $p->kelas ?? $p->ormawa ?? $p->keterangan_eksternal ?? '-';
                    $labelStatus = $p->status === 'selesai' ? 'Dikembalikan' : ucfirst($p->status);
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $p->nama_peminjam }}</td>
                    <td>{{ $kategoriTampil }}</td>
                    <td>{{ $keteranganTambahan }}</td>
                    <td>{{ $p->asetKelas->nama_ruangan ?? '-' }}</td>
                    <td>
                        @if ($p->details->isNotEmpty())
                            {{ $p->details->map(fn ($d) => ($d->asetUmum->nama_alat ?? '-') . ' x' . $d->jumlah)->join(', ') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $p->created_at->format('d/m/Y') }}</td>
                    <td>{{ $labelStatus }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;">Tidak ada data peminjaman.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection

@section('ringkasan')
    <table>
        <tr>
            <th>Menunggu</th>
            <th>Disetujui</th>
            <th>Ditolak</th>
            <th>Dibatalkan</th>
            <th>Selesai</th>
            <th>Total</th>
        </tr>
        <tr>
            <td>{{ $ringkasanStatus['menunggu'] }}</td>
            <td>{{ $ringkasanStatus['disetujui'] }}</td>
            <td>{{ $ringkasanStatus['ditolak'] }}</td>
            <td>{{ $ringkasanStatus['dibatalkan'] }}</td>
            <td>{{ $ringkasanStatus['selesai'] }}</td>
            <td><strong>{{ $daftarPeminjaman->count() }}</strong></td>
        </tr>
    </table>
@endsection
