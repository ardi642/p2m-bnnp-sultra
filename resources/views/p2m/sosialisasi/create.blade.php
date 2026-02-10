@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            
            <div class="row justify-content-center mb-4">
                <div class="col-12 col-lg-10">
                    <h1 class="h3 mb-1 fw-bold text-dark">Input Kegiatan P2M</h1>
                    <p class="text-muted mb-0">Kegiatan P2M Sosialisasi Konvensional / Tatap Muka</p>
                </div>
            </div>
            
            @include('p2m.partials.select-p2m-create')

            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="card border-0 shadow-lg">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="card-title mb-0 fw-bold">Form Input Data</h5>
                        </div>

                        <div class="card-body p-4 p-lg-5">
                            
                            <form action="{{ route('p2m.sosialisasi.store') }}" method="POST" enctype="multipart/form-data" id="form-create">
                                @csrf
                                
                                {{-- SECTION 1: DATA KEGIATAN --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">
                                    Data Sosialisasi Tatap Muka / Konvensional
                                </h6>

                                <div class="row g-4 mb-5">
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

                                    <div class="col-12 col-lg-{{ auth()->user()->isAdmin() ? '6' : '12' }}">
                                        <label class="form-label fw-semibold text-secondary small">Sumber Anggaran <span class="text-danger">*</span></label>
                                        <select class="form-select @error('anggaran_pelaksanaan') is-invalid @enderror" name="anggaran_pelaksanaan">
                                            <option value="" disabled selected>-- Pilih Sumber --</option>
                                            <option value="DIPA" @selected(old('anggaran_pelaksanaan') == 'DIPA')>DIPA</option>
                                            <option value="NON DIPA" @selected(old('anggaran_pelaksanaan') == 'NON DIPA')>NON DIPA</option>
                                        </select>
                                        @error('anggaran_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-secondary small">Nama Kegiatan <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-lg @error('nama_kegiatan') is-invalid @enderror" name="nama_kegiatan" value="{{ old('nama_kegiatan') }}" placeholder="Masukkan nama kegiatan">
                                        @error('nama_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>


                                {{-- SECTION 2: DETAIL PELAKSANAAN --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">
                                    Detail Pelaksanaan
                                </h6>

                                <div class="row g-4 mb-5">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan') }}">
                                        @error('tanggal_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Target Sasaran <span class="text-danger">*</span></label>
                                        <select class="form-select @error('sasaran_kegiatan') is-invalid @enderror" name="sasaran_kegiatan">
                                            <option value="" selected disabled>-- Pilih Lingkungan --</option>
                                            <option value="lingkungan kerja" @selected(old('sasaran_kegiatan') == 'lingkungan kerja')>Lingkungan Kerja</option>
                                            <option value="lingkungan pendidikan" @selected(old('sasaran_kegiatan') == 'lingkungan pendidikan')>Lingkungan Pendidikan</option>
                                            <option value="lingkungan masyarakat" @selected(old('sasaran_kegiatan') == 'lingkungan masyarakat')>Lingkungan Masyarakat</option>
                                            <option value="lingkungan swasta" @selected(old('sasaran_kegiatan') == 'lingkungan swasta')>Lingkungan Swasta</option>
                                        </select>
                                        @error('sasaran_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-secondary small">Lokasi / Tempat Kegiatan <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('tempat_kegiatan') is-invalid @enderror" name="tempat_kegiatan" value="{{ old('tempat_kegiatan') }}" placeholder="Masukkan lokasi kegiatan">
                                        @error('tempat_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>


                                {{-- SECTION 3: PERSONIL & BUKTI FISIK --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">
                                    Personil & Bukti Fisik
                                </h6>

                                <div class="row g-4 mb-4">
                                    <div class="col-12 col-lg-8">
                                        <label class="form-label fw-semibold text-secondary small">Pegawai Bertugas <span class="text-danger">*</span></label>
                                        <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Pilih pegawai..." autocomplete="off">
                                            <option value="">Pilih pegawai...</option>
                                            @foreach ($pegawais as $pgw)
                                                <option value="{{ $pgw->nip }}" @selected(collect(old('pegawai_nips'))->contains($pgw->nip))>
                                                    {{ $pgw->nama }} ({{ $pgw->nip }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('pegawai_nips') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    <div class="col-12 col-lg-4">
                                        <label class="form-label fw-semibold text-secondary small">Jumlah Peserta <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control @error('jumlah_peserta') is-invalid @enderror" name="jumlah_peserta" value="{{ old('jumlah_peserta') }}" placeholder="0">
                                            <span class="input-group-text bg-light text-secondary">Orang</span>
                                        </div>
                                        @error('jumlah_peserta') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                    </div>

                                    {{-- INPUT 1: DOKUMENTASI --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="bg-light p-3 rounded-3 border border-dashed h-100">
                                            <label class="form-label fw-bold small text-primary mb-2">
                                                <i class="bi bi-folder2-open me-2"></i>Dokumentasi
                                            </label>
                                            {{-- Keterangan Umum --}}
                                            <p class="text-muted small mb-2" style="font-size: 0.75rem">Upload file dokumentasi kegiatan. Maksimal 10MB.</p>
                                            
                                            <input type="file" id="fp-dokumentasi" name="dokumentasi[]" multiple>
                                            @error('dokumentasi') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- INPUT 2: LAMPIRAN PENDUKUNG --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="bg-light p-3 rounded-3 border border-dashed h-100">
                                            <label class="form-label fw-bold small text-danger mb-2">
                                                <i class="bi bi-paperclip me-2"></i>Lampiran Pendukung
                                            </label>
                                            {{-- Keterangan Umum (Tanpa SK/Surat) --}}
                                            <p class="text-muted small mb-2" style="font-size: 0.75rem">Upload file lampiran pendukung kegiatan. Maksimal 10MB.</p>
                                            
                                            <input type="file" id="fp-lampiran" name="lampiran[]" multiple>
                                            @error('lampiran') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div> 

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
    @vite(['resources/css/filepond.css'])
    
    <style>
        .ts-control { border: 1px solid #dee2e6; padding: 0.5rem 0.75rem; border-radius: 0.375rem; box-shadow: none; }
        .ts-control.focus { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
        .filepond--panel-root { background-color: #ffffff; border: 1px solid #dee2e6; }
        .border-dashed { border-style: dashed !important; border-width: 2px !important; }
        .filepond--item { width: 100%; }
    </style>
@endpush

@push('scripts')
{{-- Load JS FilePond via Vite --}}
@vite(['resources/js/filepond.js'])

<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        
        // 1. TOM SELECT
        if(typeof TomSelect !== 'undefined'){
            new TomSelect("#select-pegawai", {
                create: false,
                sortField: { field: "text", direction: "asc" },
                maxItems: null,
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

        // 2. FILEPOND MANAGER
        
        const commonConfig = {
            uploadRoute: '{{ route('upload.temp') }}',
            revertRoute: '{{ route('revert.temp') }}',
            loadRoute:   '{{ route('load.temp') }}',
            csrfToken:   '{{ csrf_token() }}',
            submitBtnId: 'btn-submit'
        };

        if (window.FilePondManager) {
            
            // A. Init Dokumentasi (Format Bebas, Max 10MB)
            window.FilePondManager.create('#fp-dokumentasi', {
                ...commonConfig,
                maxSize: '10MB',
                existingFiles: @json(old('dokumentasi', [])),
            });

            // B. Init Lampiran Pendukung (Format Bebas, Max 10MB)
            window.FilePondManager.create('#fp-lampiran', {
                ...commonConfig,
                maxSize: '10MB',
                existingFiles: @json(old('lampiran', [])),
            });

            // C. Validasi Submit
            window.FilePondManager.attachFormSubmit('form-create', 'btn-submit');

        } else {
            console.error("FilePondManager belum dimuat. Pastikan 'npm run build' atau 'npm run dev' berjalan.");
        }
        
    });
</script>
@endpush