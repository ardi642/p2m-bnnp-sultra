@extends('admin')

@section('content')
<main class="admin-main" x-data="rehabEdit">
    <div class="container-fluid p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Edit Laporan Harian</h4>
                <p class="text-secondary small mb-0">Update data realisasi dan dokumentasi</p>
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

        <form action="{{ route('rehab.laporan.update', $laporan->id) }}" method="POST" enctype="multipart/form-data" id="form-rehab">
            @csrf
            @method('PUT')

            <div class="row g-4">
                {{-- INFO READONLY --}}
                <div class="col-12">
                    <div class="alert alert-light border shadow-sm d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-primary me-2">{{ $laporan->satuanKerja->satuan_kerja ?? 'Satker Tidak Dikenal' }}</span>
                            <span class="fw-bold text-dark"><i class="bi bi-calendar-event me-1"></i> {{ \Carbon\Carbon::parse($laporan->tanggal)->translatedFormat('l, d F Y') }}</span>
                        </div>
                        <div class="text-muted small">ID Laporan: #{{ $laporan->id }}</div>
                    </div>
                </div>

                {{-- DATA REALISASI --}}
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold m-0 text-success"><i class="bi bi-graph-up-arrow me-2"></i>Update Realisasi</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                {{-- Rawat Jalan --}}
                                <div class="col-md-4">
                                    <div class="p-3 border rounded bg-info-subtle bg-opacity-10 text-center">
                                        <label class="form-label fw-bold text-info-emphasis mb-2">Rawat Jalan</label>
                                        <input type="number" 
                                            name="realisasi_rawat_jalan" 
                                            placeholder="Masukkan angka"
                                            class="form-control form-control-lg text-center fw-bold text-info-emphasis @error('realisasi_rawat_jalan') is-invalid @enderror" 
                                            value="{{ old('realisasi_rawat_jalan', $laporan->realisasi_rawat_jalan) }}" 
                                            min="0">
                                        
                                        @error('realisasi_rawat_jalan') 
                                            <div class="invalid-feedback d-block small mt-2">{{ $message }}</div> 
                                        @enderror
                                    </div>
                                </div>

                                {{-- Pasca Rehab --}}
                                <div class="col-md-4">
                                    <div class="p-3 border rounded bg-success-subtle bg-opacity-10 text-center">
                                        <label class="form-label fw-bold text-success mb-2">Pasca Rehab</label>
                                        <input type="number" 
                                            name="realisasi_pasca_rehab" 
                                            placeholder="Masukkan angka"
                                            class="form-control form-control-lg text-center fw-bold text-success @error('realisasi_pasca_rehab') is-invalid @enderror" 
                                            value="{{ old('realisasi_pasca_rehab', $laporan->realisasi_pasca_rehab) }}" 
                                            min="0">
                                        
                                        @error('realisasi_pasca_rehab') 
                                            <div class="invalid-feedback d-block small mt-2">{{ $message }}</div> 
                                        @enderror
                                    </div>
                                </div>

                                {{-- SKHPN --}}
                                <div class="col-md-4">
                                    <div class="p-3 border rounded bg-warning-subtle bg-opacity-10 text-center">
                                        <label class="form-label fw-bold text-warning-emphasis mb-2">SKHPN</label>
                                        <input type="number" 
                                            name="realisasi_skhpn" 
                                            placeholder="Masukkan angka"
                                            class="form-control form-control-lg text-center fw-bold text-warning-emphasis @error('realisasi_skhpn') is-invalid @enderror" 
                                            value="{{ old('realisasi_skhpn', $laporan->realisasi_skhpn) }}" 
                                            min="0">
                                        
                                        @error('realisasi_skhpn') 
                                            <div class="invalid-feedback d-block small mt-2">{{ $message }}</div> 
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- AREA PENGELOLAAN FILE --}}
                <div class="col-12 mb-4">
                    
                    {{-- 1. KOTAK KHUSUS DOKUMENTASI LAMA --}}
                    @php
                        $oldFotos = $laporan->dokumen->where('kategori', 'dokumentasi');
                    @endphp
                    
                    @if($oldFotos->count() > 0)
                        <div class="card bg-light border border-dashed mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold text-primary mb-3">
                                    <i class="bi bi-images me-2"></i>Dokumentasi Tersimpan
                                </h6>
                                
                                <div class="row g-3">
                                    @foreach($oldFotos as $doc)
                                        {{-- INLINE CARD COMPONENT --}}
                                        @php $isMarkedDeleted = old('delete_files') && in_array($doc->id, old('delete_files')); @endphp
                                        <div class="col-6 col-md-4 col-lg-3 file-item" id="file-card-{{ $doc->id }}">
                                            <div class="card h-100 shadow-sm border border-secondary-subtle position-relative overflow-hidden file-card-inner transition-all {{ $isMarkedDeleted ? 'border-danger-subtle-thick' : '' }}">
                                                
                                                <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" style="background-color: rgba(255, 255, 255, 0.9); z-index: 5;">
                                                    <div class="text-danger mb-1"><i class="bi bi-trash3-fill fs-1"></i></div>
                                                    <span class="text-danger fw-bold small text-uppercase">Akan Dihapus</span>
                                                </div>

                                                {{-- PREVIEW AREA --}}
                                                <div class="ratio ratio-16x9 bg-secondary bg-opacity-10 border-bottom d-flex align-items-center justify-content-center overflow-hidden">
                                                    @if($doc->is_link)
                                                        <div class="text-info"><i class="bi bi-link-45deg display-4"></i></div>
                                                    @elseif(Str::contains($doc->tipe_file, 'image'))
                                                        <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100">
                                                    @else
                                                        <div class="text-primary"><i class="bi bi-file-earmark-text-fill display-4"></i></div>
                                                    @endif
                                                </div>

                                                <div class="card-body p-2 text-center d-flex flex-column justify-content-between">
                                                    <div class="mb-2">
                                                        <div class="small text-truncate fw-bold" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</div>
                                                        @if($doc->is_link)
                                                            <div class="text-muted small fst-italic text-truncate"><a href="{{ $doc->path_url }}" target="_blank">{{ $doc->path_url }}</a></div>
                                                        @endif
                                                    </div>
                                                    
                                                    <div class="d-flex gap-1 justify-content-center position-relative" style="z-index: 10;">
                                                        @if(!$doc->is_link)
                                                            <a href="{{ route('dokumen.download', $doc->id) }}" class="btn btn-outline-primary btn-sm w-100 py-0" title="Unduh"><i class="bi bi-download"></i></a>
                                                        @else
                                                            <a href="{{ $doc->path_url }}" target="_blank" class="btn btn-outline-info btn-sm w-100 py-0" title="Buka"><i class="bi bi-box-arrow-up-right"></i></a>
                                                        @endif
                                                        <button type="button" id="btn-delete-{{ $doc->id }}" class="btn btn-sm w-100 py-0 {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" onclick="markForDeletion({{ $doc->id }})">@if($isMarkedDeleted) Batal @else Hapus @endif</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif


                    {{-- 2. KOTAK KHUSUS LAMPIRAN LAMA --}}
                    @php
                        $oldLampirans = $laporan->dokumen->where('kategori', 'lampiran');
                    @endphp

                    @if($oldLampirans->count() > 0)
                        <div class="card bg-light border border-dashed mb-4">
                            <div class="card-body">
                                <h6 class="fw-bold text-danger mb-3">
                                    <i class="bi bi-paperclip me-2"></i>Lampiran Tersimpan
                                </h6>

                                <div class="row g-3">
                                    @foreach($oldLampirans as $doc)
                                        {{-- INLINE CARD COMPONENT --}}
                                        @php $isMarkedDeleted = old('delete_files') && in_array($doc->id, old('delete_files')); @endphp
                                        <div class="col-6 col-md-4 col-lg-3 file-item" id="file-card-{{ $doc->id }}">
                                            <div class="card h-100 shadow-sm border border-secondary-subtle position-relative overflow-hidden file-card-inner transition-all {{ $isMarkedDeleted ? 'border-danger-subtle-thick' : '' }}">
                                                
                                                <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" style="background-color: rgba(255, 255, 255, 0.9); z-index: 5;">
                                                    <div class="text-danger mb-1"><i class="bi bi-trash3-fill fs-1"></i></div>
                                                    <span class="text-danger fw-bold small text-uppercase">Akan Dihapus</span>
                                                </div>

                                                {{-- PREVIEW AREA --}}
                                                <div class="ratio ratio-16x9 bg-secondary bg-opacity-10 border-bottom d-flex align-items-center justify-content-center overflow-hidden">
                                                    @if($doc->is_link)
                                                        <div class="text-info"><i class="bi bi-link-45deg display-4"></i></div>
                                                    @elseif(Str::contains($doc->tipe_file, 'image'))
                                                        <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100">
                                                    @elseif(Str::contains($doc->tipe_file, 'pdf'))
                                                        <div class="text-danger"><i class="bi bi-file-earmark-pdf-fill display-4"></i></div>
                                                    @else
                                                        <div class="text-secondary"><i class="bi bi-file-earmark-text-fill display-4"></i></div>
                                                    @endif
                                                </div>

                                                <div class="card-body p-2 text-center d-flex flex-column justify-content-between">
                                                    <div class="mb-2">
                                                        <div class="small text-truncate fw-bold" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</div>
                                                        @if($doc->is_link)
                                                            <div class="text-muted small fst-italic text-truncate"><a href="{{ $doc->path_url }}" target="_blank">{{ $doc->path_url }}</a></div>
                                                        @endif
                                                    </div>
                                                    
                                                    <div class="d-flex gap-1 justify-content-center position-relative" style="z-index: 10;">
                                                        @if(!$doc->is_link)
                                                            <a href="{{ route('dokumen.download', $doc->id) }}" class="btn btn-outline-primary btn-sm w-100 py-0" title="Unduh"><i class="bi bi-download"></i></a>
                                                        @else
                                                            <a href="{{ $doc->path_url }}" target="_blank" class="btn btn-outline-info btn-sm w-100 py-0" title="Buka"><i class="bi bi-box-arrow-up-right"></i></a>
                                                        @endif
                                                        <button type="button" id="btn-delete-{{ $doc->id }}" class="btn btn-sm w-100 py-0 {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" onclick="markForDeletion({{ $doc->id }})">@if($isMarkedDeleted) Batal @else Hapus @endif</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- HIDDEN INPUTS UNTUK LOGIC HAPUS --}}
                    <div id="delete-inputs-container">
                        @if(old('delete_files'))
                            @foreach(old('delete_files') as $deletedId)
                                <input type="hidden" name="delete_files[]" value="{{ $deletedId }}" id="input-delete-{{ $deletedId }}">
                            @endforeach
                        @endif
                    </div>


                    {{-- ========================================== --}}
                    {{-- BAGIAN 3: UPLOAD FILE & LINK BARU (HYBRID) --}}
                    {{-- ========================================== --}}
                    <div class="bg-light p-4 rounded-3 border border-dashed">
                        <label class="form-label fw-bold h6 mb-3 text-dark d-block border-bottom pb-2">
                            <i class="bi bi-cloud-arrow-up me-2"></i>Upload File & Link Baru (Opsional)
                        </label>
                        
                        <div class="row g-3">
                            {{-- KIRI: DOKUMENTASI BARU --}}
                            <div class="col-12 col-md-6">
                                <div class="bg-white p-3 rounded border h-100 d-flex flex-column shadow-sm">
                                    <label class="form-label fw-bold small text-primary mb-1">
                                        <i class="bi bi-folder2-open me-2"></i>Dokumentasi Baru
                                    </label>
                                    
                                    {{-- 1. File Upload --}}
                                    <div class="mb-3">
                                        <p class="text-muted small mb-2" style="font-size: 0.75rem">Upload dokumentasi. Maksimal 10MB.</p>
                                        <input type="file" id="fp-dokumentasi" name="dokumentasi[]" multiple>
                                    </div>

                                    <hr class="border-secondary-subtle my-3">

                                    {{-- 2. Link Input (Alpine) --}}
                                    <div x-data="linkManager( {{ \Illuminate\Support\Js::from(array_values(old('dokumentasi_links', []))) }} )">
                                        <label class="form-label fw-bold small text-primary mb-2">
                                            <i class="bi bi-link-45deg me-1"></i>Atau Tautkan Link
                                        </label>
                                        
                                        <template x-for="(link, index) in links" :key="index">
                                            <div class="input-group mb-2 input-group-sm">
                                                <input type="text" class="form-control" :name="`dokumentasi_links[${index}][nama]`" placeholder="Nama Tautan / File" x-model="link.nama" required>
                                                <input type="url" class="form-control" :name="`dokumentasi_links[${index}][url]`" placeholder="https://" x-model="link.url" required>
                                                <button type="button" class="btn btn-outline-danger" @click="removeLink(index)"><i class="bi bi-x"></i></button>
                                            </div>
                                        </template>

                                        @error('dokumentasi_links.*') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                                        <button type="button" class="btn btn-xs btn-outline-primary dashed-border w-100 mt-1" @click="addLink()">
                                            <i class="bi bi-plus-circle me-1"></i> Tambah Link
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- KANAN: LAMPIRAN BARU --}}
                            <div class="col-12 col-md-6">
                                <div class="bg-white p-3 rounded border h-100 d-flex flex-column shadow-sm">
                                    <label class="form-label fw-bold small text-danger mb-1">
                                        <i class="bi bi-paperclip me-2"></i>Lampiran Pendukung Baru
                                    </label>
                                    
                                    {{-- 1. File Upload --}}
                                    <div class="mb-3">
                                        <p class="text-muted small mb-2" style="font-size: 0.75rem">Upload file pendukung. Maksimal 10MB.</p>
                                        <input type="file" id="fp-lampiran" name="lampiran[]" multiple>
                                    </div>

                                    <hr class="border-secondary-subtle my-3">

                                    {{-- 2. Link Input (Alpine) --}}
                                    <div x-data="linkManager( {{ \Illuminate\Support\Js::from(array_values(old('lampiran_links', []))) }} )">
                                        <label class="form-label fw-bold small text-danger mb-2">
                                            <i class="bi bi-link-45deg me-1"></i>Atau Tautkan Link
                                        </label>
                                        
                                        <template x-for="(link, index) in links" :key="index">
                                            <div class="input-group mb-2 input-group-sm">
                                                <input type="text" class="form-control" :name="`lampiran_links[${index}][nama]`" placeholder="Nama Tautan / File" x-model="link.nama" required>
                                                <input type="url" class="form-control" :name="`lampiran_links[${index}][url]`" placeholder="https://" x-model="link.url" required>
                                                <button type="button" class="btn btn-outline-danger" @click="removeLink(index)"><i class="bi bi-x"></i></button>
                                            </div>
                                        </template>

                                        @error('lampiran_links.*') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

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
                <button type="button" onclick="window.location.reload()" class="btn btn-light border px-4 py-2">Reset</button>
                <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">Simpan Perubahan</button>
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
        .border-danger-thick { border-color: #dc3545 !important; border-width: 2px !important; }
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
            
            // A. Init Dokumentasi Baru
            window.FilePondManager.create('#fp-dokumentasi', {
                ...commonConfig,
                maxSize: '10MB',
                // Existing Files disini hanya untuk file BARU yang gagal validasi saat submit, bukan file lama dari DB
                existingFiles: @json(old('dokumentasi', [])), 
            });

            // B. Init Lampiran Baru
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

    // LOGIC HAPUS FILE LAMA
    window.markForDeletion = function(id) {
        const cardInner = document.querySelector('#file-card-' + id + ' .file-card-inner');
        const overlay = cardInner.querySelector('.delete-overlay');
        const btnDelete = document.getElementById('btn-delete-' + id);
        const containerInputs = document.getElementById('delete-inputs-container');
        
        if (!overlay.classList.contains('d-none')) {
            // BATAL HAPUS
            overlay.classList.add('d-none');
            overlay.classList.remove('d-flex');
            cardInner.classList.remove('border-danger-subtle-thick');
            btnDelete.classList.remove('btn-secondary');
            btnDelete.classList.add('btn-outline-danger');
            btnDelete.innerHTML = 'Hapus';
            const input = document.getElementById('input-delete-' + id);
            if(input) input.remove();
        } else {
            // TANDAI HAPUS
            overlay.classList.remove('d-none');
            overlay.classList.add('d-flex');
            cardInner.classList.add('border-danger-subtle-thick');
            btnDelete.classList.remove('btn-outline-danger');
            btnDelete.classList.add('btn-secondary');
            btnDelete.innerHTML = 'Batal';
            
            const input = document.createElement('input');
            input.type = 'hidden'; 
            input.name = 'delete_files[]'; 
            input.value = id; 
            input.id = 'input-delete-' + id;
            containerInputs.appendChild(input);
        }
    };

</script>
@endpush