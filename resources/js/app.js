import './bootstrap';

import 'bootstrap/dist/css/bootstrap.min.css';
import 'tom-select/dist/css/tom-select.bootstrap5.css';
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';

import * as bootstrap from 'bootstrap';
import Swal from 'sweetalert2';
import TomSelect from 'tom-select';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import ApexCharts from 'apexcharts';

window.bootstrap = bootstrap;
window.Swal = Swal;
window.TomSelect = TomSelect;
window.Alpine = Alpine;
window.ApexCharts = ApexCharts;

Alpine.plugin(collapse);

document.addEventListener('alpine:init', () => {
    Alpine.store('layout', {
        // Deteksi layar saat pertama load. 
        // Jika Desktop (>=992), TRUE. Jika Mobile, FALSE.
        sidebarOpen: window.innerWidth >= 992,

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },
        
        closeSidebar() {
            this.sidebarOpen = false;
        }
    });
});