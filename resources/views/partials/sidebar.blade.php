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
                            <a class="nav-link {{ Route::is('p2m.*') ? 'active' : '' }}" href="./analytics.html">
                                <i class="bi bi-file-earmark-text"></i>
                                <span>Kegiatan P2M</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link collapse show" href="#" data-bs-toggle="collapse" data-bs-target="#dataMaster" aria-expanded="true" style="">
                                <i class="bi bi-puzzle"></i>
                                <span>Data Master </span>
                                <i class="bi bi-chevron-down ms-auto"></i>
                            </a>
                            <div class="collapse" id="dataMaster">
                                <ul class="nav nav-submenu">
                                    <li class="nav-item">
                                        <a class="nav-link" href="./elements.html">
                                            <i class="bi bi-grid"></i>
                                            <span>Data Pegawai</span>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="./elements-buttons.html">
                                            <i class="bi bi-square"></i>
                                            <span>Satuan Kerja</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link " href="./analytics.html">
                                <i class="bi bi-file-earmark-text"></i>
                                <span>Manajemen User</span>
                            </a>
                        </li>
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