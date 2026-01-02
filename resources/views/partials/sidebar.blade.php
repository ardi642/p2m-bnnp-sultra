<aside class="admin-sidebar shadow-sm">
    <div class="sidebar-header">
        <a href="/" class="text-decoration-none d-flex align-items-center gap-2 text-dark">
            {{-- Ganti src dengan logo Anda --}}
            <img src="{{ asset('assets/logo-bnn.png') }}" width="32" alt="Logo">
            <span class="fw-bold h5 mb-0 text-primary">BNNP Sultra</span>
        </a>
    </div>

    <div class="sidebar-content">
        <nav class="nav flex-column">
            
            {{-- 1. DASHBOARD (Semua User) --}}
            <div class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ Request::is('dashboard') ? 'active' : '' }}">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-speedometer2 icon-main"></i> <span>Dashboard</span>
                    </div>
                </a>
            </div>

            {{-- 2. KEGIATAN P2M (Semua User) --}}
            <div class="nav-item">
                <a href="{{ route('p2m.index') }}" class="nav-link {{ Request::is('p2m*') ? 'active' : '' }}">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-file-earmark-text icon-main"></i> <span>Kegiatan P2M</span>
                    </div>
                </a>
            </div>

            {{-- 3. MASTER DATA (Hanya Admin & Admin Satker) --}}
            {{-- Logika @if kita taruh di pembungkus dropdown ini --}}
            @if (auth()->user()->hasRole(['admin', 'admin_satker']))
                <div class="nav-item" x-data="{ open: {{ Request::is('admin*') ? 'true' : 'false' }} }">
                    
                    {{-- Parent Menu --}}
                    <a href="#" class="nav-link" @click.prevent="open = !open">
                        <div class="d-flex align-items-center">
                            {{-- Saya ganti icon jadi database agar lebih umum untuk 'Master Data' --}}
                            <i class="bi bi-database icon-main"></i> 
                            <span>Master Data</span>
                        </div>
                        {{-- Panah animasi --}}
                        <i class="bi bi-chevron-down arrow-icon" :class="{ 'arrow-rotate': open }"></i>
                    </a>
                    
                    {{-- Child Menu (Dropdown) --}}
                    <ul class="nav-treeview" x-show="open" x-collapse>
                        
                        {{-- Menu Pegawai --}}
                        <li class="nav-item">
                            <a href="{{ route('admin.pegawai.index') }}" class="nav-link {{ Request::is('admin/pegawai*') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill" style="font-size: 6px; margin-right: 10px;"></i>
                                <span>Manajemen Pegawai</span>
                            </a>
                        </li>

                        {{-- Menu User --}}
                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}" class="nav-link {{ Request::is('admin/users*') ? 'active' : '' }}">
                                <i class="bi bi-circle-fill" style="font-size: 6px; margin-right: 10px;"></i>
                                <span>Manajemen User</span>
                            </a>
                        </li>

                    </ul>
                </div>
            @endif
            {{-- End Logika @if --}}

        </nav>
    </div>
</aside>