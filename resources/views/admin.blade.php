<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPANTAU SULTRA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/filepond.js'])
    @stack('styles')
</head>

{{-- 
    LOGIKA CLASS:
    - sidebar-closed: Dipakai Desktop untuk sembunyikan sidebar.
    - sidebar-open: Dipakai Mobile untuk munculkan sidebar.
--}}
<body x-data 
      :class="{ 
          'sidebar-closed': !$store.layout.sidebarOpen, 
          'sidebar-open': $store.layout.sidebarOpen 
      }">

    {{-- Overlay Mobile --}}
    <div class="sidebar-overlay" @click="$store.layout.closeSidebar()"></div>

    <div class="admin-wrapper">
        @include('partials.sidebar')

        <div class="main-content">
            @include('partials.header')

            {{-- Langsung Yield Konten --}}
            @yield('content')

            @include('partials.footer')
        </div>
    </div>

    @stack('scripts')
    <script type="module">
        const initAlpine = () => {
            if (window.Alpine) { window.Alpine.start(); } 
            else { setTimeout(initAlpine, 50); }
        };
        initAlpine();
    </script>
</body>
</html>