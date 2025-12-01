<div class="row justify-content-center mb-10">
    <div class="col-12 col-lg-10">
        <div class="card shadow-lg px-5 py-3">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-12 col-lg-2 ">Kegiatan P2M:</div>
                    <div class="col-12 col-lg-4">
                        <select class="form-select" id="p2m-select" aria-label="Default select example">
                            <option disabled>Pilih Kegiatan P2M</option>
                            <option {{ Route::is('p2m.sosialisasi.*') ? 'selected' : '' }} data-url="{{ route('p2m.sosialisasi.create') }}">Sosialisasi Tatap Muka/Konvensional</option>
<<<<<<< HEAD
                            <option {{ Route::is('p2m.upacara.*') ? 'selected' : '' }} data-url="{{ route('p2m.upacara.create') }}">Sosialisasi sebagai Pembina Upacara</option>
                            <option {{ Route::is('p2m.kie.*') ? 'selected' : '' }} data-url="{{ route('p2m.kie.create') }}">KIE (Kegiatan Informasi dan Edukasi) Keliling</option>
                            <option {{ Route::is('p2m.lingkungan.*') ? 'selected' : '' }} data-url="{{ route('p2m.lingkungan.create') }}">Lingkungan Bersinar</option>
=======
                            <option {{ Route::is('p2m.cfd.*') ? 'selected' : '' }} data-url="{{ route('p2m.cfd.create') }}">Sosialisasi di lokasi Car Free Days (CFD)</option>
                            <option {{ Route::is('p2m.elektronik.*') ? 'selected' : '' }} data-url="{{ route('p2m.elektronik.create') }}">Informasi dan Edukasi Melalui Media Elektronik</option>
                            <option {{ Route::is('p2m.online.*') ? 'selected' : '' }} data-url="{{ route('p2m.online.create') }}">Informasi dan Edukasi Melalui Media Online</option>
>>>>>>> origin/feature/input-p2m-akbar
                        </select>
                    </div>
                    <div class="col-12 col-lg">
                        <div class="d-flex justify-content-lg-end">
                                @php
                                    $baseRoute = Str::beforeLast(Route::currentRouteName(), '.');
                                @endphp
                                <a href="{{ route($baseRoute . '.index') }}" class="btn btn-primary">
                                    Lihat data kegiatan
                                </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    
    const p2mSelect = document.getElementById("p2m-select")
    p2mSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const targetUrl = selectedOption.getAttribute('data-url');
        if (targetUrl == window.location.href) return
        
        window.location.href = targetUrl
    })

</script>
@endpush