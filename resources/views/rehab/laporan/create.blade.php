@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4">
        
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Input Laporan Harian</h4>
                <p class="text-secondary small mb-0">Input data realisasi Rawat Jalan, Pasca Rehab, dan SKHPN</p>
            </div>
            <a href="{{ route('rehab.laporan.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        {{-- ERROR ALERT --}}
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div><strong>Input Tidak Valid!</strong> Silakan periksa kolom yang berwarna merah.</div>
                </div>
            </div>
        @endif

        <form action="{{ route('rehab.laporan.store') }}" method="POST" enctype="multipart/form-data" id="form-rehab">
            @csrf

            <div class="row g-4">
                
                {{-- =================================== --}}
                {{-- BAGIAN TANGGAL & SATKER --}}
                {{-- =================================== --}}
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold m-0 text-primary"><i class="bi bi-calendar-event me-2"></i>Informasi Umum</h6>
                        </div>
                        <div class="card-body p-4">
                            
                            {{-- Input Tanggal --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-secondary">Tanggal Laporan <span class="text-danger">*</span></label>
                                <input type="date" 
                                    name="tanggal" 
                                    class="form-control @error('tanggal') is-invalid @enderror" 
                                    value="{{ old('tanggal', date('Y-m-d')) }}">
                                
                                {{-- Pesan Error --}}
                                @error('tanggal') 
                                    <div class="invalid-feedback">{{ $message }}</div> 
                                @enderror
                            </div>

                            {{-- Input Satker (Hanya Admin) --}}
                            @if(auth()->user()->isAdmin())
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-secondary">Satuan Kerja <span class="text-danger">*</span></label>
                                <select name="satuan_kerja_id" class="form-select @error('satuan_kerja_id') is-invalid @enderror">
                                    <option value="">-- Pilih Satker --</option>
                                    @foreach($satuanKerjas as $sk)
                                        <option value="{{ $sk->id }}" {{ old('satuan_kerja_id') == $sk->id ? 'selected' : '' }}>
                                            {{ $sk->satuan_kerja }}
                                        </option>
                                    @endforeach
                                </select>

                                {{-- Pesan Error --}}
                                @error('satuan_kerja_id') 
                                    <div class="invalid-feedback">{{ $message }}</div> 
                                @enderror
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- =================================== --}}
                {{-- BAGIAN DATA REALISASI (ANGKA) --}}
                {{-- =================================== --}}
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold m-0 text-success"><i class="bi bi-graph-up-arrow me-2"></i>Data Realisasi</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                
                                {{-- Input Rawat Jalan --}}
                                <div class="col-md-4">
                                    <div class="p-3 border rounded bg-info-subtle bg-opacity-10 text-center">
                                        <label class="form-label fw-bold text-info-emphasis mb-2">Rawat Jalan</label>
                                        <input type="number" 
                                            name="realisasi_rawat_jalan" 
                                            placeholder="Masukkan angka"
                                            class="form-control form-control-lg text-center fw-bold text-info-emphasis @error('realisasi_rawat_jalan') is-invalid @enderror" 
                                            value="{{ old('realisasi_rawat_jalan', 0) }}" 
                                            min="0">
                                        
                                        {{-- Pesan Error --}}
                                        @error('realisasi_rawat_jalan') 
                                            <div class="invalid-feedback d-block small mt-2">{{ $message }}</div> 
                                        @enderror
                                    </div>
                                </div>

                                {{-- Input Pasca Rehab --}}
                                <div class="col-md-4">
                                    <div class="p-3 border rounded bg-success-subtle bg-opacity-10 text-center">
                                        <label class="form-label fw-bold text-success mb-2">Pasca Rehab</label>
                                        <input type="number" 
                                            name="realisasi_pasca_rehab" 
                                            placeholder="Masukkan angka"
                                            class="form-control form-control-lg text-center fw-bold text-success @error('realisasi_pasca_rehab') is-invalid @enderror" 
                                            value="{{ old('realisasi_pasca_rehab', 0) }}" 
                                            min="0">
                                        
                                        {{-- Pesan Error --}}
                                        @error('realisasi_pasca_rehab') 
                                            <div class="invalid-feedback d-block small mt-2">{{ $message }}</div> 
                                        @enderror
                                    </div>
                                </div>

                                {{-- Input SKHPN --}}
                                <div class="col-md-4">
                                    <div class="p-3 border rounded bg-warning-subtle bg-opacity-10 text-center">
                                        <label class="form-label fw-bold text-warning-emphasis mb-2">SKHPN</label>
                                        <input type="number" 
                                            name="realisasi_skhpn" 
                                            placeholder="Masukkan angka"
                                            class="form-control form-control-lg text-center fw-bold text-warning-emphasis @error('realisasi_skhpn') is-invalid @enderror" 
                                            value="{{ old('realisasi_skhpn', 0) }}" 
                                            min="0">
                                        
                                        {{-- Pesan Error --}}
                                        @error('realisasi_skhpn') 
                                            <div class="invalid-feedback d-block small mt-2">{{ $message }}</div> 
                                        @enderror
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                {{-- ========================================== --}}
                {{-- AREA UPLOAD FILE & LINK (HYBRID) --}}
                {{-- ========================================== --}}
                <div class="col-12 mb-4">
                    <div class="bg-light p-4 rounded-3 border border-dashed">
                        
                        <label class="form-label fw-bold h6 mb-3 text-dark d-block border-bottom pb-2">
                            <i class="bi bi-cloud-arrow-up me-2"></i>Upload File & Link (Opsional)
                        </label>
                        
                        <div class="row g-3">
                            
                            {{-- KOLOM KIRI: DOKUMENTASI --}}
                            <div class="col-12 col-md-6">
                                <div class="bg-white p-3 rounded border h-100 d-flex flex-column shadow-sm">
                                    <label class="form-label fw-bold small text-primary mb-1">
                                        <i class="bi bi-folder2-open me-2"></i>Dokumentasi
                                    </label>
                                    
                                    {{-- 1. File Upload --}}
                                    <div class="mb-3">
                                        <p class="text-muted small mb-2" style="font-size: 0.75rem">Upload dokumentasi. Maksimal 10MB.</p>
                                        <input type="file" id="fp-dokumentasi" name="dokumentasi[]" multiple>
                                        @error('dokumentasi') <div class="text-danger small">{{ $message }}</div> @enderror
                                    </div>

                                    <hr class="border-secondary-subtle my-3">

                                    {{-- 2. Link Input (Alpine) --}}
                                    <div x-data="linkManager( {{ \Illuminate\Support\Js::from(array_values(old('dokumentasi_links', []))) }} )">
                                        <label class="form-label fw-bold small text-primary mb-2">
                                            <i class="bi bi-link-45deg me-1"></i>Atau Tautkan Link
                                        </label>
                                        
                                        <template x-for="(link, index) in links" :key="index">
                                            <div class="input-group mb-2 input-group-sm">
                                                <input type="text" class="form-control" 
                                                        :name="`dokumentasi_links[${index}][nama]`" 
                                                        placeholder="Nama Tautan / File" 
                                                        x-model="link.nama" required>
                                                
                                                <input type="url" class="form-control" 
                                                        :name="`dokumentasi_links[${index}][url]`" 
                                                        placeholder="https://" 
                                                        x-model="link.url" required>
                                                
                                                <button type="button" class="btn btn-outline-danger" @click="removeLink(index)">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                        </template>
                                        
                                        @error('dokumentasi_links.*') 
                                            <div class="text-danger small mb-2">Pastikan nama dan URL diisi dengan benar.</div> 
                                        @enderror

                                        <button type="button" class="btn btn-xs btn-outline-primary dashed-border w-100 mt-1" @click="addLink()">
                                            <i class="bi bi-plus-circle me-1"></i> Tambah Link
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- KOLOM KANAN: LAMPIRAN --}}
                            <div class="col-12 col-md-6">
                                <div class="bg-white p-3 rounded border h-100 d-flex flex-column shadow-sm">
                                    <label class="form-label fw-bold small text-danger mb-1">
                                        <i class="bi bi-paperclip me-2"></i>Lampiran Pendukung
                                    </label>
                                    
                                    {{-- 1. File Upload --}}
                                    <div class="mb-3">
                                        <p class="text-muted small mb-2" style="font-size: 0.75rem">Upload file pendukung. Maksimal 10MB.</p>
                                        <input type="file" id="fp-lampiran" name="lampiran[]" multiple>
                                        @error('lampiran') <div class="text-danger small">{{ $message }}</div> @enderror
                                    </div>

                                    <hr class="border-secondary-subtle my-3">

                                    {{-- 2. Link Input (Alpine) --}}
                                    <div x-data="linkManager( {{ \Illuminate\Support\Js::from(array_values(old('lampiran_links', []))) }} )">
                                        <label class="form-label fw-bold small text-danger mb-2">
                                            <i class="bi bi-link-45deg me-1"></i>Atau Tautkan Link
                                        </label>
                                        
                                        <template x-for="(link, index) in links" :key="index">
                                            <div class="input-group mb-2 input-group-sm">
                                                <input type="text" class="form-control" 
                                                        :name="`lampiran_links[${index}][nama]`" 
                                                        placeholder="Nama Tautan / File" 
                                                        x-model="link.nama" required>
                                                
                                                <input type="url" class="form-control" 
                                                        :name="`lampiran_links[${index}][url]`" 
                                                        placeholder="https://" 
                                                        x-model="link.url" required>
                                                
                                                <button type="button" class="btn btn-outline-danger" @click="removeLink(index)">
                                                    <i class="bi bi-x"></i>
                                                </button>
                                            </div>
                                        </template>

                                        @error('lampiran_links.*') 
                                            <div class="text-danger small mb-2">Pastikan nama dan URL diisi dengan benar.</div> 
                                        @enderror

                                        <button type="button" class="btn btn-xs btn-outline-danger dashed-border w-100 mt-1" @click="addLink()">
                                            <i class="bi bi-plus-circle me-1"></i> Tambah Link
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div> 
                    </div>
                </div>

            </div>

            <div class="d-flex justify-content-end gap-2 pb-5">
                <button type="button" onclick="window.location.reload()" class="btn btn-light border px-4 py-2">Reset Form</button>
                <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">Simpan Laporan</button>
            </div>

        </form>
    </div>
