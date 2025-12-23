@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            
            {{-- Header --}}
            <div class="row justify-content-center mb-4">
                <div class="col-12 col-lg-10">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-1 fw-bold text-dark">Edit Kegiatan P2M</h1>
                            <p class="text-muted mb-0">Perbarui Data Media Online</p>
                        </div>
                        <a href="{{ route('p2m.online.index') }}" 
                           class="btn btn-outline-secondary d-flex align-items-center gap-2">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="card border-0 shadow-lg">
                        
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="card-title mb-0 fw-bold">Form Edit Data</h5>
                        </div>

                        <div class="card-body p-4 p-lg-5">
                            
                            <form action="{{ route('p2m.online.update', $kegiatan->id) }}" 
                                  method="POST" 
                                  enctype="multipart/form-data" 
                                  id="form-edit">
                                @csrf 
                                @method('PUT')

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
                                                <option value="" disabled>-- Pilih Satuan Kerja --</option>
                                                @foreach ($satuanKerjas as $satker)
                                                    <option value="{{ $satker->id }}" 
                                                            @selected(old('satuan_kerja_id', $kegiatan->satuan_kerja_id) == $satker->id)>
                                                        {{ $satker->satuan_kerja }}
                                                    </option>
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
                                            <option value="DIPA" 
                                                    @selected(old('anggaran_pelaksanaan', $kegiatan->anggaran_pelaksanaan) == 'DIPA')>
                                                DIPA
                                            </option>
                                            <option value="NON DIPA" 
                                                    @selected(old('anggaran_pelaksanaan', $kegiatan->anggaran_pelaksanaan) == 'NON DIPA')>
                                                NON DIPA
                                            </option>
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
                                            @foreach($mediaOptions as $key => $label)
                                                <option value="{{ $key }}" 
                                                        @selected(old('jenis_media', $kegiatan->jenis_media) == $key)>
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
                                               value="{{ old('nama_media', $kegiatan->nama_media) }}" 
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
                                               value="{{ old('tanggal_mulai_pelaksanaan', $kegiatan->tanggal_mulai_pelaksanaan->format('Y-m-d')) }}">
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
                                                   value="{{ old('durasi_pelaksanaan', $kegiatan->durasi_pelaksanaan) }}" 
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
                                    <div class="col-12">
                                        <div class="bg-light p-4 rounded-3 border border-dashed">
                                            <label class="form-label fw-bold h6 mb-3 text-dark d-block border-bottom pb-2">
                                                <i class="bi bi-images me-2"></i>Pengelolaan Dokumentasi
                                            </label>
                                            
                                            {{-- FILE LAMA --}}
                                            @if($kegiatan->dokumentasi->count() > 0)
                                                <p class="small fw-bold text-secondary mb-2">File Tersimpan:</p>
                                                <div class="row g-3 mb-4" id="existing-files-container">
                                                    @foreach($kegiatan->dokumentasi as $doc)
                                                        @php 
                                                            $isMarkedDeleted = old('delete_files') && in_array($doc->id, old('delete_files')); 
                                                        @endphp
                                                        <div class="col-6 col-md-4 col-lg-3 file-item" id="file-card-{{ $doc->id }}">
                                                            <div class="card h-100 shadow-sm border border-secondary-subtle position-relative overflow-hidden file-card-inner transition-all {{ $isMarkedDeleted ? 'border-danger-subtle-thick' : '' }}">
                                                                
                                                                {{-- Overlay --}}
                                                                <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" 
                                                                     style="background-color: rgba(255, 255, 255, 0.9); z-index: 5;">
                                                                    <div class="text-danger mb-1"><i class="bi bi-trash3-fill fs-1"></i></div>
                                                                    <span class="text-danger fw-bold small text-uppercase">Akan Dihapus</span>
                                                                </div>
                                                                
                                                                {{-- Preview --}}
                                                                <div class="ratio ratio-16x9 bg-secondary bg-opacity-10 border-bottom d-flex align-items-center justify-content-center overflow-hidden">
                                                                    @if(Str::contains($doc->tipe_file, 'image')) 
                                                                        <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100">
                                                                    @elseif(Str::contains($doc->tipe_file, 'pdf')) 
                                                                        <div class="text-danger"><i class="bi bi-file-earmark-pdf-fill display-4"></i></div>
                                                                    @else 
                                                                        <div class="text-secondary"><i class="bi bi-file-earmark-text-fill display-4"></i></div> 
                                                                    @endif
                                                                </div>
                                                                
                                                                {{-- Actions --}}
                                                                <div class="card-body p-2 text-center d-flex flex-column justify-content-between">
                                                                    <div class="mb-2">
                                                                        <div class="small text-truncate fw-bold" title="{{ $doc->nama_file_asli }}">
                                                                            {{ $doc->nama_file_asli }}
                                                                        </div>
                                                                    </div>
                                                                    <div class="d-flex gap-1 justify-content-center position-relative" style="z-index: 10;">
                                                                        <a href="{{ route('dokumentasi.download', $doc->id) }}" 
                                                                           class="btn btn-outline-primary btn-sm w-100 py-0" 
                                                                           title="Unduh">
                                                                            <i class="bi bi-download"></i>
                                                                        </a>
                                                                        <button type="button" 
                                                                                id="btn-delete-{{ $doc->id }}" 
                                                                                class="btn btn-sm w-100 py-0 {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" 
                                                                                onclick="markForDeletion({{ $doc->id }})">
                                                                            @if($isMarkedDeleted) Batal @else Hapus @endif
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                
                                                <div id="delete-inputs-container">
                                                    @if(old('delete_files')) 
                                                        @foreach(old('delete_files') as $deletedId) 
                                                            <input type="hidden" name="delete_files[]" value="{{ $deletedId }}" id="input-delete-{{ $deletedId }}"> 
                                                        @endforeach 
                                                    @endif
                                                </div>
                                            @endif
                                            
                                            {{-- FILE BARU --}}
                                            <p class="small fw-bold text-secondary mb-1 mt-2">Upload File Baru (Opsional):</p>
                                            <input type="file" 
                                                   class="filepond" 
                                                   name="dokumentasi[]" 
                                                   multiple 
                                                   data-allow-reorder="true" 
                                                   data-max-file-size="10MB" 
                                                   data-max-files="10">
                                            @error('dokumentasi') 
                                                <div class="text-danger small mt-1">{{ $message }}</div> 
                                            @enderror
                                        </div>
                                    </div>
                                </div> 

                                {{-- Buttons --}}
                                <div class="d-flex flex-column-reverse flex-lg-row justify-content-end gap-2 pt-3 border-top">
                                    <button type="button" 
                                            onclick="window.location.reload()" 
                                            class="btn btn-light border text-secondary px-4">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                    </button>
                                    <button type="submit" 
                                            id="btn-submit" 
                                            class="btn btn-primary px-5 shadow-sm">
                                        <i class="bi bi-save me-1"></i> Simpan Perubahan
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
    @vite(['resources/css/tom-select.css', 'resources/css/filepond.css', 'resources/js/filepond.js'])
    <style> 
        .filepond--panel-root { background-color: #ffffff; border: 1px solid #dee2e6; } 
        .border-dashed { border-style: dashed !important; border-width: 2px !important; } 
        .border-danger-subtle-thick { border-color: #dc3545 !important; border-width: 2px !important; } 
    </style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const inputElement = document.querySelector('input.filepond');
        const form = document.getElementById('form-edit');
        const submitBtn = document.getElementById('btn-submit');
        const originalBtnText = submitBtn.innerHTML;

        const setButtonState = (isLoading, text = null) => {
            if (isLoading) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + (text || 'Memproses...');
                submitBtn.classList.add('btn-secondary');
                submitBtn.classList.remove('btn-primary');
            } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                submitBtn.classList.add('btn-primary');
                submitBtn.classList.remove('btn-secondary');
            }
        };

        const pond = FilePond.create(inputElement, {
            acceptedFileTypes: ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            labelIdle: 'Drag & Drop file atau <span class="filepond--label-action">Cari File</span>',
            imagePreviewHeight: 120,
            credits: false,
            allowMultiple: true,
            files: [
                @if(old('dokumentasi'))
                    @foreach(old('dokumentasi') as $file)
                    { source: '{{ $file }}', options: { type: 'local' } },
                    @endforeach
                @endif
            ],
            server: {
                process: {
                    url: '{{ route('upload.temp') }}',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    onload: (response) => { return response; },
                    onerror: (response) => { setButtonState(false); return response; }
                },
                revert: { url: '{{ route('revert.temp') }}', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } },
                load: { url: '{{ route('load.temp') }}/?file=', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } },
            },
            onprocessstart: () => { setButtonState(true, 'Mengupload...'); },
            onprocessfiles: () => { setButtonState(false); },
            onwarning: () => { setButtonState(false); },
            onerror: () => { setButtonState(false); },
            onremovefile: () => {
                const files = pond.getFiles();
                const isStillBusy = files.some(file => file.status === 3 || file.status === 9);
                if (!isStillBusy) { setButtonState(false); }
            }
        });

        form.addEventListener('submit', function(e) {
            const files = pond.getFiles();
            const isBusy = files.some(file => file.status !== 2 && file.status !== 5);
            if (isBusy) {
                e.preventDefault(); e.stopPropagation();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Upload Belum Selesai', text: 'Silakan tunggu proses upload selesai.', showConfirmButton: true, confirmButtonText: 'Mengerti' });
                } else {
                    alert('Mohon tunggu, file sedang diupload.');
                }
            } else {
                setButtonState(true, 'Menyimpan...');
            }
        });
    });

    window.markForDeletion = function(id) {
        const cardInner = document.querySelector('#file-card-' + id + ' .file-card-inner');
        const overlay = cardInner.querySelector('.delete-overlay');
        const btnDelete = document.getElementById('btn-delete-' + id);
        const containerInputs = document.getElementById('delete-inputs-container');
        
        if (!overlay.classList.contains('d-none')) {
            overlay.classList.add('d-none');
            overlay.classList.remove('d-flex');
            cardInner.classList.remove('border-danger-subtle-thick');
            btnDelete.classList.remove('btn-secondary');
            btnDelete.classList.add('btn-outline-danger');
            btnDelete.innerHTML = 'Hapus';
            const input = document.getElementById('input-delete-' + id);
            if(input) input.remove();
        } else {
            overlay.classList.remove('d-none');
            overlay.classList.add('d-flex');
            cardInner.classList.add('border-danger-subtle-thick');
            btnDelete.classList.remove('btn-outline-danger');
            btnDelete.classList.add('btn-secondary');
            btnDelete.innerHTML = 'Batal';
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'delete_files[]'; input.value = id; input.id = 'input-delete-' + id;
            containerInputs.appendChild(input);
        }
    };
</script>
@endpush