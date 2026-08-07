<div class="sidebar-header position-relative">
    <div class="sidebar-toggler x">
        <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
    </div>

    <div class="logo d-flex align-items-center gap-2">
        <img src="{{ asset('images/logo-sidebar.png') }}" alt="Logo STT Cipasung" class="sidebar-brand-logo">
        <span class="sidebar-brand-text fw-semibold">Inventaris Sarpras</span>
    </div>
</div>

<div class="sidebar-menu">
    <ul class="menu">
        @if (auth()->user()->role === 'admin_tu')
            <li class="sidebar-title">Menu</li>
            <li class="sidebar-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link">
                    <i class="bi bi-grid-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="sidebar-title">Data Master</li>
            <li class="sidebar-item {{ request()->routeIs('admin.aset-kelas*') ? 'active' : '' }}">
                <a href="{{ route('admin.aset-kelas') }}" class="sidebar-link">
                    <i class="bi bi-building"></i>
                    <span>Aset Kelas</span>
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('admin.aset-umum*') ? 'active' : '' }}">
                <a href="{{ route('admin.aset-umum') }}" class="sidebar-link">
                    <i class="bi bi-tools"></i>
                    <span>Aset Umum</span>
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('admin.pemeliharaan-proyektor*') ? 'active' : '' }}">
                <a href="{{ route('admin.pemeliharaan-proyektor') }}" class="sidebar-link">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Pemeliharaan Proyektor</span>
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('admin.mahasiswa*') ? 'active' : '' }}">
                <a href="{{ route('admin.mahasiswa') }}" class="sidebar-link">
                    <i class="bi bi-people-fill"></i>
                    <span>Data Mahasiswa</span>
                </a>
            </li>

            <li class="sidebar-title">Peminjaman</li>
            <li class="sidebar-item {{ request()->routeIs('admin.jadwal-ruangan*') ? 'active' : '' }}">
                <a href="{{ route('admin.jadwal-ruangan') }}" class="sidebar-link">
                    <i class="bi bi-calendar-week"></i>
                    <span>Lihat Jadwal</span>
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('admin.peminjaman.laporan*') ? 'active' : '' }}">
                <a href="{{ route('admin.peminjaman.laporan') }}" class="sidebar-link">
                    <i class="bi bi-bar-chart-line-fill"></i>
                    <span>Laporan Peminjaman</span>
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('admin.booking-eksternal*') ? 'active' : '' }}">
                <a href="{{ route('admin.booking-eksternal') }}" class="sidebar-link">
                    <i class="bi bi-calendar2-plus"></i>
                    <span>Booking Eksternal</span>
                </a>
            </li>
        @elseif (auth()->user()->role === 'pimpinan')
            <li class="sidebar-title">Menu</li>
            <li class="sidebar-item {{ request()->routeIs('pimpinan.dashboard') ? 'active' : '' }}">
                <a href="{{ route('pimpinan.dashboard') }}" class="sidebar-link">
                    <i class="bi bi-grid-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('pimpinan.peminjaman') ? 'active' : '' }}">
                <a href="{{ route('pimpinan.peminjaman') }}" class="sidebar-link">
                    <i class="bi bi-journal-text"></i>
                    <span>Peminjaman</span>
                </a>
            </li>
        @elseif (auth()->user()->role === 'mahasiswa')
            <li class="sidebar-title">Menu</li>
            <li class="sidebar-item {{ request()->routeIs('mahasiswa.dashboard') ? 'active' : '' }}">
                <a href="{{ route('mahasiswa.dashboard') }}" class="sidebar-link">
                    <i class="bi bi-grid-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('mahasiswa.aset-umum') ? 'active' : '' }}">
                <a href="{{ route('mahasiswa.aset-umum') }}" class="sidebar-link">
                    <i class="bi bi-box-seam"></i>
                    <span>Aset Umum</span>
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('mahasiswa.aset-kelas') ? 'active' : '' }}">
                <a href="{{ route('mahasiswa.aset-kelas') }}" class="sidebar-link">
                    <i class="bi bi-building"></i>
                    <span>Aset Kelas</span>
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('peminjaman.create') || request()->routeIs('peminjaman.edit') ? 'active' : '' }}">
                <a href="{{ route('peminjaman.create') }}" class="sidebar-link">
                    <i class="bi bi-pencil-square"></i>
                    <span>Ajukan Peminjaman</span>
                </a>
            </li>
            <li class="sidebar-item {{ request()->routeIs('peminjaman.riwayat') || request()->routeIs('peminjaman.show') ? 'active' : '' }}">
                <a href="{{ route('peminjaman.riwayat') }}" class="sidebar-link">
                    <i class="bi bi-clock-history"></i>
                    <span>Riwayat Peminjaman</span>
                </a>
            </li>
        @endif

        <li class="sidebar-title">Tampilan</li>
        <li class="sidebar-item">
            <a href="#" class="sidebar-link" onclick="toggleTheme(); return false;">
                <i class="bi bi-moon-stars-fill"></i>
                <span>Mode Gelap/Terang</span>
            </a>
        </li>

        <li class="sidebar-title">Akun</li>
        <li class="sidebar-item {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
            <a href="{{ route('profile.edit') }}" class="sidebar-link">
                <i class="bi bi-person-fill-gear"></i>
                <span>Profile</span>
            </a>
        </li>
        <li class="sidebar-item">
            <form action="{{ route('logout') }}" method="POST" id="logout-form">
                @csrf
            </form>
            <a href="#" class="sidebar-link" id="logout">
                <i class="bi bi-box-arrow-right"></i>
                <span>Keluar</span>
            </a>
        </li>
    </ul>
</div>
