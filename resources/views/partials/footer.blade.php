        <!-- Footer -->
        <footer class="admin-footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0 text-muted">© 2025 BNN Provinsi Sulawesi Tenggara</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p class="mb-0 text-muted">Build with ❤️ Magang Nasional (Maganghub) Kemnaker 2025</p>
                    </div>
                </div>
            </div>
        </footer>
        </div> <!-- /.admin-wrapper -->
    </div>

    <!-- Toast Container -->
    <div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3" style="z-index: 11">
        <div id="toast-container"></div>
    </div>


    <!-- Icon Demo Modal -->
    <div class="modal fade" id="iconDemoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-palette me-2"></i>
                        Icon System Demo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" x-data="iconDemo">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Current Provider: <span class="badge bg-primary" x-text="currentProvider"></span></h6>
                            <div class="btn-group" role="group">
                                <button type="button" 
                                        class="btn btn-outline-primary"
                                        @click="switchProvider('bootstrap')"
                                        :class="{ 'active': currentProvider === 'bootstrap' }">
                                    Bootstrap Icons
                                </button>
                                <button type="button" 
                                        class="btn btn-outline-primary"
                                        @click="switchProvider('lucide')"
                                        :class="{ 'active': currentProvider === 'lucide' }">
                                    Lucide Icons
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-3 text-center">
                            <div class="p-3 border rounded">
                                <i class="bi bi-speedometer2 icon-xl text-primary mb-2"></i>
                                <br><small>Dashboard</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3 border rounded">
                                <i class="bi bi-people icon-xl text-success mb-2"></i>
                                <br><small>Users</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3 border rounded">
                                <i class="bi bi-graph-up icon-xl text-info mb-2"></i>
                                <br><small>Analytics</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3 border rounded">
                                <i class="bi bi-gear icon-xl text-warning mb-2"></i>
                                <br><small>Settings</small>
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="mt-4">Icon Animations</h6>
                    <div class="row g-3">
                        <div class="col-md-3 text-center">
                            <i class="bi bi-arrow-clockwise icon-xl icon-spin text-primary"></i>
                            <br><small>Spin</small>
                        </div>
                        <div class="col-md-3 text-center">
                            <i class="bi bi-heart icon-xl icon-pulse text-danger"></i>
                            <br><small>Pulse</small>
                        </div>
                        <div class="col-md-3 text-center">
                            <i class="bi bi-star icon-xl icon-hover text-warning"></i>
                            <br><small>Hover Effect</small>
                        </div>
                        <div class="col-md-3 text-center">
                            <i class="bi bi-check-circle icon-xl text-success"></i>
                            <br><small>Static</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const toggleButton = document.querySelector('[data-sidebar-toggle]');
        const wrapper = document.getElementById('admin-wrapper');

        if (toggleButton && wrapper) {
          // Set initial state from localStorage
          const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
          if (isCollapsed) {
            wrapper.classList.add('sidebar-collapsed');
            toggleButton.classList.add('is-active');
          }

          // Attach click listener
          toggleButton.addEventListener('click', () => {
            const isCurrentlyCollapsed = wrapper.classList.contains('sidebar-collapsed');
            
            if (isCurrentlyCollapsed) {
              wrapper.classList.remove('sidebar-collapsed');
              toggleButton.classList.remove('is-active');
              localStorage.setItem('sidebar-collapsed', 'false');
            } else {
              wrapper.classList.add('sidebar-collapsed');
              toggleButton.classList.add('is-active');
              localStorage.setItem('sidebar-collapsed', 'true');
            }
          });
        }
      });
    </script>
    @yield('scripts')
</body>
</html> 