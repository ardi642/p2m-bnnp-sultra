@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            <div class="row justify-content-center mb-4">
                <div class="col-12 col-lg-10">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-1 fw-bold text-dark">Edit Desa Bersinar</h1>
                            <p class="text-muted mb-0">Perbarui Data Pencanangan</p>
                        </div>
                        <a href="{{ route('p2m.desa-bersinar.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
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
                            <form action="{{ route('p2m.desa-bersinar.update', $desa->id) }}" method="POST" enctype="multipart/form-data" id="form-edit">
                                @csrf 
                                @method('PUT')
                                
                                {{-- Section 1: Informasi Dasar --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">Informasi Dasar</h6>
                                <div class="row g-4 mb-5">
                                    @if (auth()->user()->isAdmin())    
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label fw-semibold text-secondary small">Satuan Kerja <span class="text-danger">*</span></label>
                                        <select id="select-satker" class="@error('satuan_kerja_id') is-invalid @enderror" name="satuan_kerja_id">
                                            @foreach ($satuanKerjas as $satuanKerja)
                                                <option value="{{ $satuanKerja->id }}" @selected(old('satuan_kerja_id', $desa->satuan_kerja_id) == $satuanKerja->id)>{{ $satuanKerja->satuan_kerja }}</option>
                                            @endforeach
                                        </select>
                                        @error('satuan_kerja_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    @endif

                                    <div class="col-12 col-lg-{{ auth()->user()->isAdmin() ? '6' : '12' }}">
                                        <label class="form-label fw-semibold text-secondary small">Sumber Anggaran <span class="text-danger">*</span></label>
                                        <select id="select-anggaran" class="@error('anggaran_pembentukan') is-invalid @enderror" name="anggaran_pembentukan">
                                            <option value="DIPA" @selected(old('anggaran_pembentukan', $desa->anggaran_pembentukan) == 'DIPA')>DIPA</option>
                                            <option value="NON DIPA" @selected(old('anggaran_pembentukan', $desa->anggaran_pembentukan) == 'NON DIPA')>NON DIPA</option>
                                        </select>
                                        @error('anggaran_pembentukan') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- Section 2: Detail Lokasi & Pelaksanaan --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">Detail Lokasi & Pelaksanaan</h6>
                                <div class="row g-4 mb-5">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Nama Desa <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('nama_desa') is-invalid @enderror" name="nama_desa" value="{{ old('nama_desa', $desa->nama_desa) }}" placeholder="Masukkan nama desa">
                                        @error('nama_desa') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Nama Kelurahan <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('nama_kelurahan') is-invalid @enderror" name="nama_kelurahan" value="{{ old('nama_kelurahan', $desa->nama_kelurahan) }}" placeholder="Masukkan nama kelurahan">
                                        @error('nama_kelurahan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    
                                    {{-- PERBAIKAN UTAMA: KABUPATEN KOTA --}}
                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-secondary small">Kabupaten / Kota <span class="text-danger">*</span></label>
                                        
                                        {{-- Class is-invalid ditambahkan agar CSS TomSelect mendeteksi error --}}
                                        <select id="select-kabkota" name="kabupaten_kota_id" class="@error('kabupaten_kota_id') is-invalid @enderror" placeholder="Pilih Kabupaten/Kota..." autocomplete="off">
                                            
                                            {{-- WAJIB: Option kosong agar browser tidak auto-select opsi pertama saat error --}}
                                            <option value="">Pilih Kabupaten/Kota...</option>

                                            @foreach ($kabupatens as $kab)
                                                <option value="{{ $kab->id }}" @selected(old('kabupaten_kota_id', $desa->kabupaten_kota_id) == $kab->id)>{{ $kab->nama }}</option>
                                            @endforeach
                                        </select>
                                        
                                        @error('kabupaten_kota_id') 
                                            <div class="text-danger small mt-1">
                                                <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                            </div> 
                                        @enderror
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Tanggal Pencanangan <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('tanggal_pencanangan') is-invalid @enderror" name="tanggal_pencanangan" value="{{ old('tanggal_pencanangan', $desa->tanggal_pencanangan->format('Y-m-d')) }}">
                                        @error('tanggal_pencanangan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Jumlah Penggiat P4GN <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control @error('jumlah_penggiat') is-invalid @enderror" name="jumlah_penggiat" value="{{ old('jumlah_penggiat', $desa->jumlah_penggiat) }}" placeholder="Masukkan jumlah penggiat">
                                            <span class="input-group-text bg-light text-secondary">Orang</span>
                                        </div>
                                        @error('jumlah_penggiat') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-secondary small">Keberadaan IBM <span class="text-danger">*</span></label>
                                        <select class="form-select @error('keberadaan_ibm') is-invalid @enderror" name="keberadaan_ibm">
                                            <option value="" disabled>-- Pilih Status IBM --</option>
                                            <option value="Ada" @selected(old('keberadaan_ibm', $desa->keberadaan_ibm) == 'Ada')>Ada</option>
                                            <option value="Belum Ada" @selected(old('keberadaan_ibm', $desa->keberadaan_ibm) == 'Belum Ada')>Belum Ada</option>
                                        </select>
                                        @error('keberadaan_ibm') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- Section 3: Personil & Dokumentasi --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">Personil & Dokumentasi</h6>
                                <div class="row g-4 mb-4">
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label fw-semibold text-secondary small">Penanggung Jawab (Pegawai BNN) <span class="text-danger">*</span></label>
                                        <select id="select-pegawai" name="pegawai_nips[]" class="@error('pegawai_nips') is-invalid @enderror" multiple placeholder="Pilih pegawai..." autocomplete="off">
                                            @foreach ($pegawais as $pgw)
                                                <option value="{{ $pgw->nip }}" @selected(collect(old('pegawai_nips', $selectedPegawaiNips))->contains($pgw->nip))>{{ $pgw->nama }} ({{ $pgw->nip }})</option>
                                            @endforeach
                                        </select>
                                        @error('pegawai_nips') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label fw-semibold text-secondary small">No. HP Penanggung Jawab</label>
                                        <input type="text" class="form-control @error('no_hp_penanggung_jawab') is-invalid @enderror" name="no_hp_penanggung_jawab" value="{{ old('no_hp_penanggung_jawab', $desa->no_hp_penanggung_jawab) }}" placeholder="Masukkan nomor handphone">
                                        @error('no_hp_penanggung_jawab') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-12 mt-3">
                                        <div class="bg-light p-4 rounded-3 border border-dashed">
                                            <label class="form-label fw-bold h6 mb-3 text-dark d-block border-bottom pb-2"><i class="bi bi-images me-2"></i>Pengelolaan Dokumentasi</label>
                                            
                                            {{-- File Lama (Database) --}}
                                            @if($desa->dokumentasi->count() > 0)
                                                <p class="small fw-bold text-secondary mb-2">File Tersimpan:</p>
                                                <div class="row g-3 mb-4" id="existing-files-container">
                                                    @foreach($desa->dokumentasi as $doc)
                                                        @php $isMarkedDeleted = old('delete_files') && in_array($doc->id, old('delete_files')); @endphp
                                                        <div class="col-6 col-md-4 col-lg-3 file-item" id="file-card-{{ $doc->id }}">
                                                            <div class="card h-100 shadow-sm border border-secondary-subtle position-relative overflow-hidden file-card-inner transition-all {{ $isMarkedDeleted ? 'border-danger-subtle-thick' : '' }}">
                                                                <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" style="background-color: rgba(255, 255, 255, 0.9); z-index: 5;"><div class="text-danger mb-1"><i class="bi bi-trash3-fill fs-1"></i></div><span class="text-danger fw-bold small text-uppercase">Akan Dihapus</span></div>
                                                                <div class="ratio ratio-16x9 bg-secondary bg-opacity-10 border-bottom d-flex align-items-center justify-content-center overflow-hidden">
                                                                    @if(Str::contains($doc->tipe_file, 'image')) <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100">
                                                                    @elseif(Str::contains($doc->tipe_file, 'pdf')) <div class="text-danger"><i class="bi bi-file-earmark-pdf-fill display-4"></i></div>
                                                                    @else <div class="text-secondary"><i class="bi bi-file-earmark-text-fill display-4"></i></div> @endif
                                                                </div>
                                                                <div class="card-body p-2 text-center d-flex flex-column justify-content-between">
                                                                    <div class="mb-2"><div class="small text-truncate fw-bold" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</div></div>
                                                                    <div class="d-flex gap-1 justify-content-center position-relative" style="z-index: 10;">
                                                                        <a href="{{ route('dokumentasi.download', $doc->id) }}" class="btn btn-outline-primary btn-sm w-100 py-0"><i class="bi bi-download"></i></a>
                                                                        <button type="button" id="btn-delete-{{ $doc->id }}" class="btn btn-sm w-100 py-0 {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" onclick="markForDeletion({{ $doc->id }})">@if($isMarkedDeleted) Batal @else Hapus @endif</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <div id="delete-inputs-container">@if(old('delete_files')) @foreach(old('delete_files') as $deletedId) <input type="hidden" name="delete_files[]" value="{{ $deletedId }}" id="input-delete-{{ $deletedId }}"> @endforeach @endif</div>
                                            @endif

                                            {{-- Upload File Baru (FilePond) --}}
                                            <p class="small fw-bold text-secondary mb-1 mt-2">Upload File Baru (Opsional):</p>
                                            <input type="file" class="filepond" name="dokumentasi[]" multiple data-allow-reorder="true" data-max-file-size="10MB" data-max-files="10">
                                            @error('dokumentasi') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>

                                {{-- Buttons --}}
                                <div class="d-flex flex-column-reverse flex-lg-row justify-content-end gap-2 pt-3 border-top">
                                    <button type="button" onclick="window.location.reload()" class="btn btn-light border text-secondary px-4"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</button>
                                    <button type="submit" id="btn-submit" class="btn btn-primary px-5 shadow-sm"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
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
        .ts-control { border: 1px solid #dee2e6; padding: 0.5rem 0.75rem; border-radius: 0.375rem; } 
        .ts-control.focus { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
        
        /* FIX: CSS agar TomSelect jadi merah saat Validasi Error */
        .is-invalid + .ts-wrapper .ts-control {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5zM6 8.2h.01'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        .filepond--panel-root { background-color: #ffffff; border: 1px solid #dee2e6; } 
        .border-danger-subtle-thick { border-color: #dc3545 !important; border-width: 2px !important; } 
    </style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. SETUP TOM SELECT
        if(typeof TomSelect !== 'undefined'){
            // A. TomSelect Umum
            const commonConfig = { plugins: ['remove_button', 'clear_button'], create: false, maxOptions: null };
            ['#select-satker', '#select-kabkota', '#select-anggaran'].forEach(id => {
                if(document.querySelector(id)) new TomSelect(id, commonConfig);
            });

            // B. TomSelect Pegawai (Render Icon)
            new TomSelect("#select-pegawai", {
                create: false,
                sortField: { field: "text", direction: "asc" },
                placeholder: "Pilih pegawai...",
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

        // 2. SETUP FILEPOND & FORM HANDLING
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
            acceptedFileTypes: ['image/jpeg', 'image/png', 'application/pdf'],
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
                        icon: 'warning', title: 'Upload Belum Selesai',
                        text: 'Silakan tunggu proses upload selesai atau hapus file yang macet.',
                        timer: 3000, timerProgressBar: true
                    });
                } else {
                    alert('Mohon tunggu, file sedang diupload.');
                }
            } else {
                setButtonState(true, 'Menyimpan...');
            }
        });
    });

    // 3. FUNGSI HAPUS FILE LAMA (MARK FOR DELETION)
    window.markForDeletion = function(id) {
        const cardInner = document.querySelector('#file-card-' + id + ' .file-card-inner');
        const overlay = cardInner.querySelector('.delete-overlay');
        const btnDelete = document.getElementById('btn-delete-' + id);
        const containerInputs = document.getElementById('delete-inputs-container');
        
        if (!overlay.classList.contains('d-none')) {
            // BATAL HAPUS
            overlay.classList.add('d-none'); overlay.classList.remove('d-flex');
            cardInner.classList.remove('border-danger-subtle-thick');
            btnDelete.classList.remove('btn-secondary'); btnDelete.classList.add('btn-outline-danger'); 
            btnDelete.innerHTML = 'Hapus';
            const input = document.getElementById('input-delete-' + id); 
            if(input) input.remove();
        } else {
            // TANDAI HAPUS
            overlay.classList.remove('d-none'); overlay.classList.add('d-flex');
            cardInner.classList.add('border-danger-subtle-thick');
            btnDelete.classList.remove('btn-outline-danger'); btnDelete.classList.add('btn-secondary'); 
            btnDelete.innerHTML = 'Batal';
            const input = document.createElement('input'); 
            input.type = 'hidden'; input.name = 'delete_files[]'; input.value = id; input.id = 'input-delete-' + id;
            containerInputs.appendChild(input);
        }
    };
</script>
@endpush