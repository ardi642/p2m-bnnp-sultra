<header class="admin-header shadow-sm">
    <div class="d-flex align-items-center gap-3">
        
        {{-- TOMBOL TOGGLE DINAMIS --}}
        <button class="btn-toggle" @click="$store.layout.toggleSidebar()" title="Toggle Sidebar">
            
            {{-- LOGIKA ICON: --}}
            {{-- Base class: 'bi' dan 'fs-4' --}}
            {{-- Alpine Logic (:class): --}}
            {{-- Jika Open? Pakai 'bi-arrow-left' (<-) --}}
            {{-- Jika Close? Pakai 'bi-list' (Menu) --}}
            
            <i class="bi fs-4" 
               :class="$store.layout.sidebarOpen ? 'bi-arrow-left' : 'bi-list'">
            </i>

        </button>
        
        <h5 class="mb-0 fw-semibold text-secondary d-none d-md-block">Dashboard Panel</h5>
    </div>

    {{-- User Dropdown tetap sama --}}
    <div class="dropdown">
        <button class="btn btn-link text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
            {{ auth()->user()->name ?? 'User' }}
        </button>
        <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
            <li>
                 <form action="{{ route('logout') }}" method="POST">
                    @csrf <button class="dropdown-item text-danger">Logout</button>
                </form>
            </li>
        </ul>
    </div>
</header>