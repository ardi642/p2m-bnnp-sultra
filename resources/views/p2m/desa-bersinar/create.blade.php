@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            <div class="row justify-content-center mb-4">
                <div class="col-12 col-lg-10">
                    <h1 class="h3 mb-1 fw-bold text-dark">Input Desa Bersinar</h1>
                    <p class="text-muted mb-0">Input Data Pencanangan Desa Bersinar</p>
                </div>
            </div>
            
            @include('p2m.partials.select-p2m-create')

            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="card border-0 shadow-lg">
                        <div class="card-header bg-white py-3 border-bottom"><h5 class="card-title mb-0 fw-bold">Form Input Data</h5></div>
                        <div class="card-body p-4 p-lg-5">
                            <form action="{{ route('p2m.desa-bersinar.store') }}" method="POST" enctype="multipart/form-data" id="form-create">
                                @csrf
                                
                                {{-- Section 1 --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">Informasi Dasar</h6>
                                <div class="row g-4 mb-5">
                                    @if (auth()->user()->isAdmin())    
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label fw-semibold text-secondary small">Satuan Kerja <span class="text-danger">*</span></label>
                                        <select id="select-satker" class="@error('satuan_kerja_id') is-invalid @enderror" name="satuan_kerja_id" placeholder="Pilih Satuan Kerja...">
                                            <option value="">Pilih Satuan Kerja...</option>
                                            @foreach ($satuanKerjas as $satuanKerja)
                                                <option value="{{ $satuanKerja->id }}" @selected(old('satuan_kerja_id') == $satuanKerja->id)>{{ $satuanKerja->satuan_kerja }}</option>
                                            @endforeach
                                        </select>
                                        @error('satuan_kerja_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    @endif

                                    <div class="col-12 col-lg-{{ auth()->user()->isAdmin() ? '6' : '12' }}">
                                        <label class="form-label fw-semibold text-secondary small">Sumber Anggaran <span class="text-danger">*</span></label>
                                        <select id="select-anggaran" class="@error('anggaran_pembentukan') is-invalid @enderror" name="anggaran_pembentukan" placeholder="Pilih Sumber...">
                                            <option value="">Pilih Sumber...</option>
                                            <option value="DIPA" @selected(old('anggaran_pembentukan') == 'DIPA')>DIPA</option>
                                            <option value="NON DIPA" @selected(old('anggaran_pembentukan') == 'NON DIPA')>NON DIPA</option>
                                        </select>
                                        @error('anggaran_pembentukan') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- Section 2 --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">Detail Lokasi & Pelaksanaan</h6>
                                <div class="row g-4 mb-5">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Nama Desa <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('nama_desa') is-invalid @enderror" name="nama_desa" value="{{ old('nama_desa') }}" placeholder="Masukkan nama desa">
                                        @error('nama_desa') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Nama Kelurahan <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('nama_kelurahan') is-invalid @enderror" name="nama_kelurahan" value="{{ old('nama_kelurahan') }}" placeholder="Masukkan nama kelurahan">
                                        @error('nama_kelurahan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-secondary small">Kabupaten / Kota <span class="text-danger">*</span></label>
                                        <select id="select-kabkota" name="kabupaten_kota_id" placeholder="Pilih Kabupaten/Kota..." autocomplete="off">
                                            <option value="">Pilih Kabupaten/Kota...</option>
                                            @foreach ($kabupatens as $kab)
                                                <option value="{{ $kab->id }}" @selected(old('kabupaten_kota_id') == $kab->id)>{{ $kab->nama }}</option>
                                            @endforeach
                                        </select>
                                        @error('kabupaten_kota_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Tanggal Pencanangan <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('tanggal_pencanangan') is-invalid @enderror" name="tanggal_pencanangan" value="{{ old('tanggal_pencanangan') }}">
                                        @error('tanggal_pencanangan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Jumlah Penggiat P4GN <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control @error('jumlah_penggiat') is-invalid @enderror" name="jumlah_penggiat" value="{{ old('jumlah_penggiat') }}" placeholder="Masukkan jumlah penggiat">
                                            <span class="input-group-text bg-light text-secondary">Orang</span>
                                        </div>
                                        @error('jumlah_penggiat') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    
                                    {{-- PERUBAHAN: Keberadaan IBM jadi SELECT --}}
                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-secondary small">Keberadaan IBM <span class="text-danger">*</span></label>
                                        <select class="form-select @error('keberadaan_ibm') is-invalid @enderror" name="keberadaan_ibm">
                                            <option value="" selected disabled>-- Pilih Status IBM --</option>
                                            <option value="Ada" @selected(old('keberadaan_ibm') == 'Ada')>Ada</option>
                                            <option value="Belum Ada" @selected(old('keberadaan_ibm') == 'Belum Ada')>Belum Ada</option>
                                        </select>
                                        @error('keberadaan_ibm') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- Section 3 --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">Personil & Dokumentasi</h6>
                                <div class="row g-4 mb-4">
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label fw-semibold text-secondary small">Penanggung Jawab (Pegawai BNN) <span class="text-danger">*</span></label>
                                        <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Pilih pegawai..." autocomplete="off">
                                            @foreach ($pegawais as $pgw)
                                                <option value="{{ $pgw->nip }}" @selected(collect(old('pegawai_nips'))->contains($pgw->nip))>{{ $pgw->nama }} ({{ $pgw->nip }})</option>
                                            @endforeach
                                        </select>
                                        @error('pegawai_nips') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    {{-- PERUBAHAN: Label & Placeholder --}}
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label fw-semibold text-secondary small">No. HP Penanggung Jawab</label>
                                        <input type="text" class="form-control @error('no_hp_penanggung_jawab') is-invalid @enderror" name="no_hp_penanggung_jawab" value="{{ old('no_hp_penanggung_jawab') }}" placeholder="Masukkan nomor handphone">
                                    </div>
                                    <div class="col-12">
                                        <div class="bg-light p-4 rounded-3 border border-dashed">
                                            <label class="form-label fw-bold h6 mb-1 text-dark"><i class="bi bi-cloud-arrow-up me-2"></i>Upload Dokumentasi</label>
                                            <p class="text-muted small mb-3">Format: .jpg, .png, .pdf. Maks 10MB/file.</p>
                                            <input type="file" class="filepond" name="dokumentasi[]" multiple data-allow-reorder="true" data-max-file-size="10MB" data-max-files="10">
                                            @error('dokumentasi') <div class="alert alert-danger py-2 mt-2 small"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>

                                {{-- Buttons --}}
                                <div class="d-flex flex-column-reverse flex-lg-row justify-content-end gap-2 pt-3 border-top">
                                    <button type="button" onclick="window.location.reload()" class="btn btn-light border text-secondary px-4"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</button>
                                    <button type="submit" id="btn-submit" class="btn btn-primary px-5 shadow-sm"><i class="bi bi-save me-1"></i> Simpan Data</button>
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
        .ts-control { border: 1px solid #dee2e6; padding: 0.5rem 0.75rem; border-radius: 0.375rem; box-shadow: none; }
        .ts-control.focus { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
        .filepond--panel-root { background-color: #ffffff; border: 1px solid #dee2e6; }
        .border-dashed { border-style: dashed !important; border-width: 2px !important; }
    </style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. SETUP TOM SELECT
        if(typeof TomSelect !== 'undefined'){
            // A. TomSelect Umum (Satker, KabKota, dll)
            const commonConfig = { plugins: ['remove_button', 'clear_button'], create: false, maxOptions: null };
            
            ['#select-satker', '#select-kabkota', '#select-anggaran'].forEach(id => {
                if(document.querySelector(id)) new TomSelect(id, commonConfig);
            });

            // B. TomSelect Khusus Pegawai (Ada Icon User & NIP)
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
        const form = document.getElementById('form-create');
        const submitBtn = document.getElementById('btn-submit');
        const originalBtnText = submitBtn.innerHTML;

        // Fungsi Helper: Ubah status tombol submit
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

        // Inisialisasi FilePond
        const pond = FilePond.create(inputElement, {
            acceptedFileTypes: ['image/jpeg', 'image/png', 'application/pdf'],
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

        // Event Listener Submit
        form.addEventListener('submit', function(e) {
            const files = pond.getFiles();
            // Cek apakah ada file yang masih loading/processing (status 2=IDLE, 5=COMPLETE)
            const isBusy = files.some(file => file.status !== 2 && file.status !== 5);

            if (isBusy) {
                e.preventDefault(); 
                e.stopPropagation();
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Upload Belum Selesai',
                        text: 'Silakan tunggu proses upload selesai atau hapus file yang macet.',
                        timer: 3000,
                        timerProgressBar: true
                    });
                } else {
                    alert('Mohon tunggu, file sedang diupload.');
                }
            } else {
                setButtonState(true, 'Menyimpan...');
            }
        });
    });
</script>
@endpush