        <!-- Sidebar -->
        <aside class="admin-sidebar" id="admin-sidebar">
            <div class="sidebar-content">
                <nav class="sidebar-nav">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ Route::is('dashboard') ? 'active' : '' }}"
                                href="{{ route('dashboard') }}">
                                <i class="bi bi-speedometer2"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            {{-- <a class="nav-link {{ Route::is('p2m.*') ? 'active' : '' }}" href="./analytics.html"> --}}
                            <a class="nav-link {{ Route::is('p2m.*') ? 'active' : '' }}" href="{{ route('p2m.index') }}">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <span>P2M</span>
                                </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Floating Hamburger Menu -->
        <button class="hamburger-menu" type="button" data-sidebar-toggle aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>
