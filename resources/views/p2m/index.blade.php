@extends('admin')
@section('content')
    <!-- Main Content -->
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Kegiatan P2M</h1>
                    <p class="text-muted mb-0">Master Data P2M</p>
                </div>
            </div>
            @include('p2m.partials.select-p2m-index')
        </div>
    </main>
@endsection

@push('scripts')
<script>
    
    const p2mSelect = document.getElementById("p2m-select")
    p2mSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const targetUrl = selectedOption.getAttribute('data-url');
        if (this.value == "" || targetUrl == window.location.href) return
        
        window.location.href = targetUrl
    })

</script>
@endpush