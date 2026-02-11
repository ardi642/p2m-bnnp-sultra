@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            <div class="row justify-content-center mb-4">
                <div class="col-12 col-lg-10">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-1 fw-bold text-dark">Input Kegiatan P2M</h1>
                            <p class="text-muted mb-0">Kegiatan Media Online</p>
                        </div>
                        <a href="{{ route('p2m.online.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
            
            {{-- @include('p2m.partials.select-p2m-create') --}}

            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="card border-0 shadow-lg">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="card-title mb-0 fw-bold">Form Input Data</h5>
                        </div>


                        <div class="card-body p-4 p-lg-5">
                            
                            <form action="{{ route('p2m.online.store') }}" 
                                  method="POST" 
                                  enctype="multipart/form-data" 
                                  id="form-create">
                                @csrf
                                
                                {{-- SECTION 1 --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">
                                    Data Pelaksanaan
                                </h6>

                                <div class="row g-4 mb-5">
                                    
                                    {{-- Satker --}}
                                    @if (auth()->user()->isAdmin())     
                                        <div class="col-12 col-lg-6">
                                            <label class="form-label fw-semibold text-secondary small">
                                                Satuan Kerja <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select @error('satuan_kerja_id') is-invalid @enderror" 
                                                    name="satuan_kerja_id">
                                                <option value="" selected disabled>-- Pilih Satuan Kerja --</option>
                                                @foreach ($satuanKerjas as $satuanKerja)
                                                    <option value="{{ $satuanKerja->id }}" @selected(old('satuan_kerja_id') == $satuanKerja->id)>{{ $satuanKerja->satuan_kerja }}</option>
                                                @endforeach
                                            </select>
                                            @error('satuan_kerja_id') 
                                                <div class="invalid-feedback">{{ $message }}</div> 
                                            @enderror
                                        </div>
                                    @endif

                                    {{-- Anggaran --}}
                                    <div class="col-12 col-lg-{{ auth()->user()->isAdmin() ? '6' : '12' }}">
                                        <label class="form-label fw-semibold text-secondary small">
                                            Sumber Anggaran <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('anggaran_pelaksanaan') is-invalid @enderror" 
                                                name="anggaran_pelaksanaan">
                                            <option value="" disabled selected>-- Pilih Sumber --</option>
                                            <option value="DIPA" @selected(old('anggaran_pelaksanaan') == 'DIPA')>DIPA</option>
                                            <option value="NON DIPA" @selected(old('anggaran_pelaksanaan') == 'NON DIPA')>NON DIPA</option>
                                        </select>
                                        @error('anggaran_pelaksanaan') 
                                            <div class="invalid-feedback">{{ $message }}</div> 
                                        @enderror
                                    </div>

                                    {{-- Jenis Media --}}
                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-secondary small">
                                            Jenis Media <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('jenis_media') is-invalid @enderror" 
                                                name="jenis_media">
                                            <option value="" selected disabled>-- Pilih Jenis Media --</option>
                                            @foreach($mediaOptions as $key => $label)
                                                <option value="{{ $key }}" @selected(old('jenis_media') == $key)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('jenis_media') 
                                            <div class="invalid-feedback">{{ $message }}</div> 
                                        @enderror
                                    </div>

                                    {{-- Nama Media --}}
                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-secondary small">
                                            Nama Media <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" 
                                               class="form-control @error('nama_media') is-invalid @enderror" 
                                               name="nama_media" 
                                               value="{{ old('nama_media') }}" 
                                               placeholder="Masukkan nama media">
                                        @error('nama_media') 
                                            <div class="invalid-feedback">{{ $message }}</div> 
                                        @enderror
                                    </div>

                                    {{-- Tanggal --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">
                                            Tanggal Mulai Pelaksanaan <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" 
                                               class="form-control @error('tanggal_mulai_pelaksanaan') is-invalid @enderror" 
                                               name="tanggal_mulai_pelaksanaan" 
                                               value="{{ old('tanggal_mulai_pelaksanaan') }}">
                                        @error('tanggal_mulai_pelaksanaan') 
                                            <div class="invalid-feedback">{{ $message }}</div> 
                                        @enderror
                                    </div>

                                    {{-- Durasi --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">
                                            Durasi Pelaksanaan <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input type="number" 
                                                   class="form-control @error('durasi_pelaksanaan') is-invalid @enderror" 
                                                   name="durasi_pelaksanaan" 
                                                   value="{{ old('durasi_pelaksanaan') }}" 
                                                   placeholder="0">
                                            <span class="input-group-text bg-light text-secondary">Hari</span>
                                        </div>
                                        @error('durasi_pelaksanaan') 
                                            <div class="text-danger small mt-1">{{ $message }}</div> 
                                        @enderror
                                    </div>

                                </div>


                                {{-- SECTION 2 --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">
                                    Bukti Fisik
                                </h6>

                                <div class="row g-4 mb-4">

                                    {{-- ========================================== --}}
                                    {{-- AREA UPLOAD FILE & LINK (HYBRID) --}}
                                    {{-- ========================================== --}}
                                    <div class="col-12 mt-5">
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

                                <div class="d-flex flex-column-reverse flex-lg-row justify-content-end gap-2 pt-3 border-top mt-4">
                                    <button type="button" onclick="window.location.reload()" class="btn btn-light border text-secondary px-4">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                    </button>
                                    <button type="submit" id="btn-submit" class="btn btn-primary px-5 shadow-sm">
                                        <i class="bi bi-save me-1"></i> Simpan Data
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('styles')
    @vite(['resources/css/filepond.css'])
    <style>
        .dashed-border { border-style: dashed !important; border-width: 1px !important; }
        .ts-control { border: 1px solid #dee2e6; padding: 0.5rem 0.75rem; border-radius: 0.375rem; box-shadow: none; }
        .ts-control.focus { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
        .filepond--panel-root { background-color: #ffffff; border: 1px solid #dee2e6; }
        .border-dashed { border-style: dashed !important; border-width: 2px !important; }
        .filepond--item { width: 100%; }
    </style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
         // 2. FILEPOND MANAGER
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
            window.FilePondManager.attachFormSubmit('form-create', 'btn-submit');

        } else {
            console.error("FilePondManager belum dimuat. Pastikan 'npm run build' atau 'npm run dev' berjalan.");
        }
        
    });

    // 3. ALPINE JS LINK MANAGER
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