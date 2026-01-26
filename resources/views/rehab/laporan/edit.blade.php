@extends('admin')

@section('content')
<main class="admin-main" x-data="rehabForm">
    <div class="container-fluid p-4 p-lg-5">
        
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Edit Laporan Bulanan</h4>
                <p class="text-secondary small mb-0">Update Data Rehabilitasi</p>
            </div>
            <a href="{{ route('rehab.laporan.index') }}" class="btn btn-secondary btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        {{-- FORM UPDATE --}}
        <form action="{{ route('rehab.laporan.update', $laporan->id) }}" method="POST" enctype="multipart/form-data" id="form-rehab" @submit.prevent="submitForm">
            @csrf
            @method('PUT')
            
            {{-- CARD 1: INFO PERIODE (READ ONLY) --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4 text-dark border-bottom pb-2">
                        <i class="bi bi-info-circle me-2 text-primary"></i>Periode & Satuan Kerja
                    </h5>
                    
                    <div class="alert alert-light border border-secondary-subtle d-flex align-items-center p-3 mb-0">
                        <div class="me-4 text-center d-none d-md-block">
                            <i class="bi bi-calendar-check text-secondary display-6"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="row g-3">
                                <div class="col-md-6 border-end-md">
                                    <div class="small text-uppercase text-secondary fw-bold mb-1">Satuan Kerja</div>
                                    <div class="fw-bold text-dark fs-5">{{ $laporan->satuanKerja->satuan_kerja ?? '-' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small text-uppercase text-secondary fw-bold mb-1">Periode Laporan</div>
                                    <div class="fw-bold text-dark fs-5 d-flex align-items-center">
                                        {{ \Carbon\Carbon::parse($laporan->periode)->translatedFormat('F Y') }}
                                        <span class="badge bg-secondary ms-2 small" style="font-size: 0.65rem;">
                                            <i class="bi bi-lock-fill me-1"></i>Terkunci
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-text text-muted mt-2 small">
                        <i class="bi bi-shield-check me-1"></i> Periode dan Satuan Kerja tidak dapat diubah pada mode Edit. Jika salah input periode, silakan hapus dan buat baru.
                    </div>
                </div>
            </div>

            {{-- CARD 2: INDIKATOR KINERJA --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4 text-dark border-bottom pb-2">
                        <i class="bi bi-bar-chart-line me-2 text-primary"></i>Update Indikator Kinerja
                    </h5>
                    
                    <div class="row g-4">
                        {{-- 1. RAWAT JALAN --}}
                        <div class="col-md-4">
                            <div class="card h-100 border border-warning-subtle bg-warning bg-opacity-10 shadow-sm">
                                <div class="card-header bg-transparent border-bottom border-warning-subtle fw-bold text-dark text-center py-3">
                                    RAWAT JALAN
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="small fw-bold text-secondary mb-1">Target</label>
                                        <div class="input-group">
                                            <input type="number" name="target_rawat_jalan" class="form-control fw-bold @error('target_rawat_jalan') is-invalid @enderror" value="{{ old('target_rawat_jalan', $laporan->target_rawat_jalan) }}" placeholder="Isi target...">
                                            <span class="input-group-text bg-white small text-muted">Org</span>
                                            @error('target_rawat_jalan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div>
                                        <label class="small fw-bold text-secondary mb-1">Realisasi</label>
                                        <div class="input-group">
                                            <input type="number" name="realisasi_rawat_jalan" class="form-control fw-bold @error('realisasi_rawat_jalan') is-invalid @enderror" value="{{ old('realisasi_rawat_jalan', $laporan->realisasi_rawat_jalan) }}" placeholder="Isi realisasi...">
                                            <span class="input-group-text bg-white small text-muted">Org</span>
                                            @error('realisasi_rawat_jalan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 2. PASCA REHAB --}}
                        <div class="col-md-4">
                            <div class="card h-100 border border-success-subtle bg-success bg-opacity-10 shadow-sm">
                                <div class="card-header bg-transparent border-bottom border-success-subtle fw-bold text-dark text-center py-3">
                                    PASCA REHABILITASI
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="small fw-bold text-secondary mb-1">Target</label>
                                        <div class="input-group">
                                            <input type="number" name="target_pasca_rehab" class="form-control fw-bold @error('target_pasca_rehab') is-invalid @enderror" value="{{ old('target_pasca_rehab', $laporan->target_pasca_rehab) }}" placeholder="Isi target...">
                                            <span class="input-group-text bg-white small text-muted">Org</span>
                                            @error('target_pasca_rehab') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div>
                                        <label class="small fw-bold text-secondary mb-1">Realisasi</label>
                                        <div class="input-group">
                                            <input type="number" name="realisasi_pasca_rehab" class="form-control fw-bold @error('realisasi_pasca_rehab') is-invalid @enderror" value="{{ old('realisasi_pasca_rehab', $laporan->realisasi_pasca_rehab) }}" placeholder="Isi realisasi...">
                                            <span class="input-group-text bg-white small text-muted">Org</span>
                                            @error('realisasi_pasca_rehab') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 3. SKHPN --}}
                        <div class="col-md-4">
                            <div class="card h-100 border border-info-subtle bg-info bg-opacity-10 shadow-sm">
                                <div class="card-header bg-transparent border-bottom border-info-subtle fw-bold text-dark text-center py-3">
                                    SKHPN
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="small fw-bold text-secondary mb-1">Target</label>
                                        <div class="input-group">
                                            <input type="number" name="target_skhpn" class="form-control fw-bold @error('target_skhpn') is-invalid @enderror" value="{{ old('target_skhpn', $laporan->target_skhpn) }}" placeholder="Isi target...">
                                            <span class="input-group-text bg-white small text-muted">Org</span>
                                            @error('target_skhpn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    <div>
                                        <label class="small fw-bold text-secondary mb-1">Realisasi</label>
                                        <div class="input-group">
                                            <input type="number" name="realisasi_skhpn" class="form-control fw-bold @error('realisasi_skhpn') is-invalid @enderror" value="{{ old('realisasi_skhpn', $laporan->realisasi_skhpn) }}" placeholder="Isi realisasi...">
                                            <span class="input-group-text bg-white small text-muted">Org</span>
                                            @error('realisasi_skhpn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD 3: DOKUMEN PENDUKUNG --}}
            <div class="card shadow-sm border-0 mb-5">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4 text-dark border-bottom pb-2">
                        <i class="bi bi-paperclip me-2 text-primary"></i>Dokumen Pendukung
                    </h5>

                    {{-- A. FILE LAMA (JIKA ADA) --}}
                    @if($laporan->dokumentasi->count() > 0)
                        <div class="mb-4">
                            <h6 class="fw-bold text-secondary small mb-3 text-uppercase">File Tersimpan</h6>
                            
                            <div class="row g-3" id="existing-files-container">
                                @foreach($laporan->dokumentasi as $doc)
                                    @php 
                                        $isMarkedDeleted = old('delete_files') && in_array($doc->id, old('delete_files')); 
                                        $fileUrl = Storage::url($doc->path_file);
                                    @endphp

                                    <div class="col-6 col-md-4 col-lg-3 file-item" id="file-card-{{ $doc->id }}">
                                        <div class="card h-100 shadow-sm border border-secondary-subtle position-relative overflow-hidden file-card-inner {{ $isMarkedDeleted ? 'border-danger-thick' : '' }}" style="transition: all 0.3s ease;">
                                            
                                            {{-- OVERLAY MERAH (AKAN DIHAPUS) --}}
                                            {{-- z-index 20 --}}
                                            <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" 
                                                 style="background-color: rgba(255, 255, 255, 0.85); z-index: 20;">
                                                <div class="text-danger mb-2"><i class="bi bi-trash3-fill display-4"></i></div>
                                                <span class="text-danger fw-bold small text-uppercase px-2 py-1 border border-danger rounded">AKAN DIHAPUS</span>
                                            </div>

                                            {{-- PREVIEW GAMBAR/ICON --}}
                                            <div class="ratio ratio-16x9 bg-secondary bg-opacity-10 border-bottom d-flex align-items-center justify-content-center overflow-hidden">
                                                @if(Str::contains($doc->tipe_file, 'image'))
                                                    <img src="{{ $fileUrl }}" class="object-fit-cover w-100 h-100">
                                                @elseif(Str::contains($doc->tipe_file, 'pdf'))
                                                    <div class="text-danger"><i class="bi bi-file-earmark-pdf-fill display-4"></i></div>
                                                @elseif(Str::contains($doc->tipe_file, ['word', 'officedocument']))
                                                    <div class="text-primary"><i class="bi bi-file-earmark-word-fill display-4"></i></div>
                                                @else
                                                    <div class="text-secondary"><i class="bi bi-file-earmark-text-fill display-4"></i></div>
                                                @endif
                                            </div>
                                            
                                            {{-- INFO FILE & TOMBOL --}}
                                            {{-- PERBAIKAN: z-index dinaikkan jadi 30 agar di atas overlay (20) --}}
                                            <div class="card-body p-2 text-center d-flex flex-column justify-content-between position-relative" style="z-index: 30;">
                                                <div class="mb-2">
                                                    <div class="small text-truncate fw-bold text-dark" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</div>
                                                    <div class="text-muted" style="font-size: 0.7rem;">
                                                        {{ $doc->ukuran_file >= 1048576 ? number_format($doc->ukuran_file / 1048576, 2) . ' MB' : number_format($doc->ukuran_file / 1024, 0) . ' KB' }}
                                                    </div>
                                                </div>
                                                
                                                <div class="d-flex gap-1">
                                                    {{-- Download --}}
                                                    <a href="{{ route('dokumentasi.download', $doc->id) }}" class="btn btn-outline-secondary btn-sm flex-grow-1 py-0 d-flex align-items-center justify-content-center" style="font-size: 0.75rem;" title="Download">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                    {{-- Hapus Toggle --}}
                                                    <button type="button" 
                                                            id="btn-delete-{{ $doc->id }}"
                                                            class="btn btn-sm flex-grow-1 py-0 {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" 
                                                            onclick="markForDeletion({{ $doc->id }})"
                                                            style="font-size: 0.75rem;">
                                                        @if($isMarkedDeleted) Batal @else Hapus @endif
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            {{-- HIDDEN INPUTS (Diisi Javascript) --}}
                            <div id="delete-inputs-container">
                                @if(old('delete_files'))
                                    @foreach(old('delete_files') as $deletedId)
                                        <input type="hidden" name="delete_files[]" value="{{ $deletedId }}" id="input-delete-{{ $deletedId }}">
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- B. UPLOAD BARU --}}
                    <div class="bg-body-tertiary p-4 rounded border border-dashed">
                        <label class="form-label fw-bold mb-1 text-dark">
                            <i class="bi bi-cloud-arrow-up me-2"></i>Upload File Baru
                        </label>
                        <p class="text-muted small mb-3">Format: .jpg, .png, .pdf, .docx. Maksimal 10MB per file.</p>
                        
                        <input type="file" 
                               class="filepond" 
                               name="dokumentasi[]" 
                               multiple 
                               data-allow-reorder="true"
                               data-max-file-size="10MB"
                               data-max-files="10">
                        @error('dokumentasi') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>

            {{-- TOMBOL AKSI --}}
            <div class="d-flex justify-content-end gap-2 pb-5">
                <button type="button" onclick="window.location.reload()" class="btn btn-light border px-4 py-2">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Perubahan
                </button>
                <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm" :disabled="isUploading">
                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                </button>
            </div>

        </form>
    </div>
</main>
@endsection

@push('styles')
    @vite(['resources/css/filepond.css', 'resources/js/filepond.js'])
    <style>
        .form-control { border-color: #ced4da; border-radius: 0.375rem; }
        .form-control:focus { border-color: #6c757d; box-shadow: 0 0 0 0.25rem rgba(108, 117, 125, 0.15); outline: none; }
        .form-control.is-invalid { border-color: #dc3545; background-image: none; }
        
        .filepond--panel-root { background-color: #ffffff; border: 1px solid #dee2e6; }
        .border-dashed { border-style: dashed !important; border-width: 2px !important; }
        
        /* Style untuk status hapus file lama */
        .border-danger-thick { border-color: #dc3545 !important; border-width: 2px !important; }
        .delete-overlay { display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .border-end-md { border-right: 1px solid #dee2e6; }
        @media (max-width: 768px) { .border-end-md { border-right: none; border-bottom: 1px solid #dee2e6; padding-bottom: 1rem; margin-bottom: 1rem; } }
    </style>
@endpush

@push('scripts')
<script>
    // Logic Javascript Standar untuk Toggle Hapus File Lama
    window.markForDeletion = function(id) {
        const cardInner = document.querySelector('#file-card-' + id + ' .file-card-inner');
        const overlay = cardInner.querySelector('.delete-overlay');
        const btnDelete = document.getElementById('btn-delete-' + id);
        const containerInputs = document.getElementById('delete-inputs-container');
        let input = document.getElementById('input-delete-' + id);
        
        if (input) { 
            // BATAL HAPUS (Kembalikan ke Normal)
            input.remove();
            overlay.classList.add('d-none');
            overlay.classList.remove('d-flex');
            cardInner.classList.remove('border-danger-thick');
            btnDelete.classList.remove('btn-secondary');
            btnDelete.classList.add('btn-outline-danger');
            btnDelete.innerHTML = 'Hapus';
        } else { 
            // TANDAI HAPUS (Munculkan Overlay Merah)
            input = document.createElement('input');
            input.type = 'hidden'; 
            input.name = 'delete_files[]'; 
            input.value = id; 
            input.id = 'input-delete-' + id;
            containerInputs.appendChild(input);
            
            overlay.classList.remove('d-none');
            overlay.classList.add('d-flex');
            cardInner.classList.add('border-danger-thick');
            btnDelete.classList.remove('btn-outline-danger');
            btnDelete.classList.add('btn-secondary');
            btnDelete.innerHTML = 'Batal';
        }
    };
</script>

<script type="module">
    document.addEventListener('alpine:init', () => {
        Alpine.data('rehabForm', () => ({
            isUploading: false,
            pond: null,

            init() {
                const submitBtn = document.getElementById('btn-submit');
                const inputElement = document.querySelector('input.filepond');

                this.pond = FilePond.create(inputElement, {
                    acceptedFileTypes: ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                    labelIdle: 'Drag & Drop file atau <span class="filepond--label-action">Cari File</span>',
                    credits: false,
                    server: {
                        process: {
                            url: '{{ route("upload.temp") }}',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            onload: (response) => response,
                            onerror: (response) => { this.isUploading = false; return response; }
                        },
                        revert: {
                            url: '{{ route("revert.temp") }}',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        }
                    },
                    files: [
                        @if(old('dokumentasi'))
                            @foreach(old('dokumentasi') as $file)
                                { source: '{{ $file }}', options: { type: 'local' } },
                            @endforeach
                        @endif
                    ],
                    onprocessstart: () => { 
                        this.isUploading = true; 
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengupload...';
                        submitBtn.classList.add('btn-secondary');
                        submitBtn.classList.remove('btn-primary');
                        submitBtn.disabled = true;
                    },
                    onprocessfiles: () => { 
                        this.isUploading = false; 
                        submitBtn.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Perubahan';
                        submitBtn.classList.add('btn-primary');
                        submitBtn.classList.remove('btn-secondary');
                        submitBtn.disabled = false;
                    },
                    onwarning: () => { this.isUploading = false; },
                    onerror: () => { this.isUploading = false; },
                    onremovefile: () => {
                        const files = this.pond.getFiles();
                        const isBusy = files.some(file => file.status === 3 || file.status === 9);
                        if (!isBusy) {
                            this.isUploading = false;
                            submitBtn.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Perubahan';
                            submitBtn.classList.add('btn-primary');
                            submitBtn.classList.remove('btn-secondary');
                            submitBtn.disabled = false;
                        }
                    }
                });
            },

            submitForm(e) {
                const files = this.pond.getFiles();
                // Status 2 = Processing Complete, 5 = Idle
                const isBusy = files.some(file => file.status !== 2 && file.status !== 5);

                if (this.isUploading || isBusy) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Upload Belum Selesai',
                            text: 'Silakan tunggu proses upload file selesai atau hapus file yang macet.',
                            showConfirmButton: true
                        });
                    } else {
                        alert('Mohon tunggu, file sedang diupload.');
                    }
                    return;
                }
                
                // Submit Form
                e.target.submit();
            }
        }));
    });
</script>
@endpush