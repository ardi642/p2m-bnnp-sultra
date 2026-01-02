<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    
    @vite([
        'resources/css/app.css', 
        'resources/js/app.js', 
        'resources/js/filepond.js'
    ])

    @stack('styles')
</head>

{{-- Bind class 'sidebar-open' ke Alpine Store --}}
<body x-data :class="{ 'sidebar-open': $store.layout.sidebarOpen }">

    <div class="sidebar-overlay" @click="$store.layout.closeSidebar()"></div>

    <div class="admin-wrapper">
        @include('partials.sidebar')

        <div class="main-content">
            
            @include('partials.header')

            {{-- PERBAIKAN: Langsung yield, tanpa wrapper main tambahan --}}
            {{-- View Anda (create.blade.php) akan merender <main class="admin-main"> di sini --}}
            @yield('content')

            @include('partials.footer')
            
        </div>
    </div>

    @stack('scripts')

    <script type="module">
        // Kita gunakan type="module" agar dia berjalan di antrean terakhir
        // Fungsi ini akan terus mengecek apakah Alpine sudah siap
        
        const initAlpine = () => {
            if (window.Alpine) {
                // Cek apakah Alpine sudah jalan? Jika belum, jalankan.
                // Kita cek properti internal Alpine atau sekedar try-catch aman
                console.log('🚀 Starting Alpine manually...');
                window.Alpine.start();
            } else {
                // Jika app.js belum selesai load, tunggu sebentar
                setTimeout(initAlpine, 50);
            }
        };

        initAlpine();
    </script>
</body>
</html>