@extends('admin')

@section('content')
<main class="admin-main" x-data="rehabForm">
    <div class="container-fluid p-4 p-lg-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Edit Laporan Harian</h4>
                <p class="text-secondary small mb-0">Update Data Realisasi Harian</p>
            </div>
            <a href="{{ route('rehab.laporan.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><strong>Gagal Menyimpan!</strong> Ada inputan wajib yang masih kosong atau salah. Silakan cek form di bawah.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form action="{{ route('rehab.laporan.update', $laporan->id) }}" method="POST" enctype="multipart/form-data" id="form-rehab" @submit.prevent="submitForm">
            @csrf
            @method('PUT')
            
            {{-- INFO (READ ONLY) --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4 text-dark border-bottom pb-2">
                        <i class="bi bi-info-circle me-2 text-primary"></i>Info Laporan
                    </h5>
                    <div class="alert alert-light border border-secondary-subtle d-flex align-items-center p-3 mb-0">
                        <div class="flex-grow-1">
                            <div class="row g-3">
                                <div class="col-md-6 border-end-md">
                                    <div class="small text-uppercase text-secondary fw-bold mb-1">Satuan Kerja</div>
                                    <div class="fw-bold text-dark fs-5">{{ $laporan->satuanKerja->satuan_kerja ?? '-' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small text-uppercase text-secondary fw-bold mb-1">Tanggal Laporan</div>
                                    <div class="fw-bold text-dark fs-5 d-flex align-items-center">
                                        {{ $laporan->tanggal_text }}
                                        <span class="badge bg-secondary ms-2 small" style="font-size: 0.65rem;">
                                            <i class="bi bi-lock-fill me-1"></i>Terkunci
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- UPDATE REALISASI --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4 text-dark border-bottom pb-2">
                        <i class="bi bi-pencil-square me-2 text-primary"></i>Update Realisasi
                    </h5>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card h-100 border border-warning-subtle bg-warning bg-opacity-10 shadow-sm">
                                <div class="card-header bg-transparent border-bottom border-warning-subtle fw-bold text-dark text-center py-3">RAWAT JALAN</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="small fw-bold text-secondary mb-1">Jumlah Orang <span class="text-danger">*</span></label>
                                        <div class="input-group has-validation">
                                            <input type="number" name="realisasi_rawat_jalan" 
                                                   class="form-control fw-bold @error('realisasi_rawat_jalan') is-invalid @enderror" 
                                                   value="{{ old('realisasi_rawat_jalan', $laporan->realisasi_rawat_jalan) }}" 
                                                   placeholder="Isi jumlah..." min="0">
                                            <span class="input-group-text bg-white small text-muted">Org</span>
                                            @error('realisasi_rawat_jalan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card h-100 border border-success-subtle bg-success bg-opacity-10 shadow-sm">
                                <div class="card-header bg-transparent border-bottom border-success-subtle fw-bold text-dark text-center py-3">PASCA REHAB</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="small fw-bold text-secondary mb-1">Jumlah Orang <span class="text-danger">*</span></label>
                                        <div class="input-group has-validation">
                                            <input type="number" name="realisasi_pasca_rehab" 
                                                   class="form-control fw-bold @error('realisasi_pasca_rehab') is-invalid @enderror" 
                                                   value="{{ old('realisasi_pasca_rehab', $laporan->realisasi_pasca_rehab) }}" 
                                                   placeholder="Isi jumlah..." min="0">
                                            <span class="input-group-text bg-white small text-muted">Org</span>
                                            @error('realisasi_pasca_rehab') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card h-100 border border-info-subtle bg-info bg-opacity-10 shadow-sm">
                                <div class="card-header bg-transparent border-bottom border-info-subtle fw-bold text-dark text-center py-3">SKHPN</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="small fw-bold text-secondary mb-1">Jumlah Orang <span class="text-danger">*</span></label>
                                        <div class="input-group has-validation">
                                            <input type="number" name="realisasi_skhpn" 
                                                   class="form-control fw-bold @error('realisasi_skhpn') is-invalid @enderror" 
                                                   value="{{ old('realisasi_skhpn', $laporan->realisasi_skhpn) }}" 
                                                   placeholder="Isi jumlah..." min="0">
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

            {{-- DOKUMEN (PERBAIKAN VISUAL: TINGGI CARD & PREVIEW) --}}
            <div class="card shadow-sm border-0 mb-5">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4 text-dark border-bottom pb-2">
                        <i class="bi bi-paperclip me-2 text-primary"></i>Dokumen Pendukung
                    </h5>
                    
                    {{-- File Tersimpan --}}
                    @if($laporan->dokumentasi->count() > 0)
                        <div class="mb-4">
                            <h6 class="fw-bold text-secondary small mb-3 text-uppercase">File Tersimpan</h6>
                            
                            {{-- Grid Container --}}
                            <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4" id="existing-files-container">
                                @foreach($laporan->dokumentasi as $doc)
                                    @php $isMarked = old('delete_files') && in_array($doc->id, old('delete_files')); @endphp
                                    
                                    <div class="col file-item" id="file-card-{{ $doc->id }}">
                                        {{-- 
                                            PERBAIKAN 1: 'min-height: 260px' 
                                            Agar kartu tinggi, sehingga overlay tengah tidak menabrak tombol bawah.
                                        --}}
                                        <div class="card h-100 shadow-sm border border-secondary-subtle position-relative overflow-hidden file-card-inner {{ $isMarked ? 'border-danger-thick' : '' }}" 
                                             style="min-height: 260px;">
                                            
                                            {{-- LAYER 2: OVERLAY (Z-20) --}}
                                            {{-- Tambahkan 'pb-5' agar ikon sampah agak naik ke atas --}}
                                            <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 {{ $isMarked ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center pb-5" 
                                                 style="background-color: rgba(255, 255, 255, 0.95); z-index: 20;">
                                                <div class="text-danger mb-2"><i class="bi bi-trash3-fill display-3"></i></div>
                                                <span class="text-danger fw-bold small text-uppercase px-3 py-1 border border-danger rounded bg-white shadow-sm">AKAN DIHAPUS</span>
                                            </div>

                                            {{-- BODY CARD --}}
                                            <div class="card-body p-3 text-center d-flex flex-column justify-content-between">
                                                
                                                {{-- LAYER 1: PREVIEW (Z-10) --}}
                                                <div class="flex-grow-1 d-flex flex-column justify-content-center mb-3" style="position: relative; z-index: 10;">
                                                    
                                                    {{-- PERBAIKAN 2: TAMPILKAN GAMBAR ASLI --}}
                                                    <div class="rounded border bg-light d-flex align-items-center justify-content-center overflow-hidden mb-2" style="height: 120px;">
                                                        @if(Str::contains($doc->tipe_file, 'image')) 
                                                            <img src="{{ Storage::url($doc->path_file) }}" alt="Preview" class="img-fluid" style="width: 100%; height: 100%; object-fit: cover;">
                                                        @elseif(Str::contains($doc->tipe_file, 'pdf')) 
                                                            <i class="bi bi-file-pdf display-4 text-danger"></i>
                                                        @else 
                                                            <i class="bi bi-file-earmark-text display-4 text-secondary"></i> 
                                                        @endif
                                                    </div>

                                                    {{-- NAMA FILE --}}
                                                    <div class="small fw-bold text-dark text-break lh-sm" title="{{ $doc->nama_file_asli }}">
                                                        {{ Str::limit($doc->nama_file_asli, 25) }}
                                                    </div>
                                                    <small class="text-muted" style="font-size: 0.7rem;">
                                                        {{ $doc->ukuran_file > 1048576 ? round($doc->ukuran_file/1048576, 2).' MB' : round($doc->ukuran_file/1024, 0).' KB' }}
                                                    </small>
                                                </div>

                                                {{-- LAYER 3: TOMBOL AKSI (Z-30) --}}
                                                <div class="d-flex gap-2 justify-content-center w-100" style="position: relative; z-index: 30;">
                                                    <a href="{{ route('dokumen.download', $doc->id) }}" 
                                                       class="btn btn-outline-secondary btn-sm flex-grow-1" 
                                                       title="Download">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                    
                                                    <button type="button" 
                                                            id="btn-delete-{{ $doc->id }}" 
                                                            class="btn btn-sm flex-grow-1 {{ $isMarked ? 'btn-secondary text-white' : 'btn-outline-danger' }}" 
                                                            onclick="markForDeletion({{ $doc->id }})">
                                                        @if($isMarked) Batal @else Hapus @endif
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div id="delete-inputs-container">
                                @if(old('delete_files')) 
                                    @foreach(old('delete_files') as $d) 
                                        <input type="hidden" name="delete_files[]" value="{{ $d }}" id="input-delete-{{ $d }}"> 
                                    @endforeach 
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Upload File Baru --}}
                    <div class="bg-body-tertiary p-4 rounded border border-dashed">
                        <label class="form-label fw-bold mb-1 text-dark"><i class="bi bi-cloud-arrow-up me-2"></i>Upload File Baru</label>
                        <p class="text-muted small mb-3">Format: .jpg, .png, .pdf, .docx. Maks 10MB/file.</p>
                        
                        <input type="file" 
                               class="filepond" 
                               name="dokumentasi[]" 
                               multiple 
                               data-allow-reorder="true"
                               data-max-file-size="10MB"
                               data-max-files="10">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pb-5">
                <button type="button" onclick="window.location.reload()" class="btn btn-light border px-4 py-2">Reset Perubahan</button>
                <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-2 fw-bold" :disabled="isUploading">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</main>
@endsection

@push('styles')
    @vite(['resources/css/filepond.css', 'resources/js/filepond.js'])
    <style>
        .form-control { border-color: #ced4da; }
        .border-danger-thick { border-color: #dc3545 !important; border-width: 2px !important; }
        .filepond--panel-root { background-color: #ffffff; border: 1px solid #dee2e6; }
        .border-dashed { border-style: dashed !important; border-width: 2px !important; }
        .input-group > .invalid-feedback { display: block; width: 100%; margin-top: .25rem; font-size: .875em; color: #dc3545; }
        .form-control.is-invalid { border-color: #dc3545; }
        .delete-overlay { backdrop-filter: blur(2px); }
    </style>
@endpush

@push('scripts')
<script>
    window.markForDeletion = function(id) {
        const cardInner = document.querySelector('#file-card-' + id + ' .file-card-inner');
        const overlay = cardInner.querySelector('.delete-overlay');
        const btnDelete = document.getElementById('btn-delete-' + id);
        const containerInputs = document.getElementById('delete-inputs-container');
        let input = document.getElementById('input-delete-' + id);
        
        if (input) { 
            input.remove(); 
            overlay.classList.add('d-none'); overlay.classList.remove('d-flex');
            cardInner.classList.remove('border-danger-thick'); 
            btnDelete.classList.remove('btn-secondary'); btnDelete.classList.remove('text-white'); btnDelete.classList.add('btn-outline-danger'); btnDelete.innerHTML = 'Hapus';
        } else { 
            input = document.createElement('input'); input.type = 'hidden'; input.name = 'delete_files[]'; input.value = id; input.id = 'input-delete-' + id;
            containerInputs.appendChild(input); 
            overlay.classList.remove('d-none'); overlay.classList.add('d-flex');
            cardInner.classList.add('border-danger-thick'); 
            btnDelete.classList.remove('btn-outline-danger'); btnDelete.classList.add('btn-secondary'); btnDelete.classList.add('text-white'); btnDelete.innerHTML = 'Batal';
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
                        },
                        load: {
                            url: '{{ route("load.temp") }}/?file=',
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
                        submitBtn.innerHTML = 'Simpan Perubahan';
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
                            submitBtn.innerHTML = 'Simpan Perubahan';
                            submitBtn.classList.add('btn-primary');
                            submitBtn.classList.remove('btn-secondary');
                            submitBtn.disabled = false;
                        }
                    }
                });
            },

            submitForm(e) {
                const files = this.pond.getFiles();
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
                        alert('Silakan tunggu proses upload file selesai atau hapus file yang macet.');
                    }
                    return;
                }
                e.target.submit();
            }
        }));
    });
</script>
@endpush