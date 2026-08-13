<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    {{-- Tampilan mengikuti template Mazer (CSS hasil compile, tidak di-build ulang) --}}
    <link rel="stylesheet" href="{{ asset('css/mazer/main/app.css') }}?v={{ filemtime(public_path('css/mazer/main/app.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/mazer/main/app-dark.css') }}?v={{ filemtime(public_path('css/mazer/main/app-dark.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/mazer/shared/iconly.css') }}?v={{ filemtime(public_path('css/mazer/shared/iconly.css')) }}">
    {{-- Override warna biru Mazer -> hijau STT Cipasung --}}
    <link rel="stylesheet" href="{{ asset('css/mazer-green-theme.css') }}?v={{ filemtime(public_path('css/mazer-green-theme.css')) }}">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    {{-- DataTables Responsive: kolom yang gak muat di layar sempit dilipat jadi tombol "+" (expand),
         bukan bikin tabel geser ke samping --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    {{-- Alpine.js (dipakai modal profile) + Bootstrap JS bundle --}}
    @vite(['resources/js/app.js'])

    {{-- jQuery, DataTables & SweetAlert2 dimuat di <head> (bukan di bawah body) karena
         script per-halaman (DataTables, konfirmasi hapus, dll) ada DI DALAM {{ '{{ $slot }}' }}
         yang posisinya lebih atas dari akhir body - kalau library ini dimuat belakangan,
         script halaman jalan duluan sebelum $ / Swal ada, dan diam-diam gagal semua. --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <script>
        (function () {
            var theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
            document.body.classList.add(theme === 'dark' ? 'theme-dark' : 'theme-light');
        })();
    </script>

    <div id="app">
        <div id="sidebar" class="active">
            <div class="sidebar-wrapper active">
                @include('layouts.sidebar')
            </div>
        </div>

        <div id="main">
            <header class="mb-3 d-flex align-items-center">
                <a href="#" class="burger-btn d-block">
                    <i class="bi bi-justify fs-3"></i>
                </a>

                <span class="text-muted ms-2">Selamat datang, {{ auth()->user()->name }}</span>

                <div class="notif-wrapper dropdown ms-auto">
                    <a href="#" class="notif-bell" role="button" id="notifBellToggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell fs-4"></i>
                        <span id="notif-badge" class="notif-badge badge rounded-pill bg-danger d-none">0</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end notif-dropdown-menu" aria-labelledby="notifBellToggle">
                        <div class="notif-dropdown-header">
                            <p class="notif-title mb-0">Notifikasi</p>
                            <button type="button" id="notif-tandai-semua" class="notif-tandai-semua" disabled>Tandai semua dibaca</button>
                        </div>
                        <div id="notif-list" class="notif-list">
                            <div class="notif-kosong text-muted text-center py-4">Belum ada notifikasi</div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="page-heading">
                @isset($header)
                    <div class="page-title mb-3">
                        {{ $header }}
                    </div>
                @endisset

                {{ $slot }}
            </div>

            <footer>
                <div class="footer clearfix mb-0 text-muted">
                    <div class="float-start">
                        <p>&copy; {{ date('Y') }} <a href="https://sttcipasung.ac.id/" target="_blank" rel="noopener">STT Cipasung</a> &mdash; Sistem Inventaris Sarpras</p>
                    </div>
                    <div class="float-end">
                        <p>Powered by <a href="https://www.instagram.com/muhammadhasbiasari?igsh=eGF4MGR6OTc1OXRz&utm_source=qr" target="_blank" rel="noopener">MHA</a></p>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    {{-- Skrip khusus Mazer: burger-btn & sidebar-hide toggle sidebar --}}
    <script src="{{ asset('js/mazer/app.js') }}"></script>
    <script>
        // sinkronin format tema kita ke class dark-mode punya Mazer
        function terapkanTemaSidebar() {
            var theme = localStorage.getItem('theme') || 'light';
            document.body.classList.toggle('theme-dark', theme === 'dark');
            document.body.classList.toggle('theme-light', theme !== 'dark');
        }

        if (document.readyState === 'loading') {
            window.addEventListener('DOMContentLoaded', terapkanTemaSidebar);
        } else {
            terapkanTemaSidebar();
        }
    </script>

    @stack('modal')
    @stack('scripts')

    <style>
        .notif-popup-besar.swal2-popup {
            border-radius: 1.1rem;
            padding: 2rem 1.75rem 1.75rem;
        }

        .notif-popup-besar .swal2-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0F6B4C;
        }

        .notif-popup-besar .swal2-html-container {
            font-size: 1.02rem;
        }

        .notif-popup-besar .swal2-icon.swal2-info {
            border-color: #0F6B4C;
            color: #0F6B4C;
        }

        .notif-popup-besar .swal2-close {
            font-size: 1.8rem;
        }

        .notif-popup-besar .swal2-close:hover {
            color: #0F6B4C;
        }

        .notif-popup-besar .swal2-confirm {
            background-color: #0F6B4C !important;
            border-radius: 0.6rem;
            padding: 0.6rem 1.4rem;
        }
    </style>

    <script>
        $(function () {
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            [...tooltipTriggerList].map(el => new bootstrap.Tooltip(el));
        });

        document.getElementById('logout')?.addEventListener('click', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'Keluar?',
                text: 'Anda akan keluar dari aplikasi',
                icon: 'warning',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonColor: '#0F6B4C',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, keluar',
                cancelButtonText: 'Batal',
            }).then(function (result) {
                if (result.isConfirmed) {
                    document.getElementById('logout-form').submit();
                }
            });
        });

        // popup notifikasi baru -> muncul di tengah, gak nutup sendiri, antre kalau lebih dari satu
        let idTerbesarSebelumnya = null;
        let antrianPopupNotifikasi = [];
        let popupNotifikasiSedangTampil = false;

        const peranUser = "{{ auth()->user()->role }}";
        const urlPolaTandaiSatuDibaca = "{{ route('notifikasi.tandai-satu-dibaca', ':id') }}";
        function urlTandaiSatuDibaca(id) {
            return urlPolaTandaiSatuDibaca.replace(':id', id);
        }

        function tampilkanPopupNotifikasi(n) {
            // popup gede di tengah cukup buat mahasiswa (misal pengingat telat kembalikan barang).
            // role lain (admin, pimpinan) cukup keliatan di lonceng notifikasi, gak usah nongol popup -> biar gak ribet.
            if (peranUser !== 'mahasiswa') return;

            antrianPopupNotifikasi.push(n);
            prosesAntrianPopupNotifikasi();
        }

        function prosesAntrianPopupNotifikasi() {
            if (popupNotifikasiSedangTampil || antrianPopupNotifikasi.length === 0) return;

            const n = antrianPopupNotifikasi.shift();
            popupNotifikasiSedangTampil = true;

            Swal.fire({
                icon: 'info',
                title: n.pesan,
                width: 460,
                showCloseButton: true,
                showConfirmButton: !!n.link,
                confirmButtonText: 'Lihat Detail',
                customClass: { popup: 'notif-popup-besar' },
            }).then((result) => {
                popupNotifikasiSedangTampil = false;

                if (result.isConfirmed && n.link) {
                    fetch(urlTandaiSatuDibaca(n.id), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken() },
                    });
                    window.location.href = n.link;
                    return;
                }

                // ditutup pake tombol X (bukan diklik detailnya) -> tetep belum dibaca,
                // biar muncul lagi popup-nya di reload/login berikutnya selama belum ditindaklanjuti
                prosesAntrianPopupNotifikasi();
            });
        }

        // format waktu relatif ringkas: "baru saja", "5 menit lalu", "3 jam lalu", "2 hari lalu"
        function waktuRelatif(isoString) {
            const detik = Math.floor((Date.now() - new Date(isoString).getTime()) / 1000);

            if (detik < 60) return 'baru saja';
            if (detik < 3600) return Math.floor(detik / 60) + ' menit lalu';
            if (detik < 86400) return Math.floor(detik / 3600) + ' jam lalu';
            if (detik < 2592000) return Math.floor(detik / 86400) + ' hari lalu';
            return new Date(isoString).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        }

        function csrfToken() {
            return document.querySelector('meta[name="csrf-token"]').content;
        }

        function renderNotifikasi(data) {
            const badge = document.getElementById('notif-badge');
            const list = document.getElementById('notif-list');
            const tombolTandaiSemua = document.getElementById('notif-tandai-semua');

            if (data.jumlah_belum_dibaca > 0) {
                badge.textContent = data.jumlah_belum_dibaca;
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
            tombolTandaiSemua.disabled = data.jumlah_belum_dibaca === 0;

            if (data.notifikasi.length === 0) {
                list.innerHTML = '<div class="notif-kosong text-muted text-center py-4">Belum ada notifikasi</div>';
                return;
            }

            list.innerHTML = data.notifikasi.map(function (n) {
                const belumDibaca = !n.sudah_dibaca;
                const kelasBelumDibaca = belumDibaca ? 'notif-item-belum-dibaca' : '';
                const link = n.link ?? '#';
                return `<a href="${link}" data-id="${n.id}" class="dropdown-item notif-item ${kelasBelumDibaca}">
                    <span class="notif-item-icon"><i class="bi bi-bell-fill"></i></span>
                    <span class="notif-item-body">
                        <span class="notif-item-pesan">${n.pesan}</span>
                        <span class="notif-item-waktu">${waktuRelatif(n.created_at)}</span>
                    </span>
                    ${belumDibaca ? '<span class="notif-item-dot"></span>' : ''}
                </a>`;
            }).join('');
        }

        function ambilNotifikasi() {
            fetch("{{ route('notifikasi.data') }}")
                .then(res => res.json())
                .then(data => {
                    renderNotifikasi(data);

                    if (data.notifikasi.length > 0) {
                        const idTerbesarSekarang = Math.max(...data.notifikasi.map(n => n.id));

                        if (idTerbesarSebelumnya === null) {
                            // baru buka/reload halaman -> tetep tampilin popup buat notif yang belum dibaca
                            // (misal: kemarin ditutup doang tapi belum diklik/ditandai, jadi harus muncul lagi)
                            data.notifikasi
                                .filter(n => !n.sudah_dibaca)
                                .reverse()
                                .forEach(tampilkanPopupNotifikasi);
                            idTerbesarSebelumnya = idTerbesarSekarang;
                        } else if (idTerbesarSekarang > idTerbesarSebelumnya) {
                            data.notifikasi
                                .filter(n => n.id > idTerbesarSebelumnya)
                                .reverse()
                                .forEach(tampilkanPopupNotifikasi);
                            idTerbesarSebelumnya = idTerbesarSekarang;
                        }
                    }
                });
        }

        // klik satu notifikasi -> cuma itu aja yang ditandai dibaca, sisanya tetap kebaca statusnya
        document.getElementById('notif-list')?.addEventListener('click', function (e) {
            const item = e.target.closest('.notif-item');
            if (!item || !item.classList.contains('notif-item-belum-dibaca')) return;

            fetch(urlTandaiSatuDibaca(item.dataset.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken() },
            });
            // gak perlu nunggu response -> biarin link jalan (navigasi) langsung
        });

        document.getElementById('notif-tandai-semua')?.addEventListener('click', function () {
            fetch("{{ route('notifikasi.tandai-dibaca') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken() },
            }).then(ambilNotifikasi);
        });

        ambilNotifikasi();
        const idIntervalNotifikasi = setInterval(ambilNotifikasi, 1000);

        // begitu ada form disubmit (ajukan peminjaman, dll), stop dulu polling notifikasi ini.
        // soalnya kalau polling masih jalan bareng request submit yang lagi redirect, session bisa balapan
        // nyimpen -- polling yang selesainya belakangan bisa nimpa balik flash "berhasil"/"struk" punya
        // request submit tadi jadi kosong lagi, makanya kadang abis ajukan peminjaman notifikasi/pop-up
        // berhasilnya gak muncul padahal datanya sendiri udah beneran kesimpen.
        document.addEventListener('submit', function () {
            clearInterval(idIntervalNotifikasi);
        }, true);
    </script>
</body>

</html>
