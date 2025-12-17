<div class="card border-0 shadow-sm mb-4 rounded-4 overflow-hidden">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            
            {{-- Bagian Kiri: Label & Select --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 flex-grow-1">
                <div class="d-flex align-items-center gap-2 text-primary">
                    <i class="bi bi-grid-fill fs-4"></i>
                    <h5 class="mb-0 fw-bold text-nowrap">Menu P2M</h5>
                </div>
                
                <div class="w-100" style="max-width: 500px;">
                    <select class="form-select border-secondary-subtle form-select-lg fs-6" id="p2m-select" aria-label="Pilih Kegiatan">
                        <option {{ Route::is('p2m.index') ? 'selected' : '' }} data-url="{{ route('p2m.index') }}">-- Pilih Jenis Kegiatan --</option>
                        <option {{ Route::is('p2m.sosialisasi.*') ? 'selected' : '' }} data-url="{{ route('p2m.sosialisasi.index') }}">Sosialisasi Tatap Muka/Konvensional</option>
                        <option {{ Route::is('p2m.upacara.*') ? 'selected' : '' }} data-url="{{ route('p2m.upacara.index') }}">Sosialisasi Pembina Upacara</option>
                        <option {{ Route::is('p2m.kie.*') ? 'selected' : '' }} data-url="{{ route('p2m.kie.index') }}">KIE Keliling</option>
                        <option {{ Route::is('p2m.lingkungan.*') ? 'selected' : '' }} data-url="{{ route('p2m.lingkungan.index') }}">Lingkungan Bersinar</option>
                        <option {{ Route::is('p2m.cfd.*') ? 'selected' : '' }} data-url="{{ route('p2m.cfd.index') }}">Sosialisasi Car Free Days (CFD)</option>
                        <option {{ Route::is('p2m.elektronik.*') ? 'selected' : '' }} data-url="{{ route('p2m.elektronik.index') }}">Media Elektronik</option>
                        <option {{ Route::is('p2m.media_non_elektronik.*') ? 'selected' : '' }} data-url="{{ route('p2m.media_non_elektronik.index') }}">Media Non Elektronik</option>
                        <option {{ Route::is('p2m.online.*') ? 'selected' : '' }} data-url="{{ route('p2m.online.index') }}">Media Online</option>
                        <option {{ Route::is('p2m.tes_urine.*') ? 'selected' : '' }} data-url="{{ route('p2m.tes_urine.index') }}">Test Urine</option>
                        <option {{ Route::is('p2m.desa_bersinar.*') ? 'selected' : '' }} data-url="{{ route('p2m.desa_bersinar.index') }}">Desa Bersinar</option>
                        <option {{ Route::is('p2m.safari_religi.*') ? 'selected' : '' }} data-url="{{ route('p2m.safari_religi.index') }}">Safari Religi</option>
                    </select>
                </div>
            </div>

            {{-- Bagian Kanan: Tombol Tambah --}}
            <div class="d-flex justify-content-end">
                @if (! Route::is('p2m.index') and auth()->user()->hasRole('operator'))
                    @php
                        $baseRoute = Str::beforeLast(Route::currentRouteName(), '.');
                    @endphp
                    <a href="{{ route($baseRoute . '.create') }}" class="btn btn-primary btn-lg fs-6 px-4 rounded-pill shadow-sm d-flex align-items-center gap-2">
                        <i class="bi bi-plus-lg"></i>
                        <span>Tambah Data</span>
                    </a>
                @endif
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script>
    const p2mSelect = document.getElementById("p2m-select")
    if(p2mSelect){
        p2mSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const targetUrl = selectedOption.getAttribute('data-url');
            if (targetUrl && targetUrl !== window.location.href) {
                window.location.href = targetUrl;
            }
        });
    }
</script>
@endpush