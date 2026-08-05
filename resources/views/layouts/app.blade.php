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

    {{-- Alpine.js (dipakai modal profile) + Bootstrap JS bundle --}}
    @vite(['resources/js/app.js'])

    {{-- jQuery, DataTables & SweetAlert2 dimuat di <head> (bukan di bawah body) karena
         script per-halaman (DataTables, konfirmasi hapus, dll) ada DI DALAM {{ '{{ $slot }}' }}
         yang posisinya lebih atas dari akhir body - kalau library ini dimuat belakangan,
         script halaman jalan duluan sebelum $ / Swal ada, dan diam-diam gagal semua. --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
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
                        <h6 class="dropdown-header">Notifikasi</h6>
                        <div id="notif-list" class="notif-list">
                            <div class="notif-kosong text-muted text-center py-3">Belum ada notifikasi</div>
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
                </div>
            </footer>
        </div>
    </div>

    {{-- Skrip khusus Mazer: burger-btn & sidebar-hide toggle sidebar --}}
    <script src="{{ asset('js/mazer/app.js') }}"></script>
    <script>
        // app.js bawaan Mazer punya sistem dark-mode sendiri yang pakai localStorage key
        // yang sama ("theme") tapi format nilai beda (theme-dark/theme-light vs dark/light
        // punya kita). Init tema versi Mazer itu BUKAN langsung jalan di baris ini, tapi baru
        // jalan pas event DOMContentLoaded - jadi dia sebenarnya jalan BELAKANGAN, nimpa
        // perbaikan apapun yang kita taruh di sini secara langsung. Makanya perbaikan kita juga
        // harus didaftarkan di event yang sama, supaya jalan paling akhir (menang terakhir).
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

        // pop-up toast otomatis muncul pas ada notifikasi BARU (gak nunggu lonceng diklik).
        // idTerbesarSebelumnya null di load pertama biar notif lama gak ikut nge-popup semua.
        let idTerbesarSebelumnya = null;

        function tampilkanPopupNotifikasi(n) {
            const toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 6000,
                timerProgressBar: true,
                didOpen: (el) => {
                    el.style.cursor = 'pointer';
                    el.addEventListener('click', () => {
                        if (n.link) window.location.href = n.link;
                    });
                },
            });

            toast.fire({
                icon: 'info',
                title: n.pesan,
            });
        }

        function ambilNotifikasi() {
            fetch("{{ route('notifikasi.data') }}")
                .then(res => res.json())
                .then(data => {
                    const badge = document.getElementById('notif-badge');
                    const list = document.getElementById('notif-list');

                    if (data.jumlah_belum_dibaca > 0) {
                        badge.textContent = data.jumlah_belum_dibaca;
                        badge.classList.remove('d-none');
                    } else {
                        badge.classList.add('d-none');
                    }

                    if (data.notifikasi.length === 0) {
                        list.innerHTML = '<div class="notif-kosong text-muted text-center py-3">Belum ada notifikasi</div>';
                    } else {
                        list.innerHTML = data.notifikasi.map(function (n) {
                            const kelasBelumDibaca = n.sudah_dibaca ? '' : 'notif-item-belum-dibaca';
                            const link = n.link ?? '#';
                            return `<a href="${link}" class="dropdown-item notif-item ${kelasBelumDibaca}">${n.pesan}</a>`;
                        }).join('');
                    }

                    if (data.notifikasi.length > 0) {
                        const idTerbesarSekarang = Math.max(...data.notifikasi.map(n => n.id));

                        if (idTerbesarSebelumnya === null) {
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

        document.getElementById('notifBellToggle')?.addEventListener('click', function () {
            fetch("{{ route('notifikasi.tandai-dibaca') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            }).then(() => {
                document.getElementById('notif-badge').classList.add('d-none');
            });
        });

        ambilNotifikasi();
        setInterval(ambilNotifikasi, 5000);
    </script>
</body>

</html>
