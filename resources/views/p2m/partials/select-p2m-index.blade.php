<div class="row justify-content-center mb-10">
    <div class="col-12">
        <div class="card shadow-lg px-5 py-3">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-2">Kegiatan P2M:</div>
                    <div class="col-4">
                        <select class="form-select" id="p2m-select" aria-label="Default select example">
                            <option {{ Route::is('p2m.index') ? 'selected' : '' }} data-url="{{ route('p2m.index') }}">pilih kegiatan P2M</option>
                            <option {{ Route::is('p2m.sosialisasi.*') ? 'selected' : '' }} data-url="{{ route('p2m.sosialisasi.index') }}">Sosialisasi Tatap Muka/Konvensional</option>
                            <option {{ Route::is('p2m.cfd.*') ? 'selected' : '' }} data-url="{{ route('p2m.cfd.index') }}">Sosialisasi di lokasi Car Free Days (CFD)</option>
                            <option {{ Route::is('p2m.elektronik.*') ? 'selected' : '' }} data-url="{{ route('p2m.elektronik.create') }}">Informasi dan Edukasi Melalui Media Elektronik</option>
                            <option {{ Route::is('p2m.online.*') ? 'selected' : '' }} data-url="{{ route('p2m.online.create') }}">Informasi dan Edukasi Melalui Media Online</option>
                        </select>
                    </div>
                    <div class="col">
                        <div class="d-flex justify-content-end">
                            @if (! Route::is('p2m.index'))
                                @php
                                    $baseRoute = Str::beforeLast(Route::currentRouteName(), '.');
                                @endphp
                                <a href="{{ route($baseRoute . '.create') }}" class="btn btn-primary">
                                    Tambah Data
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
    
    const p2mSelect = document.getElementById("p2m-select")
    p2mSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const targetUrl = selectedOption.getAttribute('data-url');
        if (targetUrl == window.location.href) return
        
        window.location.href = targetUrl
    })

</script>
@endsection