<header class="admin-header shadow-sm bg-white">
    {{-- Padding disesuaikan agar tinggi header pas (sekitar 100-110px total height) --}}
    <div class="d-flex align-items-center justify-content-between w-100" style="padding: 12px 24px;">
        
        {{-- BAGIAN KIRI: LOGO - JUDUL - TOGGLE - INFO --}}
        <div class="d-flex align-items-center">
            
            {{-- 1. LOGO & JUDUL (PINDAHAN DARI SIDEBAR) --}}
            <div class="d-flex align-items-center gap-3 me-4">
                <img src="{{ asset('assets/logo-bnn.png') }}" width="75" alt="Logo">
                
                <div class="d-flex flex-column justify-content-center">
                    <span class="fw-bold text-primary h5 mb-0" style="line-height: 1; font-weight: 800 !important; white-space: nowrap;">
                        SIPANTAU SULTRA
                    </span>
                    <span class="text-secondary fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">
                        SISTEM PELAPORAN TERINTEGRASI
                    </span>
                </div>
            </div>

            {{-- 2. TOMBOL TOGGLE --}}
            <button class="btn btn-link text-dark p-0 me-3 text-decoration-none border-0" 
                    @click="$store.layout.toggleSidebar()" 
                    title="Menu">
                <i class="bi fs-3" :class="$store.layout.sidebarOpen ? 'bi-text-indent-right' : 'bi-text-indent-left'"></i>
            </button>
            
            {{-- Garis Pemisah --}}
            <div class="border-start mx-3 d-none d-lg-block" style="height: 40px; border-color: #d1d3e2 !important;"></div>

            {{-- 3. INFO HAK AKSES & SATKER --}}
            <div class="d-none d-lg-flex flex-column justify-content-center ms-2">
                @php
                    $user = auth()->user();
                    $roleClean = strtoupper(str_replace('_', ' ', $user->role));
                    
                    if($user->role === 'admin') {
                        $labelTeks = "HAK AKSES: Super Admin";
                        $labelColor = "text-danger"; 
                        $mainTeks = "MONITORING KESELURUHAN";
                    } else {
                        $labelTeks = "HAK AKSES: " . $roleClean;
                        $labelColor = ($user->role == 'admin_satker') ? "text-primary" : "text-success"; 
                        $mainTeks = $user->pegawai->satuanKerja->satuan_kerja ?? 'SATKER BELUM DISET';
                    }
                @endphp

                <small class="{{ $labelColor }} fw-bold text-uppercase mb-1" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                    {{ $labelTeks }}
                </small>
                
                <span class="fw-bold text-dark h6 mb-0 text-uppercase" style="line-height: 1;">
                    {{ $mainTeks }}
                </span>
            </div>
        </div>

        {{-- BAGIAN KANAN: USER PROFILE --}}
        <div class="dropdown">
            <button class="btn btn-link text-dark text-decoration-none dropdown-toggle d-flex align-items-center gap-3 border-0" data-bs-toggle="dropdown">
                
                <div class="d-none d-md-block text-end">
                    <span class="d-block fw-bold h6 mb-1" style="font-size: 1rem;">{{ auth()->user()->name }}</span>
                    <span class="d-block text-secondary fw-semibold" style="font-size: 0.8rem; line-height: 1;">
                        Kelola Akun
                    </span>
                </div>

            </button>
            
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2">
                <li><a class="dropdown-item py-2" href="{{ route('profile.edit') }}"><i class="bi bi-person-gear me-2"></i> Profil Saya</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                     <form action="{{ route('logout') }}" method="POST">
                        @csrf <button class="dropdown-item text-danger py-2">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>