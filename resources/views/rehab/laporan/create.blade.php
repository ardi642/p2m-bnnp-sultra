@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4">
        
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Input Laporan Harian</h4>
                <p class="text-secondary small mb-0">Input data realisasi Rawat Jalan, Pasca Rehab, dan SKHPN</p>
            </div>
            <a href="{{ route('rehab.laporan.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        {{-- ERROR ALERT --}}
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div><strong>Input Tidak Valid!</strong> Silakan periksa kolom yang berwarna merah.</div>
                </div>
            </div>
        @endif

        <form action="{{ route('rehab.laporan.store') }}" method="POST" enctype="multipart/form-data" id="form-rehab">
            @csrf

            <div class="row g-4">
                {{-- 1. INFORMASI UMUM --}}
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold m-0 text-primary"><i class="bi bi-calendar-event me-2"></i>Informasi Umum</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-secondary">Tanggal Laporan <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal" class="form-control @error('tanggal') is-invalid @enderror" 
                                       value="{{ old('tanggal', date('Y-m-d')) }}">
                                @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            @if(auth()->user()->isAdmin())
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-secondary">Satuan Kerja <span class="text-danger">*</span></label>
                                <select name="satuan_kerja_id" class="form-select @error('satuan_kerja_id') is-invalid @enderror">
                                    <option value="">-- Pilih Satker --</option>
                                    @foreach($satuanKerjas as $sk)
                                        <option value="{{ $sk->id }}" {{ old('satuan_kerja_id') == $sk->id ? 'selected' : '' }}>{{ $sk->satuan_kerja }}</option>
                                    @endforeach
                                </select>
                                @error('satuan_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- 2. DATA REALISASI --}}
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold m-0 text-success"><i class="bi bi-graph-up-arrow me-2"></i>Data Realisasi</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="p-3 border rounded bg-info-subtle bg-opacity-10 text-center">
                                        <label class="form-label fw-bold text-info-emphasis mb-2">Rawat Jalan</label>
                                        <input type="number" name="realisasi_rawat_jalan" 
                                               class="form-control form-control-lg text-center fw-bold text-info-emphasis @error('realisasi_rawat_jalan') is-invalid @enderror" 
                                               value="{{ old('realisasi_rawat_jalan', 0) }}" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded bg-success-subtle bg-opacity-10 text-center">
                                        <label class="form-label fw-bold text-success mb-2">Pasca Rehab</label>
                                        <input type="number" name="realisasi_pasca_rehab" 
                                               class="form-control form-control-lg text-center fw-bold text-success @error('realisasi_pasca_rehab') is-invalid @enderror" 
                                               value="{{ old('realisasi_pasca_rehab', 0) }}" min="0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded bg-warning-subtle bg-opacity-10 text-center">
                                        <label class="form-label fw-bold text-warning-emphasis mb-2">SKHPN</label>
                                        <input type="number" name="realisasi_skhpn" 
                                               class="form-control form-control-lg text-center fw-bold text-warning-emphasis @error('realisasi_skhpn') is-invalid @enderror" 
                                               value="{{ old('realisasi_skhpn', 0) }}" min="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. DOKUMENTASI (FilePond) --}}
                <div class="col-12">
                    <div class="card shadow-sm border-0 mb-5">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="fw-bold m-0 text-secondary"><i class="bi bi-paperclip me-2"></i>Dokumentasi Kegiatan</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="bg-body-tertiary p-4 rounded-3 border border-dashed">
                                <label class="form-label fw-bold small text-dark mb-2">Upload Foto / Dokumen</label>
                                <p class="text-muted small mb-3">Format: JPG, PNG, PDF. Maksimal 10MB per file.</p>
                                <input type="file" id="fp-dokumentasi" name="dokumentasi[]" multiple>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pb-5">
                <button type="button" onclick="window.location.reload()" class="btn btn-light border px-4 py-2">Reset Form</button>
                <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">Simpan Laporan</button>
            </div>

        </form>
    </div>
</main>
@endsection

@push('styles')
    @vite(['resources/css/filepond.css', 'resources/js/filepond.js'])
    <style>
        .border-dashed { border: 1px dashed #ced4da !important; }
        .filepond--panel-root { background-color: #f8f9fa; border: 1px solid #ced4da; }
    </style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        if (window.FilePondManager) {
            window.FilePondManager.create('#fp-dokumentasi', {
                uploadRoute: '{{ route('upload.temp') }}',
                revertRoute: '{{ route('revert.temp') }}',
                loadRoute:   '{{ route('load.temp') }}',
                csrfToken:   '{{ csrf_token() }}',
                maxSize: '10MB',
                existingFiles: @json(old('dokumentasi', [])),
            });
            window.FilePondManager.attachFormSubmit('form-rehab', 'btn-submit');
        }
    });
</script>
@endpush