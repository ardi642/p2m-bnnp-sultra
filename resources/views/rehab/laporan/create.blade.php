@extends('admin')

@section('content')
<main class="admin-main" x-data="rehabForm">
    <div class="container-fluid p-4 p-lg-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Input Laporan Harian</h4>
                <p class="text-secondary small mb-0">Modul Rehabilitasi</p>
            </div>
            <a href="{{ route('rehab.laporan.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        {{-- ALERT GENERIC (Hanya memberitahu ada error, tidak merinci) --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><strong>Gagal Menyimpan!</strong> Ada inputan wajib yang masih kosong atau salah. Silakan cek form di bawah.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form action="{{ route('rehab.laporan.store') }}" method="POST" enctype="multipart/form-data" id="form-rehab" @submit.prevent="submitForm">
            @csrf
            
            {{-- INFORMASI --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4 text-dark border-bottom pb-2">Informasi Waktu & Tempat</h5>
                    <div class="row g-3">
                        @if(Auth::user()->isAdmin())
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Satuan Kerja <span class="text-danger">*</span></label>
                            <select name="satuan_kerja_id" class="form-select py-2 @error('satuan_kerja_id') is-invalid @enderror">
                                <option value="" selected disabled>Pilih Satuan Kerja...</option>
                                @foreach($satuanKerjas as $s) 
                                    <option value="{{ $s->id }}" @selected(old('satuan_kerja_id')==$s->id)>{{ $s->satuan_kerja }}</option> 
                                @endforeach
                            </select>
                            @error('satuan_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endif
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Tanggal Laporan <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" 
                                   class="form-control py-2 @error('tanggal') is-invalid @enderror" 
                                   value="{{ old('tanggal', date('Y-m-d')) }}">
                            @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- INPUT REALISASI (FIXED: Placeholder Teks & Value 0) --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4 text-dark border-bottom pb-2">Input Realisasi Harian</h5>
                    <div class="row g-4">
                        
                        {{-- RAWAT JALAN --}}
                        <div class="col-md-4">
                            <div class="card h-100 border border-warning-subtle bg-warning bg-opacity-10 shadow-sm">
                                <div class="card-header bg-transparent border-bottom border-warning-subtle fw-bold text-dark text-center py-3">RAWAT JALAN</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="small fw-bold text-secondary mb-1">Jumlah Orang <span class="text-danger">*</span></label>
                                        <div class="input-group has-validation">
                                            <input type="number" name="realisasi_rawat_jalan" 
                                                   class="form-control fw-bold @error('realisasi_rawat_jalan') is-invalid @enderror" 
                                                   value="{{ old('realisasi_rawat_jalan', 0) }}" 
                                                   placeholder="Isi jumlah..." min="0">
                                            <span class="input-group-text bg-white small text-muted">Org</span>
                                            @error('realisasi_rawat_jalan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- PASCA REHAB --}}
                        <div class="col-md-4">
                            <div class="card h-100 border border-success-subtle bg-success bg-opacity-10 shadow-sm">
                                <div class="card-header bg-transparent border-bottom border-success-subtle fw-bold text-dark text-center py-3">PASCA REHABILITASI</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="small fw-bold text-secondary mb-1">Jumlah Orang <span class="text-danger">*</span></label>
                                        <div class="input-group has-validation">
                                            <input type="number" name="realisasi_pasca_rehab" 
                                                   class="form-control fw-bold @error('realisasi_pasca_rehab') is-invalid @enderror" 
                                                   value="{{ old('realisasi_pasca_rehab', 0) }}" 
                                                   placeholder="Isi jumlah..." min="0">
                                            <span class="input-group-text bg-white small text-muted">Org</span>
                                            @error('realisasi_pasca_rehab') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- SKHPN --}}
                        <div class="col-md-4">
                            <div class="card h-100 border border-info-subtle bg-info bg-opacity-10 shadow-sm">
                                <div class="card-header bg-transparent border-bottom border-info-subtle fw-bold text-dark text-center py-3">SKHPN</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="small fw-bold text-secondary mb-1">Jumlah Orang <span class="text-danger">*</span></label>
                                        <div class="input-group has-validation">
                                            <input type="number" name="realisasi_skhpn" 
                                                   class="form-control fw-bold @error('realisasi_skhpn') is-invalid @enderror" 
                                                   value="{{ old('realisasi_skhpn', 0) }}" 
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

            {{-- DOKUMEN --}}
            <div class="card shadow-sm border-0 mb-5">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-primary"><i class="bi bi-paperclip me-2"></i>Dokumen Pendukung</h5>
                </div>
                <div class="card-body p-4">
                    <div class="bg-body-tertiary p-4 rounded border border-dashed">
                        <label class="form-label fw-bold mb-1 text-dark">
                            <i class="bi bi-cloud-arrow-up me-2"></i>Upload File
                        </label>
                        <p class="text-muted small mb-3">Format: .jpg, .png, .pdf, .docx. Maks 10MB/file.</p>
                        
                        <input type="file" 
                               class="filepond" 
                               name="dokumentasi[]" 
                               multiple 
                               data-allow-reorder="true"
                               data-max-file-size="10MB"
                               data-max-files="10">
                        @error('dokumentasi') 
                            <div class="text-danger small mt-2">
                                <i class="bi bi-exclamation-circle me-1"></i> {{ $message }}
                            </div> 
                        @enderror
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pb-5">
                <button type="button" onclick="window.location.reload()" class="btn btn-light border px-4 py-2">Reset Form</button>
                <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-2 fw-bold" :disabled="isUploading">Simpan Laporan Harian</button>
            </div>

        </form>
    </div>
</main>
@endsection

@push('styles')
    @vite(['resources/css/filepond.css', 'resources/js/filepond.js'])
    <style>
        .form-control, .form-select { border-color: #ced4da; border-radius: 0.375rem; }
        .form-control:focus, .form-select:focus { border-color: #6c757d; box-shadow: 0 0 0 0.25rem rgba(108, 117, 125, 0.15); outline: none; }
        .filepond--panel-root { background-color: #ffffff; border: 1px solid #dee2e6; }
        .border-dashed { border-style: dashed !important; border-width: 2px !important; }
        .input-group > .invalid-feedback { display: block; width: 100%; margin-top: .25rem; font-size: .875em; color: #dc3545; }
        .form-control.is-invalid { border-color: #dc3545; background-image: none; }
    </style>
@endpush

@push('scripts')
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
                    // KUNCI: Load file lama jika ada error validasi
                    files: [
                        @if(old('dokumentasi'))
                            @foreach(old('dokumentasi') as $file)
                                {
                                    source: '{{ $file }}',
                                    options: { type: 'local' }
                                },
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
                        submitBtn.innerHTML = 'Simpan Laporan Harian';
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
                            submitBtn.innerHTML = 'Simpan Laporan Harian';
                            submitBtn.classList.add('btn-primary');
                            submitBtn.classList.remove('btn-secondary');
                            submitBtn.disabled = false;
                        }
                    }
                });
            },

            submitForm(e) {
                const files = this.pond.getFiles();
                // Status 2=COMPLETE, 5=LOAD_COMPLETE. Selain itu dianggap busy/error.
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