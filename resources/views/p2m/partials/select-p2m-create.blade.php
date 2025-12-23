<div class="row justify-content-center mb-4">
    <div class="col-12 col-lg-10">
        <div class="card border shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    
                    {{-- Bagian Kiri: Label & Select --}}
                    <div class="d-flex align-items-center gap-3 w-100">
                        <label for="p2m-select" class="fw-bold text-secondary text-nowrap mb-0">
                            Jenis Kegiatan:
                        </label>
                        <select class="form-select fw-medium" id="p2m-select" style="cursor: pointer;">
                            <option disabled>Pilih Kategori Kegiatan...</option>
                            <option {{ Route::is('p2m.sosialisasi.*') ? 'selected' : '' }} data-url="{{ route('p2m.sosialisasi.create') }}">Sosialisasi Tatap Muka/Konvensional</option>
                            <option {{ Route::is('p2m.upacara.*') ? 'selected' : '' }} data-url="{{ route('p2m.upacara.create') }}">Sosialisasi Pembina Upacara</option>
                            <option {{ Route::is('p2m.kie.*') ? 'selected' : '' }} data-url="{{ route('p2m.kie.create') }}">KIE Keliling</option>
                            <option {{ Route::is('p2m.lingkungan-bersinar.*') ? 'selected' : '' }} data-url="{{ route('p2m.lingkungan-bersinar.create') }}">Lingkungan Bersinar</option>
                            <option {{ Route::is('p2m.cfd.*') ? 'selected' : '' }} data-url="{{ route('p2m.cfd.create') }}">Car Free Days (CFD)</option>
                            <option {{ Route::is('p2m.elektronik.*') ? 'selected' : '' }} data-url="{{ route('p2m.elektronik.create') }}">Media Elektronik</option>
                            <option {{ Route::is('p2m.non-elektronik.*') ? 'selected' : '' }} data-url="{{ route('p2m.non-elektronik.create') }}">Media Non Elektronik</option>
                            <option {{ Route::is('p2m.online.*') ? 'selected' : '' }} data-url="{{ route('p2m.online.create') }}">Media Online</option>
                            <option {{ Route::is('p2m.tes-urine.*') ? 'selected' : '' }} data-url="{{ route('p2m.tes-urine.create') }}">Tes Urine / Deteksi Dini</option>
                            <option {{ Route::is('p2m.desa-bersinar.*') ? 'selected' : '' }} data-url="{{ route('p2m.desa-bersinar.create') }}">Desa Bersinar</option>
                            <option {{ Route::is('p2m.safari-religi.*') ? 'selected' : '' }} data-url="{{ route('p2m.safari-religi.create') }}">Safari Religi</option>
                        </select>
                    </div>

                    {{-- Bagian Kanan: Tombol Lihat Data --}}
                    <div class="w-100 w-md-auto text-end">
                        @php
                            // Mengambil base route (misal: p2m.sosialisasi) untuk link ke index
                            $baseRoute = Str::beforeLast(Route::currentRouteName(), '.');
                        @endphp
                        <a href="{{ route($baseRoute . '.index') }}" class="btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2 text-nowrap">
                            <i class="bi bi-list-ul"></i> {{-- Ikon List --}}
                            <span>Lihat Data Kegiatan</span>
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById("p2m-select").addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const targetUrl = selectedOption.getAttribute('data-url');
        if (targetUrl && targetUrl !== window.location.href) {
            this.disabled = true; 
            window.location.href = targetUrl;
        }
    });
</script>
@endpush