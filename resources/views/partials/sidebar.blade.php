        <!-- Sidebar -->
        <aside class="admin-sidebar" id="admin-sidebar">
            <div class="sidebar-content">
                <nav class="sidebar-nav">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="./index.html">
                                <i class="bi bi-speedometer2"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Route::is('p2m.*') ? 'active' : '' }}" href="{{ route('p2m.index') }}">
                                <i class="bi bi-file-earmark-text"></i>
                                <span>Kegiatan P2M</span>
                            </a>
                        </li>
                        @if (auth()->user()->hasRole(['admin', 'admin_satker']))    
                        <li class="nav-item">
                            <a class="nav-link {{ Route::is('admin.pegawai.*') ? 'active' : '' }}" href="{{ route('admin.pegawai.index') }}">
                                <i class="bi bi-file-earmark-text"></i>
                                <span>Manajemen Pegawai</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ Route::is('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                <i class="bi bi-file-earmark-text"></i>
                                <span>Manajemen User</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Floating Hamburger Menu -->
        <button class="hamburger-menu" 
                type="button" 
                data-sidebar-toggle
                aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>