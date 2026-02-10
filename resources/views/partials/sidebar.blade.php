<aside class="admin-sidebar bg-white"
       x-data
       x-init="
           // FITUR PENTING: Mengembalikan posisi scroll dari memori browser
           // agar saat klik menu, sidebar tidak kembali ke atas.
           $el.scrollTop = localStorage.getItem('sidebar_scroll_pos') || 0;
           
           // Simpan posisi setiap kali user melakukan scroll
           $el.addEventListener('scroll', () => {
               localStorage.setItem('sidebar_scroll_pos', $el.scrollTop);
           });
       ">
       
    <div class="sidebar-content py-3">
        <nav class="nav flex-column">
            
            {{-- DASHBOARD --}}
            <a href="{{ route('dashboard.index') }}" 
               class="nav-link dashboard-link d-flex align-items-center gap-3 {{ Request::is('/') ? 'active' : '' }}">
                <i class="bi bi-grid-fill fs-5"></i> 
                <span>Dashboard</span>
            </a>

            {{-- 1. BIDANG P2M --}}
            @if(auth()->user()->hasRole(['admin', 'admin_satker', 'admin_p2m', 'operator_satker', 'operator_p2m']))
                <div class="sidebar-section-header">
                    <i class="bi bi-megaphone-fill fs-5"></i>
                    <span>Bidang P2M</span>
                </div>

                @foreach([
                    ['route' => 'p2m.informasi-edukasi.index', 'label' => 'Informasi dan Edukasi', 'url' => 'p2m/informasi-edukasi*'],
                    // ['route' => 'p2m.upacara.index', 'label' => 'Upacara', 'url' => 'p2m/upacara*'],
                    // ['route' => 'p2m.kie.index', 'label' => 'KIE', 'url' => 'p2m/kie*'],
                    // ['route' => 'p2m.lingkungan-bersinar.index', 'label' => 'Lingkungan Bersinar', 'url' => 'p2m/lingkungan-bersinar*'],
                    ['route' => 'p2m.cfd.index', 'label' => 'Car Free Day', 'url' => 'p2m/cfd*'],
                    ['route' => 'p2m.elektronik.index', 'label' => 'Media Elektronik', 'url' => 'p2m/elektronik*'],
                    ['route' => 'p2m.non-elektronik.index', 'label' => 'Media Non-Elektronik', 'url' => 'p2m/non-elektronik*'],
                    ['route' => 'p2m.online.index', 'label' => 'Media Online', 'url' => 'p2m/online*'],
                    ['route' => 'p2m.tes-urine.index', 'label' => 'Tes Urine', 'url' => 'p2m/tes-urine*'],
                    ['route' => 'p2m.desa-kelurahan-bersinar.index', 'label' => 'Desa / Kelurahan Bersinar', 'url' => 'p2m/desa-kelurahan-bersinar*'],
                    ['route' => 'p2m.safari-religi.index', 'label' => 'Safari Religi', 'url' => 'p2m/safari-religi*'],
                ] as $menu)
                    <a href="{{ route($menu['route']) }}" class="nav-link {{ Request::is($menu['url']) ? 'active' : '' }}">
                        {{ $menu['label'] }}
                    </a>
                @endforeach
            @endif

            {{-- 2. BIDANG BERANTAS --}}
            @if(auth()->user()->hasRole(['admin', 'admin_satker', 'admin_berantas', 'operator_satker', 'operator_berantas']))
                <div class="sidebar-section-header">
                    <i class="bi bi-shield-lock-fill fs-5"></i>
                    <span>Bidang Berantas</span>
                </div>

                @foreach([
                    ['route' => 'berantas.narkotika.index', 'label' => 'Data Narkotika', 'url' => 'berantas/narkotika*'],
                    ['route' => 'berantas.tat.index', 'label' => 'Tim Asesmen (TAT)', 'url' => 'berantas/tat*'],
                    ['route' => 'berantas.ungkap-kasus.index', 'label' => 'Ungkap Kasus', 'url' => 'berantas/ungkap-kasus*'],
                    ['route' => 'berantas.register-barang-bukti.index', 'label' => 'Barang Bukti', 'url' => 'berantas/register-barang-bukti*'],
                ] as $menu)
                    <a href="{{ route($menu['route']) }}" class="nav-link {{ Request::is($menu['url']) ? 'active' : '' }}">
                        {{ $menu['label'] }}
                    </a>
                @endforeach
            @endif

            {{-- 3. BIDANG REHAB --}}
            @if(auth()->user()->hasRole(['admin', 'admin_satker', 'admin_rehab', 'operator_satker', 'operator_rehab']))
                <div class="sidebar-section-header">
                    <i class="bi bi-heart-pulse-fill fs-5"></i>
                    <span>Bidang Rehab</span>
                </div>

                <a href="{{ route('rehab.laporan.index') }}" class="nav-link {{ Request::is('rehab/laporan*') ? 'active' : '' }}">
                    Laporan Bulanan
                </a>
            @endif

            {{-- 4. ADMINISTRATOR --}}
            {{-- Role: admin, admin_satker, dan admin_bidang (p2m, berantas, rehab) --}}
            @if(auth()->user()->hasRole(['admin', 'admin_satker', 'admin_p2m', 'admin_berantas', 'admin_rehab']))
                <div class="sidebar-section-header mt-3">
                    <i class="bi bi-gear-fill fs-5"></i>
                    <span>Administrator</span>
                </div>

                {{-- Semua Admin di atas boleh melihat Data Pegawai --}}
                <a href="{{ route('admin.pegawai.index') }}" 
                   class="nav-link {{ Request::is('admin/pegawai*') ? 'active' : '' }}">
                    Data Pegawai
                </a>

                {{-- Semua Admin di atas boleh melakukan Manajemen User (reset password/create operator) --}}
                <a href="{{ route('admin.users.index') }}" 
                   class="nav-link {{ Request::is('admin/users*') ? 'active' : '' }}">
                    Manajemen User
                </a>
            @endif

            {{-- 5. AKUN PENGGUNA --}}
            <div class="sidebar-section-header mt-4 pt-3 border-top">
                <i class="bi bi-person-circle fs-5"></i>
                <span>Akun Pengguna</span>
            </div>

            <a href="{{ route('profile.edit') }}" class="nav-link {{ Request::is('profile*') ? 'active' : '' }}">
                Profil Saya
            </a>

            <form action="{{ route('logout') }}" method="POST" id="sidebar-logout-form">
                @csrf
            </form>
            
            <a href="#" class="nav-link text-danger" 
               onclick="event.preventDefault(); document.getElementById('sidebar-logout-form').submit();">
               <i class="bi bi-box-arrow-right me-2 d-inline-block"></i> Logout
            </a>

            <div class="mb-5"></div>
        </nav>
    </div>
</aside>