</main>
@endsection

@push('styles')
    @vite(['resources/css/filepond.css', 'resources/js/filepond.js'])
    <style>
        .border-dashed { border: 1px dashed #ced4da !important; }
        .filepond--panel-root { background-color: #f8f9fa; border: 1px solid #ced4da; }
    </style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {

        // FILEPOND MANAGER
        const commonConfig = {
            uploadRoute: '{{ route('upload.temp') }}',
            revertRoute: '{{ route('revert.temp') }}',
            loadRoute:   '{{ route('load.temp') }}',
            csrfToken:   '{{ csrf_token() }}',
            submitBtnId: 'btn-submit'
        };

        if (window.FilePondManager) {
            
            // A. Init Dokumentasi (Format Bebas, Max 10MB)
            window.FilePondManager.create('#fp-dokumentasi', {
                ...commonConfig,
                maxSize: '10MB',
                existingFiles: @json(old('dokumentasi', [])),
            });

            // B. Init Lampiran Pendukung (Format Bebas, Max 10MB)
            window.FilePondManager.create('#fp-lampiran', {
                ...commonConfig,
                maxSize: '10MB',
                existingFiles: @json(old('lampiran', [])),
            });

            // C. Validasi Submit
            window.FilePondManager.attachFormSubmit('form-rehab', 'btn-submit');

        } else {
            console.error("FilePondManager belum dimuat. Pastikan 'npm run build' atau 'npm run dev' berjalan.");
        }
    });

    // ALPINE JS LINK MANAGER
    document.addEventListener('alpine:init', () => {
        Alpine.data('linkManager', (initialData = []) => ({
            links: Array.isArray(initialData) ? initialData : [], 
            addLink() {
                this.links.push({ nama: '', url: '' });
            },
            removeLink(index) {
                this.links.splice(index, 1);
            }
        }));
    });

</script>
@endpush