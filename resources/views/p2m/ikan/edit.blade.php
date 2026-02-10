@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            
            {{-- Header Title --}}
            <div class="row justify-content-center mb-4">
                <div class="col-12 col-lg-10">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-1 fw-bold text-dark">Edit Kegiatan P2M</h1>
                            <p class="text-muted mb-0">Perbarui Data Integrasi Kurikulum Anti Narkotika (IKAN)</p>
                        </div>
                        <a href="{{ route('p2m.ikan.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>

            {{-- Form Edit --}}
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="card border-0 shadow-lg">
                        
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="card-title mb-0 fw-bold">
                                Form Edit Data
                            </h5>
                        </div>

                        <div class="card-body p-4 p-lg-5">
                            
                            {{-- ID Form "form-edit" --}}
                            <form action="{{ route('p2m.ikan.update', $kegiatan->id) }}" method="POST" enctype="multipart/form-data" id="form-edit">
                                @csrf
                                @method('PUT')

                                {{-- SECTION 1: DATA KEGIATAN --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">
                                    Data Integrasi Kurikulum Anti Narkotika (IKAN)
                                </h6>

                                <div class="row g-4 mb-5">
                                    {{-- Satuan Kerja (Hanya Admin) --}}
                                    @if (auth()->user()->isAdmin()) 
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label fw-semibold text-secondary small">Satuan Kerja <span class="text-danger">*</span></label>
                                        <select class="form-select @error('satuan_kerja_id') is-invalid @enderror" name="satuan_kerja_id">
                                            <option value="" disabled>-- Pilih Satuan Kerja --</option>
                                            @foreach ($satuanKerjas as $satker)
                                                <option value="{{ $satker->id }}" @selected(old('satuan_kerja_id', $kegiatan->satuan_kerja_id) == $satker->id)>
                                                    {{ $satker->satuan_kerja }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('satuan_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    @endif

                                    {{-- Anggaran --}}
                                    <div class="col-12 col-lg-{{ auth()->user()->isAdmin() ? '6' : '12' }}">
                                        <label class="form-label fw-semibold text-secondary small">Sumber Anggaran <span class="text-danger">*</span></label>
                                        <select class="form-select @error('anggaran_pelaksanaan') is-invalid @enderror" name="anggaran_pelaksanaan">
                                            <option value="DIPA" @selected(old('anggaran_pelaksanaan', $kegiatan->anggaran_pelaksanaan) == 'DIPA')>DIPA</option>
                                            <option value="NON DIPA" @selected(old('anggaran_pelaksanaan', $kegiatan->anggaran_pelaksanaan) == 'NON DIPA')>NON DIPA</option>
                                        </select>
                                        @error('anggaran_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    {{-- Nama Kegiatan --}}
                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-secondary small">Nama Kegiatan <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-lg @error('nama_kegiatan') is-invalid @enderror" name="nama_kegiatan" value="{{ old('nama_kegiatan', $kegiatan->nama_kegiatan) }}" placeholder="Masukkan nama kegiatan">
                                        @error('nama_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>


                                {{-- SECTION 2: DETAIL PELAKSANAAN --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">
                                    Detail Pelaksanaan
                                </h6>

                                <div class="row g-4 mb-5">
                                    {{-- Tanggal --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                                        {{-- Format tanggal untuk value input date harus Y-m-d --}}
                                        <input type="date" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan', $kegiatan->tanggal_pelaksanaan->format('Y-m-d')) }}">
                                        @error('tanggal_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    {{-- Sasaran --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Target Sasaran <span class="text-danger">*</span></label>
                                        <select class="form-select @error('sasaran_kegiatan') is-invalid @enderror" name="sasaran_kegiatan">
                                            <option value="lingkungan pemerintah" @selected(old('sasaran_kegiatan', $kegiatan->sasaran_kegiatan) == 'lingkungan pemerintah')>Lingkungan Pemerintah</option>
                                            <option value="lingkungan pendidikan" @selected(old('sasaran_kegiatan', $kegiatan->sasaran_kegiatan) == 'lingkungan pendidikan')>Lingkungan Pendidikan</option>
                                            <option value="lingkungan masyarakat" @selected(old('sasaran_kegiatan', $kegiatan->sasaran_kegiatan) == 'lingkungan masyarakat')>Lingkungan Masyarakat</option>
                                            <option value="lingkungan swasta" @selected(old('sasaran_kegiatan', $kegiatan->sasaran_kegiatan) == 'lingkungan swasta')>Lingkungan Swasta</option>
                                        </select>
                                        @error('sasaran_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    {{-- Tempat --}}
                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-secondary small">Lokasi / Tempat Kegiatan <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('tempat_kegiatan') is-invalid @enderror" name="tempat_kegiatan" value="{{ old('tempat_kegiatan', $kegiatan->tempat_kegiatan) }}" placeholder="Masukkan lokasi kegiatan">
                                        @error('tempat_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>


                                {{-- SECTION 3: PERSONIL & BUKTI FISIK --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">
                                    Personil & Bukti Fisik
                                </h6>

                                <div class="row g-4 mb-4">
                                    {{-- Pegawai --}}
                                    <div class="col-12 col-lg-8">
                                        <label class="form-label fw-semibold text-secondary small">Pegawai Bertugas <span class="text-danger">*</span></label>
                                        <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Pilih pegawai..." autocomplete="off">
                                            <option value="">Pilih pegawai...</option>
                                            @foreach ($pegawais as $pgw)
                                                {{-- Logic Selected: Mengambil NIP dari relasi kegiatan->pegawai --}}
                                                @php
                                                    $isSelect = collect(old('pegawai_nips', $kegiatan->pegawai->pluck('nip')->toArray()))->contains($pgw->nip);
                                                @endphp
                                                <option value="{{ $pgw->nip }}" @selected($isSelect)>
                                                    {{ $pgw->nama }} ({{ $pgw->nip }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('pegawai_nips') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    {{-- Jumlah Peserta --}}
                                    <div class="col-12 col-lg-4">
                                        <label class="form-label fw-semibold text-secondary small">Jumlah Peserta <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control @error('jumlah_peserta') is-invalid @enderror" name="jumlah_peserta" value="{{ old('jumlah_peserta', $kegiatan->jumlah_peserta) }}" placeholder="0">
                                            <span class="input-group-text bg-light text-secondary">Orang</span>
                                        </div>
                                        @error('jumlah_peserta') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    {{-- DOKUMENTASI HYBRID --}}
                                    <div class="col-12 mt-3">
                                        <div class="bg-light p-4 rounded-3 border border-dashed">
                                            <label class="form-label fw-bold h6 mb-3 text-dark d-block border-bottom pb-2">
                                                <i class="bi bi-images me-2"></i>Pengelolaan Dokumentasi
                                            </label>

                                            {{-- A. FILE LAMA (DATABASE) --}}
                                            @if($kegiatan->dokumentasi->count() > 0)
                                                <p class="small fw-bold text-secondary mb-2">File Tersimpan:</p>
                                                
                                                <div class="row g-3 mb-4" id="existing-files-container">
                                                    @foreach($kegiatan->dokumentasi as $doc)
                                                        @php
                                                            // Cek apakah user sebelumnya menandai file ini untuk dihapus (saat validasi gagal)
                                                            $isMarkedDeleted = old('delete_files') && in_array($doc->id, old('delete_files'));
                                                        @endphp

                                                        <div class="col-6 col-md-4 col-lg-3 file-item" id="file-card-{{ $doc->id }}">
                                                            <div class="card h-100 shadow-sm border border-secondary-subtle position-relative overflow-hidden file-card-inner transition-all {{ $isMarkedDeleted ? 'border-danger-subtle-thick' : '' }}">
                                                                
                                                                {{-- Overlay "AKAN DIHAPUS" --}}
                                                                <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" 
                                                                     style="background-color: rgba(255, 255, 255, 0.9); z-index: 5;">
                                                                    <div class="text-danger mb-1"><i class="bi bi-trash3-fill fs-1"></i></div>
                                                                    <span class="text-danger fw-bold small text-uppercase">Akan Dihapus</span>
                                                                </div>

                                                                {{-- Preview Icon/Image --}}
                                                                <div class="ratio ratio-16x9 bg-secondary bg-opacity-10 border-bottom d-flex align-items-center justify-content-center overflow-hidden">
                                                                    @if(Str::contains($doc->tipe_file, 'image'))
                                                                        <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100">
                                                                    @elseif(Str::contains($doc->tipe_file, 'pdf'))
                                                                        <div class="text-danger"><i class="bi bi-file-earmark-pdf-fill display-4"></i></div>
                                                                    @elseif(Str::contains($doc->tipe_file, ['word', 'officedocument']))
                                                                        <div class="text-primary"><i class="bi bi-file-earmark-word-fill display-4"></i></div>
                                                                    @else
                                                                        <div class="text-secondary"><i class="bi bi-file-earmark-text-fill display-4"></i></div>
                                                                    @endif
                                                                </div>
                                                                
                                                                {{-- Info & Actions --}}
                                                                <div class="card-body p-2 text-center d-flex flex-column justify-content-between">
                                                                    <div class="mb-2">
                                                                        <div class="small text-truncate fw-bold" title="{{ $doc->nama_file_asli }}">
                                                                            {{ $doc->nama_file_asli }}
                                                                        </div>
                                                                        <div class="text-muted" style="font-size: 0.7rem;">
                                                                            {{ $doc->ukuran_file >= 1048576 ? number_format($doc->ukuran_file / 1048576, 2) . ' MB' : number_format($doc->ukuran_file / 1024, 0) . ' KB' }}
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    {{-- Tombol Aksi (Z-Index tinggi) --}}
                                                                    <div class="d-flex gap-1 justify-content-center position-relative" style="z-index: 10;">
                                                                        <a href="{{ route('dokumentasi.download', $doc->id) }}" class="btn btn-outline-primary btn-sm w-100 py-0" style="font-size: 0.75rem;" title="Unduh">
                                                                            <i class="bi bi-download"></i>
                                                                        </a>
                                                                        <button type="button" 
                                                                                id="btn-delete-{{ $doc->id }}"
                                                                                class="btn btn-sm w-100 py-0 {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" 
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
                                                
                                                {{-- Hidden Input Container --}}
                                                <div id="delete-inputs-container">
                                                    @if(old('delete_files'))
                                                        @foreach(old('delete_files') as $deletedId)
                                                            <input type="hidden" name="delete_files[]" value="{{ $deletedId }}" id="input-delete-{{ $deletedId }}">
                                                        @endforeach
                                                    @endif
                                                </div>
                                            @endif

                                            {{-- B. UPLOAD FILE BARU --}}
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

                                {{-- BUTTONS --}}
                                <div class="d-flex flex-column-reverse flex-lg-row justify-content-end gap-2 pt-3 border-top">
                                    <button type="button" onclick="window.location.reload()" class="btn btn-light border text-secondary px-4">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                    </button>
                                    <button type="submit" id="btn-submit" class="btn btn-primary px-5 shadow-sm">
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
    @vite([ 'resources/css/filepond.css', 'resources/js/filepond.js'])
    <style>
        .ts-control { border: 1px solid #dee2e6; padding: 0.5rem 0.75rem; border-radius: 0.375rem; box-shadow: none; }
        .ts-control.focus { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
        .filepond--panel-root { background-color: #ffffff; border: 1px solid #dee2e6; }
        .border-dashed { border-style: dashed !important; border-width: 2px !important; }
        .transition-all { transition: all 0.3s ease; }
        .border-danger-subtle-thick { border-color: #dc3545 !important; border-width: 2px !important; }
    </style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. Tom Select dengan Fitur Clear Button
        if(typeof TomSelect !== 'undefined'){
            new TomSelect("#select-pegawai", {
                create: false,
                sortField: { field: "text", direction: "asc" },
                maxItems: null,
                placeholder: "Pilih pegawai...",
                // PERBAIKAN DISINI: Menambahkan 'clear_button'
                plugins: ['remove_button', 'clear_button'],
                render: {
                    option: function(data, escape) {
                        return '<div class="d-flex align-items-center"><i class="bi bi-person me-2 text-muted"></i>' + escape(data.text) + '</div>';
                    },
                    item: function(data, escape) {
                        return '<div>' + escape(data.text) + '</div>';
                    }
                }
            });
        }

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
            labelIdle: 'Drag & Drop file baru atau <span class="filepond--label-action">Cari File</span>',
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
                e.preventDefault(); 
                e.stopPropagation();
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Upload Belum Selesai',
                        text: 'Silakan tunggu proses upload selesai atau hapus file yang macet.',
                        showConfirmButton: true,
                        confirmButtonText: 'Mengerti',
                        timer: 5000,
                        timerProgressBar: true,
                        allowOutsideClick: true
                    });
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