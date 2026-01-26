import './bootstrap';

// 1. IMPORT CSS LIBRARY DI SINI (AGAR VITE BISA BACA)
import 'bootstrap/dist/css/bootstrap.min.css';
import 'tom-select/dist/css/tom-select.bootstrap5.css';
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';

// 2. Import JS Library
import * as bootstrap from 'bootstrap';
import Swal from 'sweetalert2';
import TomSelect from 'tom-select';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import ApexCharts from 'apexcharts';

// ... (sisanya sama seperti kode sebelumnya)
window.bootstrap = bootstrap;
window.Swal = Swal;
window.TomSelect = TomSelect;
window.Alpine = Alpine;
window.ApexCharts = ApexCharts;

Alpine.plugin(collapse);

document.addEventListener('alpine:init', () => {
    Alpine.store('layout', {
        // LOGIKA BARU:
        // Jika layar >= 992px (Desktop), default TRUE (Terbuka).
        // Jika layar < 992px (Mobile), default FALSE (Tertutup).
        sidebarOpen: window.innerWidth >= 992,

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },
        
        closeSidebar() {
            this.sidebarOpen = false;
        }
    });
});