<aside class="admin-sidebar shadow-sm bg-white">
    
    {{-- KONTEN NAVIGASI --}}
    {{-- Saya ubah pt-3 jadi pt-2 agar padding container tidak terlalu besar, 
         tapi kita mainkan margin di teks-nya saja --}}
    <div class="sidebar-content pt-2 px-2 flex-grow-1 overflow-auto">
        <nav class="nav flex-column gap-2">
            
            {{-- PERUBAHAN DISINI: Tambahkan 'mt-4' agar turun ke bawah --}}
            <div class="text-uppercase text-muted fw-bold px-3 mb-2 mt-4" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                Menu Utama
            </div>

            <a href="{{ route('dashboard') }}" class="nav-link rounded d-flex align-items-center gap-3 px-3 py-2 {{ Request::is('dashboard') ? 'bg-primary text-white shadow-sm' : 'text-secondary hover-bg-light' }}">
                <i class="bi bi-grid-fill fs-5"></i> <span>Dashboard</span>
            </a>

            <a href="{{ route('p2m.index') }}" class="nav-link rounded d-flex align-items-center gap-3 px-3 py-2 {{ Request::is('p2m*') ? 'bg-primary text-white shadow-sm' : 'text-secondary hover-bg-light' }}">
                <i class="bi bi-collection-fill fs-5"></i> <span>Kegiatan P2M</span>
            </a>

            @if (auth()->user()->hasRole(['admin', 'admin_satker']))
                {{-- Bagian Administrator juga dikasih jarak mt-4 biar konsisten --}}
                <div class="text-uppercase text-muted fw-bold px-3 mb-2 mt-4" style="font-size: 0.8rem; letter-spacing: 0.5px;">Administrator</div>

                <div x-data="{ open: {{ Request::is('admin*') ? 'true' : 'false' }} }">
                    <a href="#" class="nav-link rounded d-flex justify-content-between align-items-center px-3 py-2 text-secondary hover-bg-light" 
                       :class="open ? 'bg-light text-primary fw-bold' : ''"
                       @click.prevent="open = !open">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-database-fill fs-5"></i> <span>Master Data</span>
                        </div>
                        <i class="bi bi-chevron-down" style="font-size: 0.9rem;" :class="{ 'rotate-180': open }"></i>
                    </a>
                    
                    <ul class="nav flex-column ms-4 mt-1 ps-2 border-start" x-show="open" x-collapse>
                        <li class="nav-item">
                            <a href="{{ route('admin.pegawai.index') }}" class="nav-link py-2 px-3 {{ Request::is('admin/pegawai*') ? 'text-primary fw-bold' : 'text-muted' }}" style="font-size: 0.95rem;">
                                Data Pegawai
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.users.index') }}" class="nav-link py-2 px-3 {{ Request::is('admin/users*') ? 'text-primary fw-bold' : 'text-muted' }}" style="font-size: 0.95rem;">
                                Manajemen User
                            </a>
                        </li>
                    </ul>
                </div>
            @endif
        </nav>
    </div>
</aside>