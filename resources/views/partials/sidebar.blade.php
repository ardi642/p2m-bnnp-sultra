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
               class="nav-link dashboard-link d-flex align-items-center gap-3 {{ Request::is('dashboard*') ? 'active' : '' }}">
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
                    ['route' => 'p2m.elektronik.index', 'label' => 'Media Elektronik', 'url' => 'p2m/elektronik*'],
                    ['route' => 'p2m.non-elektronik.index', 'label' => 'Media Non-Elektronik', 'url' => 'p2m/non-elektronik*'],
                    ['route' => 'p2m.online.index', 'label' => 'Media Online', 'url' => 'p2m/online*'],
                    ['route' => 'p2m.desa-kelurahan-bersinar.index', 'label' => 'Desa / Kelurahan Bersinar', 'url' => 'p2m/desa-kelurahan-bersinar*'],
                    ['route' => 'p2m.asistensi-relawan.index', 'label' => 'Asistensi Relawan', 'url' => 'p2m/asistensi-relawan*'],
                    ['route' => 'p2m.pelatihan.index', 'label' => 'Pelatihan Soft Skill', 'url' => 'p2m/pelatihan*'],
                    ['route' => 'p2m.rts.index', 'label' => 'Remaja Teman Sebaya', 'url' => 'p2m/rts*'],
                    ['route' => 'p2m.keluarga.index', 'label' => 'Ketahanan Keluarga', 'url' => 'p2m/keluarga*'],
                    ['route' => 'p2m.ikan.index', 'label' => 'Integrasi Kurikulum Anti Narkotika', 'url' => 'p2m/ikan*'],
                    ['route' => 'p2m.tes-urine.index', 'label' => 'Tes Urine', 'url' => 'p2m/tes-urine*'],
                    ['route' => 'p2m.peran-serta-masyarakat.index', 'label' => 'Peran Serta Masyarakat', 'url' => 'p2m/peran-serta-masyarakat*'],
                    ['route' => 'p2m.pemberdayaan.index', 'label' => 'Pemberdayaan Alternatif', 'url' => 'p2m/pemberdayaan*'],
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
                    // ['route' => 'berantas.peta-ungkap-kasus.index', 'label' => 'Pemetaan Ungkap Kasus', 'url' => 'berantas/peta-ungkap-kasus*'],
                    ['route' => 'berantas.register-barang-bukti.index', 'label' => 'Barang Bukti', 'url' => 'berantas/register-barang-bukti*'],
                    // ['route' => 'berantas.peta-sebaran-bb.index', 'label' => 'Pemetaan Barang Bukti', 'url' => 'berantas/peta-sebaran-bb*'],
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
                @foreach([
                    ['route' => 'rehab.laporan.index', 'label' => 'Laporan Layanan', 'url' => 'rehab/laporan*'],
                    ['route' => 'rehab.pasien.index', 'label' => 'Pasien', 'url' => 'rehab/pasien*']
                ] as $menu)
                    <a href="{{ route($menu['route']) }}" class="nav-link {{ Request::is($menu['url']) ? 'active' : '' }}">
                        {{ $menu['label'] }}
                    </a>
                @endforeach
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
