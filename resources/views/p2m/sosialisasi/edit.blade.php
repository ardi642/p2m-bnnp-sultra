@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            
            {{-- Header --}}
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0">Kegiatan P2M</h1>
                            <p class="text-muted mb-0">Edit Data Kegiatan</p>
                        </div>
                        <a href="{{ route('p2m.sosialisasi.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>

            {{-- Form Edit Card --}}
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="card shadow-lg p-4">
                        <div class="card-header bg-white border-0 pb-0">
                            <h5 class="card-title fw-bold">Edit Data: {{ $kegiatan->nama_kegiatan }}</h5>
                        </div>
                        <div class="card-body">
                            
                            {{-- ID Form "form-edit" PENTING --}}
                            <form action="{{ route('p2m.sosialisasi.update', $kegiatan->id) }}" method="POST" enctype="multipart/form-data" id="form-edit">
                                @csrf
                                @method('PUT') 

                                <div class="row g-4 mb-5">
                                    
                                    {{-- 1. Satuan Kerja (Hanya Admin) --}}
                                    @if (Auth::user()->isAdmin()) 
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="satuan_kerja_id" class="form-label">Satuan Kerja <span class="text-danger">*</span></label>
                                            <select class="form-select @error('satuan_kerja_id') is-invalid @enderror" name="satuan_kerja_id">
                                                <option value="" disabled>Pilih satuan kerja</option>
                                                @foreach ($satuanKerjas as $satker)
                                                    <option value="{{ $satker->id }}" @selected(old('satuan_kerja_id', $kegiatan->satuan_kerja_id) == $satker->id)>
                                                        {{ $satker->satuan_kerja }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('satuan_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    @endif

                                    {{-- 2. Anggaran --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="anggaran_pelaksanaan" class="form-label">Anggaran Pelaksanaan <span class="text-danger">*</span></label>
                                            <select class="form-select @error('anggaran_pelaksanaan') is-invalid @enderror" name="anggaran_pelaksanaan">
                                                <option value="DIPA" @selected(old('anggaran_pelaksanaan', $kegiatan->anggaran_pelaksanaan) == 'DIPA')>DIPA</option>
                                                <option value="NON DIPA" @selected(old('anggaran_pelaksanaan', $kegiatan->anggaran_pelaksanaan) == 'NON DIPA')>NON DIPA</option>
                                            </select>
                                            @error('anggaran_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 3. Nama Kegiatan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('nama_kegiatan') is-invalid @enderror" name="nama_kegiatan" value="{{ old('nama_kegiatan', $kegiatan->nama_kegiatan) }}">
                                            @error('nama_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 4. Sasaran --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Sasaran Kegiatan <span class="text-danger">*</span></label>
                                            <select class="form-select @error('sasaran_kegiatan') is-invalid @enderror" name="sasaran_kegiatan">
                                                <option value="lingkungan pendidikan" @selected(old('sasaran_kegiatan', $kegiatan->sasaran_kegiatan) == 'lingkungan pendidikan')>Lingkungan Pendidikan</option>
                                                <option value="lingkungan kerja" @selected(old('sasaran_kegiatan', $kegiatan->sasaran_kegiatan) == 'lingkungan kerja')>Lingkungan Kerja</option>
                                                <option value="lingkungan masyarakat" @selected(old('sasaran_kegiatan', $kegiatan->sasaran_kegiatan) == 'lingkungan masyarakat')>Lingkungan Masyarakat</option>
                                            </select>
                                            @error('sasaran_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 5. Tanggal --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan', $kegiatan->tanggal_pelaksanaan->format('Y-m-d')) }}">
                                            @error('tanggal_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 6. Tempat --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Tempat Kegiatan <span class="text-danger">*</span></label>
                                            <textarea class="form-control @error('tempat_kegiatan') is-invalid @enderror" rows="1" name="tempat_kegiatan">{{ old('tempat_kegiatan', $kegiatan->tempat_kegiatan) }}</textarea>
                                            @error('tempat_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 7. Pegawai (Tom Select) --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="select-pegawai" class="form-label">Nama Pegawai <span class="text-danger">*</span></label>
                                            <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Pilih Pegawai..." autocomplete="off">
                                                <option value="">Pilih pegawai...</option>
                                                @foreach ($pegawais as $pgw)
                                                    <option value="{{ $pgw->nip }}" 
                                                        @selected(in_array($pgw->nip, old('pegawai_nips', $selectedPegawaiNips)))>
                                                        {{ $pgw->nama }} - NIP: {{ $pgw->nip }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('pegawai_nips') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 8. Jumlah Peserta --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Jumlah Peserta <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('jumlah_peserta') is-invalid @enderror" name="jumlah_peserta" value="{{ old('jumlah_peserta', $kegiatan->jumlah_peserta) }}">
                                            @error('jumlah_peserta') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 9. AREA PENGELOLAAN FILE (Hybrid) --}}
                                    <div class="col-12 mt-4">
                                        <div class="card bg-light border-0">
                                            <div class="card-body">
                                                <label class="form-label fw-bold h5 border-bottom pb-2 d-block mb-3">
                                                    <i class="bi bi-folder2-open me-2"></i>Pengelolaan Dokumentasi
                                                </label>

                                                {{-- A. FILE YANG SUDAH ADA (DATABASE) --}}
                                                @if($kegiatan->dokumentasi->count() > 0)
                                                    <p class="small text-muted mb-2">File yang sudah tersimpan (Klik "Hapus" untuk menandai file yang ingin dibuang saat disimpan):</p>
                                                    
                                                    <div class="row g-3 mb-4" id="existing-files-container">
                                                        @foreach($kegiatan->dokumentasi as $doc)
                                                            @php
                                                                // Cek apakah file ini ditandai hapus sebelumnya (saat gagal validasi)
                                                                $isMarkedDeleted = false;
                                                                if(old('delete_files') && in_array($doc->id, old('delete_files'))) {
                                                                    $isMarkedDeleted = true;
                                                                }
                                                            @endphp

                                                            <div class="col-6 col-md-4 col-lg-3 file-item" id="file-card-{{ $doc->id }}">
                                                                <div class="card h-100 shadow-sm border border-secondary-subtle position-relative overflow-hidden file-card-inner transition-all {{ $isMarkedDeleted ? 'border-danger-subtle-thick' : '' }}">
                                                                    
                                                                    {{-- Overlay "AKAN DIHAPUS" --}}
                                                                    {{-- Z-Index 5 agar di atas gambar, tapi di bawah tombol --}}
                                                                    <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" 
                                                                         style="background-color: rgba(33, 37, 41, 0.85); backdrop-filter: blur(2px); z-index: 5;">
                                                                        <div class="bg-danger rounded-circle p-3 mb-2 shadow">
                                                                            <i class="bi bi-trash3-fill text-white fs-2"></i>
                                                                        </div>
                                                                        <span class="text-white fw-bold text-uppercase small ls-1">Akan Dihapus</span>
                                                                    </div>

                                                                    {{-- Preview Icon/Image --}}
                                                                    <div class="ratio ratio-1x1 bg-light border-bottom d-flex align-items-center justify-content-center overflow-hidden">
                                                                        @if(Str::contains($doc->tipe_file, 'image'))
                                                                            <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100">
                                                                        @elseif(Str::contains($doc->tipe_file, 'pdf'))
                                                                            <div class="text-danger"><i class="bi bi-file-earmark-pdf-fill display-3"></i></div>
                                                                        @elseif(Str::contains($doc->tipe_file, ['word', 'officedocument']))
                                                                            <div class="text-primary"><i class="bi bi-file-earmark-word-fill display-3"></i></div>
                                                                        @elseif(Str::contains($doc->tipe_file, ['excel', 'spreadsheet']))
                                                                            <div class="text-success"><i class="bi bi-file-earmark-excel-fill display-3"></i></div>
                                                                        @else
                                                                            <div class="text-secondary"><i class="bi bi-file-earmark-text-fill display-3"></i></div>
                                                                        @endif
                                                                    </div>
                                                                    
                                                                    {{-- Info & Actions --}}
                                                                    <div class="card-body p-2 text-center bg-white d-flex flex-column justify-content-between">
                                                                        <div class="mb-2">
                                                                            <div class="small text-truncate fw-bold mb-1" title="{{ $doc->nama_file_asli }}">
                                                                                {{ $doc->nama_file_asli }}
                                                                            </div>
                                                                            <div class="text-muted x-small font-monospace" style="font-size: 0.7rem;">
                                                                                {{ $doc->ukuran_file >= 1048576 ? number_format($doc->ukuran_file / 1048576, 2) . ' MB' : number_format($doc->ukuran_file / 1024, 0) . ' KB' }}
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        {{-- TOMBOL AKSI (Z-Index 10 agar bisa diklik di atas overlay) --}}
                                                                        <div class="d-flex flex-column gap-2 position-relative" style="z-index: 10;">
                                                                            <div class="d-flex gap-1 justify-content-center">
                                                                                @if(Str::contains($doc->tipe_file, ['image', 'pdf', 'video']))
                                                                                    <a href="{{ Storage::url($doc->path_file) }}" target="_blank" class="btn btn-outline-info btn-sm w-50 py-0" style="font-size: 0.75rem;">
                                                                                        <i class="bi bi-eye"></i> Lihat
                                                                                    </a>
                                                                                @endif
                                                                                
                                                                                <a href="{{ route('dokumentasi.download', $doc->id) }}" class="btn btn-outline-primary btn-sm {{ Str::contains($doc->tipe_file, ['image', 'pdf', 'video']) ? 'w-50' : 'w-100' }} py-0" style="font-size: 0.75rem;">
                                                                                    <i class="bi bi-download"></i> Unduh
                                                                                </a>
                                                                            </div>

                                                                            <button type="button" 
                                                                                    id="btn-delete-{{ $doc->id }}"
                                                                                    class="btn btn-sm w-100 py-0 shadow-sm {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" 
                                                                                    onclick="markForDeletion({{ $doc->id }})">
                                                                                @if($isMarkedDeleted)
                                                                                    <i class="bi bi-arrow-counterclockwise"></i> Batal Hapus
                                                                                @else
                                                                                    <i class="bi bi-trash"></i> Hapus
                                                                                @endif
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    
                                                    {{-- Hidden Input untuk Delete Files --}}
                                                    <div id="delete-inputs-container">
                                                        @if(old('delete_files'))
                                                            @foreach(old('delete_files') as $deletedId)
                                                                <input type="hidden" name="delete_files[]" value="{{ $deletedId }}" id="input-delete-{{ $deletedId }}">
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                @endif

                                                {{-- B. UPLOAD FILE BARU (FILEPOND) --}}
                                                <div class="mb-2">
                                                    <label class="form-label fw-bold small text-uppercase">Upload File Baru (Opsional)</label>
                                                    <input type="file" 
                                                        class="filepond"
                                                        name="dokumentasi[]" 
                                                        multiple 
                                                        data-allow-reorder="true"
                                                        data-max-file-size="10MB"
                                                        data-max-files="10">
                                                    <small class="text-muted">File baru akan ditambahkan.</small>
                                                    @error('dokumentasi') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div> {{-- End Row --}}

                                {{-- BUTTONS ACTIONS --}}
                                <div class="row justify-content-end">
                                    <div class="col-12 col-lg-auto">
                                        {{-- ID "btn-submit" penting untuk JS --}}
                                        <button type="submit" id="btn-submit" class="btn btn-warning text-dark w-100 mb-4 mb-lg-0">
                                            <i class="bi bi-pencil-square me-1"></i> Perbarui Data
                                        </button>
                                    </div>
                                    <div class="col-12 col-lg-auto">
                                        {{-- Reset menggunakan Reload --}}
                                        <button type="button" onclick="window.location.reload()" class="btn btn-secondary w-100 mb-4 mb-lg-0">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Data
                                        </button>
                                    </div>
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
        .x-small { font-size: 0.75rem; }
        .ls-1 { letter-spacing: 0.05em; }
        .transition-all { transition: all 0.3s ease; }
        
        .border-danger-subtle-thick {
            border-color: #dc3545 !important;
            border-width: 2px !important;
            box-shadow: 0 0 10px rgba(220, 53, 69, 0.2) !important;
        }
    </style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. Tom Select
        if(typeof TomSelect !== 'undefined'){
            new TomSelect("#select-pegawai", {
                create: false,
                sortField: { field: "text", direction: "asc" },
                maxItems: null,
                placeholder: "Cari atau pilih pegawai...",
                plugins: ['remove_button'],
            });
        }

        // 2. Definisi Elemen
        const inputElement = document.querySelector('input.filepond');
        const form = document.getElementById('form-edit');
        const submitBtn = document.getElementById('btn-submit');
        const originalBtnText = submitBtn.innerHTML;

        const setButtonState = (isLoading, text = null) => {
            if (isLoading) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + (text || 'Mengupload...');
            } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        };

        // 3. Konfigurasi FilePond
        const pond = FilePond.create(inputElement, {
            acceptedFileTypes: ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            labelIdle: 'Drag & Drop file baru atau <span class="filepond--label-action">Cari File</span>',
            imagePreviewHeight: 120,
            credits: false,
            allowMultiple: true,

            // Files dari OLD input
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
                    onerror: (response) => {
                        setButtonState(false);
                        return response;
                    }
                },
                revert: { url: '{{ route('revert.temp') }}', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } },
                load: { url: '{{ route('load.temp') }}/?file=', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } },
            },

            // --- PROTEKSI VISUAL: HANYA SAAT UPLOAD KE SERVER ---
            onprocessstart: () => { setButtonState(true); },
            onprocessfiles: () => { setButtonState(false); },
            onremovefile: () => {
                const files = pond.getFiles();
                // Status 3=Uploading, 9=Queued. (Status 2=Local Idle tidak dihitung)
                const isStillBusy = files.some(file => file.status === 3 || file.status === 9);
                if (!isStillBusy) { setButtonState(false); }
            }
        });

        // 4. Proteksi Intercept Submit
        form.addEventListener('submit', function(e) {
            const files = pond.getFiles();
            
            // BUSY jika Status 3 (Uploading) atau 9 (Queued)
            // KITA ABAIKAN STATUS 6 (ERROR) agar user tetap bisa submit meski ada file gagal (user harus hapus manual file merahnya)
            const isBusy = files.some(file => file.status === 3 || file.status === 9);

            if (isBusy) {
                e.preventDefault(); 
                e.stopPropagation();
                setButtonState(true, 'Tunggu Upload...');
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Mohon Tunggu',
                        text: 'File sedang diproses. Tunggu hingga selesai.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert('Mohon tunggu, file sedang diupload ke server.');
                }
            } else {
                // Jika lolos cek, kunci tombol agar user tahu sedang menyimpan data teks
                setButtonState(true, 'Menyimpan...');
            }
        });
    });

    // 5. Logic Hapus File Lama
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
            btnDelete.innerHTML = '<i class="bi bi-trash"></i> Hapus';
            
            const input = document.getElementById('input-delete-' + id);
            if(input) input.remove();
        } else {
            // TANDAI HAPUS
            overlay.classList.remove('d-none');
            overlay.classList.add('d-flex');
            cardInner.classList.add('border-danger-subtle-thick');
            
            btnDelete.classList.remove('btn-outline-danger');
            btnDelete.classList.add('btn-secondary');
            btnDelete.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> Batal Hapus';
            
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'delete_files[]'; input.value = id; input.id = 'input-delete-' + id;
            containerInputs.appendChild(input);
        }
    };
</script>
@endpush