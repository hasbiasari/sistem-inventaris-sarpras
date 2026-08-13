@extends('laporan.pdf.layout')

@section('konten')
    <table>
        <thead>
            <tr>
                <th>Unit</th>
                <th>Merek</th>
                <th>Total Jam Pakai</th>
                <th>Menuju Batas Servis</th>
                <th>Estimasi Servis</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($daftarProyektor as $p)
                @php
                    $estimasi = $p->estimasiMingguMenujuServis();
                    $labelEstimasi = match (true) {
                        $p->status === 'pemeliharaan' => 'Sedang diservis',
                        $estimasi === null => 'Belum cukup data',
                        $estimasi <= 0 => 'Sekarang',
                        default => "~{$estimasi} minggu lagi",
                    };
                    $labelStatus = match ($p->status_pemeliharaan) {
                        'dalam_pemeliharaan' => 'Dalam Pemeliharaan',
                        'perlu_pemeliharaan' => 'Perlu Pemeliharaan',
                        'perlu_perhatian' => 'Perlu Perhatian',
                        default => 'Normal',
                    };
                @endphp
                <tr>
                    <td>#{{ $p->nomor_unit }}</td>
                    <td>{{ $p->merek ?? '-' }}</td>
                    <td>{{ $p->total_jam_pakai }} / {{ $p->batas_jam_maksimal }} jam</td>
                    <td>{{ $p->persentase_menuju_servis }}%</td>
                    <td>{{ $labelEstimasi }}</td>
                    <td>{{ $labelStatus }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;">Tidak ada data proyektor.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection

@section('ringkasan')
    <table>
        <tr>
            <th>Normal</th>
            <th>Perlu Perhatian</th>
            <th>Perlu Pemeliharaan</th>
            <th>Dalam Pemeliharaan</th>
            <th>Total</th>
        </tr>
        <tr>
            <td>{{ $ringkasanStatus['normal'] }}</td>
            <td>{{ $ringkasanStatus['perlu_perhatian'] }}</td>
            <td>{{ $ringkasanStatus['perlu_pemeliharaan'] }}</td>
            <td>{{ $ringkasanStatus['dalam_pemeliharaan'] }}</td>
            <td><strong>{{ $daftarProyektor->count() }}</strong></td>
        </tr>
    </table>
@endsection
