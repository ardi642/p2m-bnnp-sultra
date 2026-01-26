@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            
            {{-- Header Title --}}
            <div class="row justify-content-center mb-4">
                <div class="col-12 col-lg-10">
                    <h1 class="h3 mb-1 fw-bold text-dark">Input Lingkungan Bersinar</h1>
                    <p class="text-muted mb-0">Input Data Kawasan/Wilayah Bersih Narkoba</p>
                </div>
            </div>
            
            {{-- Partial Select Navigasi --}}
            @include('p2m.partials.select-p2m-create')

            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="card border-0 shadow-lg">
                        
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="card-title mb-0 fw-bold">Form Input Data</h5>
                        </div>

                        <div class="card-body p-4 p-lg-5">
                            
                            <form action="{{ route('p2m.lingkungan-bersinar.store') }}" method="POST" enctype="multipart/form-data" id="form-create">
                                @csrf
                                
                                {{-- SECTION 1: DATA WILAYAH --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">Data Wilayah, Anggaran & Sasaran</h6>
                                
                                <div class="row g-4 mb-5">

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

                                    {{-- Satuan Kerja (Admin Only) --}}
                                    @if (auth()->user()->isAdmin())    
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label fw-semibold text-secondary small">Satuan Kerja <span class="text-danger">*</span></label>
                                        <select class="form-select @error('satuan_kerja_id') is-invalid @enderror" name="satuan_kerja_id">
                                            <option value="" selected disabled>-- Pilih Satuan Kerja --</option>
                                            @foreach ($satuanKerjas as $satuanKerja) 
                                                <option value="{{ $satuanKerja->id }}" @selected(old('satuan_kerja_id') == $satuanKerja->id)>{{ $satuanKerja->satuan_kerja }}</option> 
                                            @endforeach
                                        </select>
                                        @error('satuan_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    @endif
                                    
                                    {{-- Sasaran --}}
                                    <div class="col-12 col-lg-{{ auth()->user()->isAdmin() ? '6' : '12' }}">
                                        <label class="form-label fw-semibold text-secondary small">Target Sasaran <span class="text-danger">*</span></label>
                                        <select class="form-select @error('sasaran_kegiatan') is-invalid @enderror" name="sasaran_kegiatan">
                                            <option value="" selected disabled>-- Pilih Lingkungan --</option>
                                            <option value="sekolah/kampus bersinar" @selected(old('sasaran_kegiatan') == 'sekolah/kampus bersinar')>Sekolah/Kampus Bersinar</option>
                                            <option value="pondok pesantren bersinar" @selected(old('sasaran_kegiatan') == 'pondok pesantren bersinar')>Pondok Pesantren Bersinar</option>
                                            <option value="tempat hiburan bersinar" @selected(old('sasaran_kegiatan') == 'tempat hiburan bersinar')>Tempat Hiburan Bersinar</option>
                                            <option value="tempat wisata bersinar" @selected(old('sasaran_kegiatan') == 'tempat wisata bersinar')>Tempat Wisata Bersinar</option>
                                            <option value="industri bersinar" @selected(old('sasaran_kegiatan') == 'industri bersinar')>Industri Bersinar</option>
                                        </select>
                                        @error('sasaran_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    
                                    {{-- Nama Tempat --}}
                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-secondary small">Nama Tempat / Wilayah / Instansi <span class="text-danger">*</span></label>
                                        {{-- Placeholder mencakup Instansi --}}
                                        <textarea class="form-control @error('nama_tempat_wilayah') is-invalid @enderror" name="nama_tempat_wilayah" rows="3" placeholder="Masukkan nama tempat, wilayah, atau instansi lengkap">{{ old('nama_tempat_wilayah') }}</textarea>
                                        @error('nama_tempat_wilayah') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- SECTION 2: DETAIL PELAKSANAAN --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">Detail Pelaksanaan & Personil</h6>
                                
                                <div class="row g-4 mb-5">
                                    {{-- Tanggal --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Tanggal Pencanangan/Pengukuhan <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('tanggal_pencanangan') is-invalid @enderror" name="tanggal_pencanangan" value="{{ old('tanggal_pencanangan') }}">
                                        @error('tanggal_pencanangan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    
                                    {{-- Jumlah Penggiat --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Jumlah Penggiat P4GN Terbentuk <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control @error('jumlah_penggiat_p4gn') is-invalid @enderror" name="jumlah_penggiat_p4gn" value="{{ old('jumlah_penggiat_p4gn') }}" placeholder="Masukkan jumlah">
                                            <span class="input-group-text bg-light text-secondary">Orang</span>
                                        </div>
                                        @error('jumlah_penggiat_p4gn') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                    
                                    {{-- No HP --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Nomor HP Penanggung Jawab Wilayah</label>
                                        <input type="text" class="form-control @error('no_hp_penanggung_jawab') is-invalid @enderror" name="no_hp_penanggung_jawab" value="{{ old('no_hp_penanggung_jawab') }}" placeholder="Masukkan nomor HP">
                                        @error('no_hp_penanggung_jawab') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    
                                    {{-- Penanggung Jawab (Pivot) --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Penanggung Jawab Wilayah (Pegawai) <span class="text-danger">*</span></label>
                                        <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Pilih pegawai..." autocomplete="off">
                                            <option value="">Pilih pegawai...</option>
                                            @foreach ($pegawais as $pgw) 
                                                <option value="{{ $pgw->nip }}" @selected(collect(old('pegawai_nips'))->contains($pgw->nip))>{{ $pgw->nama }} ({{ $pgw->nip }})</option> 
                                            @endforeach
                                        </select>
                                        @error('pegawai_nips') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- SECTION 3: DOKUMENTASI --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">Dokumentasi</h6>
                                
                                <div class="row g-4 mb-4">
                                    <div class="col-12">
                                        <div class="bg-light p-4 rounded-3 border border-dashed">
                                            <label class="form-label fw-bold h6 mb-1 text-dark"><i class="bi bi-cloud-arrow-up me-2"></i>Upload Dokumentasi</label>
                                            <p class="text-muted small mb-3">Format: .jpg, .png, .pdf, .docx. Maks 10MB/file.</p>
                                            
                                            {{-- Input FilePond --}}
                                            <input type="file" class="filepond" name="dokumentasi[]" multiple data-allow-reorder="true" data-max-file-size="10MB" data-max-files="10">
                                            
                                            @error('dokumentasi') 
                                                <div class="alert alert-danger py-2 mt-2 small">
                                                    <i class="bi bi-exclamation-circle me-1"></i> {{ $message }}
                                                </div> 
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
    @vite([ 'resources/css/filepond.css', 'resources/js/filepond.js'])
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
        
        // 1. Inisialisasi Tom Select
        if(typeof TomSelect !== 'undefined'){
            new TomSelect("#select-pegawai", {
                create: false, 
                sortField: { field: "text", direction: "asc" }, 
                maxItems: null, 
                placeholder: "Pilih pegawai...", 
                plugins: ['remove_button', 'clear_button'],
                render: { 
                    option: function(data, escape) { return '<div class="d-flex align-items-center"><i class="bi bi-person me-2 text-muted"></i>' + escape(data.text) + '</div>'; }, 
                    item: function(data, escape) { return '<div>' + escape(data.text) + '</div>'; } 
                }
            });
        }

        // 2. Logic FilePond dengan Pengaman Submit
        const inputElement = document.querySelector('input.filepond');
        // Mendeteksi Form ID Create
        const form = document.getElementById('form-create');
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
            credits: false,
            allowMultiple: true,
            server: {
                process: { 
                    url: '{{ route('upload.temp') }}', 
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    onload: (response) => { return response; },
                    onerror: (response) => { setButtonState(false); return response; }
                },
                revert: { url: '{{ route('revert.temp') }}', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } },
                load: { url: '{{ route('load.temp') }}/?file=', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }
            },
            
            // Event Listener Visual
            onprocessstart: () => { setButtonState(true, 'Mengupload...'); },
            onprocessfiles: () => { setButtonState(false); },
            onwarning: () => { setButtonState(false); },
            onerror: () => { setButtonState(false); },
            onremovefile: () => {
                const files = pond.getFiles();
                // Status 3=Loading, 9=Queued. Jika ada salah satu, tombol tetap mati.
                const isStillBusy = files.some(file => file.status === 3 || file.status === 9);
                if (!isStillBusy) { setButtonState(false); }
            }
        });

        // 3. Intercept Form Submit (Pencegahan Terakhir)
        if (form) {
            form.addEventListener('submit', function(e) {
                const files = pond.getFiles();
                // Cek status: 2=Idle/Sukses, 5=Processing Complete. Status selain itu = Sibuk.
                const isBusy = files.some(file => file.status !== 2 && file.status !== 5);

                if (isBusy) {
                    e.preventDefault(); 
                    e.stopPropagation();
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Upload Belum Selesai',
                            text: 'Silakan tunggu proses upload selesai atau hapus file yang macet.',
                            showConfirmButton: true, // Tombol Mengerti Ada
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
        }
    });
</script>
@endpush