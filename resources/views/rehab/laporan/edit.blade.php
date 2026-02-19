@extends('admin')

@section('content')
<main class="admin-main" x-data="rehabEdit">
    <div class="container-fluid p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Edit Laporan Harian</h4>
                <p class="text-secondary small mb-0">Update data realisasi dan dokumentasi</p>
            </div>
            <a href="{{ route('rehab.laporan.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <form action="{{ route('rehab.laporan.update', $laporan->id) }}" method="POST" enctype="multipart/form-data" id="form-rehab">
            @csrf
            @method('PUT')

            <div class="row g-4">
                {{-- INFO READONLY --}}
                <div class="col-12">
                    <div class="alert alert-light border shadow-sm d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-primary me-2">{{ $laporan->satuanKerja->satuan_kerja ?? 'Satker Tidak Dikenal' }}</span>
                            <span class="fw-bold text-dark"><i class="bi bi-calendar-event me-1"></i> {{ \Carbon\Carbon::parse($laporan->tanggal)->translatedFormat('l, d F Y') }}</span>
                        </div>
                        <div class="text-muted small">ID Laporan: #{{ $laporan->id }}</div>
                    </div>
                </div>

                {{-- DATA REALISASI --}}
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h6 class="fw-bold m-0 text-success"><i class="bi bi-graph-up-arrow me-2"></i>Update Realisasi</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="p-3 border rounded bg-info-subtle bg-opacity-10 text-center">
                                        <label class="form-label fw-bold text-info-emphasis mb-2">Rawat Jalan</label>
                                        <input type="number" name="realisasi_rawat_jalan" 
                                               class="form-control form-control-lg text-center fw-bold text-info-emphasis" 
                                               value="{{ old('realisasi_rawat_jalan', $laporan->realisasi_rawat_jalan) }}" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded bg-success-subtle bg-opacity-10 text-center">
                                        <label class="form-label fw-bold text-success mb-2">Pasca Rehab</label>
                                        <input type="number" name="realisasi_pasca_rehab" 
                                               class="form-control form-control-lg text-center fw-bold text-success" 
                                               value="{{ old('realisasi_pasca_rehab', $laporan->realisasi_pasca_rehab) }}" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded bg-warning-subtle bg-opacity-10 text-center">
                                        <label class="form-label fw-bold text-warning-emphasis mb-2">SKHPN</label>
                                        <input type="number" name="realisasi_skhpn" 
                                               class="form-control form-control-lg text-center fw-bold text-warning-emphasis" 
                                               value="{{ old('realisasi_skhpn', $laporan->realisasi_skhpn) }}" min="0" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DOKUMENTASI --}}
                <div class="col-12">
                    <div class="card shadow-sm border-0 mb-5">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="fw-bold m-0 text-secondary"><i class="bi bi-paperclip me-2"></i>Dokumentasi</h6>
                        </div>
                        <div class="card-body p-4">
                            
                            {{-- File Lama --}}
                            @if($laporan->dokumentasi->count() > 0)
                                <h6 class="fw-bold text-dark mb-3 small text-uppercase">File Tersimpan</h6>
                                <div class="row g-3 mb-4">
                                    @foreach($laporan->dokumentasi as $doc)
                                        <div class="col-6 col-md-3 col-lg-2" id="file-card-{{ $doc->id }}">
                                            <div class="card h-100 shadow-sm border position-relative overflow-hidden file-card-inner">
                                                
                                                {{-- Overlay Hapus --}}
                                                <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 d-none flex-column justify-content-center align-items-center text-center" style="background-color: rgba(255, 255, 255, 0.9); z-index: 5;">
                                                    <div class="text-danger mb-1"><i class="bi bi-trash3-fill fs-1"></i></div>
                                                    <span class="text-danger fw-bold small text-uppercase">Akan Dihapus</span>
                                                </div>

                                                <div class="ratio ratio-4x3 bg-light border-bottom d-flex align-items-center justify-content-center">
                                                    @if(Str::contains($doc->tipe_file, 'image'))
                                                        <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100">
                                                    @else
                                                        <i class="bi bi-file-earmark-text display-4 text-secondary"></i>
                                                    @endif
                                                </div>
                                                <div class="card-body p-2 text-center">
                                                    <div class="small text-truncate fw-bold mb-2" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</div>
                                                    <div class="d-flex gap-1 justify-content-center position-relative" style="z-index: 10;">
                                                        <a href="{{ asset('storage/'.$doc->path_file) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100 py-0"><i class="bi bi-eye"></i></a>
                                                        <button type="button" id="btn-delete-{{ $doc->id }}" class="btn btn-outline-danger btn-sm w-100 py-0" onclick="markForDeletion({{ $doc->id }})">Hapus</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                {{-- Container input hidden untuk delete --}}
                                <div id="delete-inputs-container"></div>
                            @endif

                            {{-- File Baru --}}
                            <div class="bg-body-tertiary p-4 rounded-3 border border-dashed">
                                <label class="form-label fw-bold small text-dark mb-2">Upload File Baru</label>
                                <input type="file" id="fp-dokumentasi" name="dokumentasi[]" multiple>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pb-5">
                <button type="button" onclick="window.location.reload()" class="btn btn-light border px-4 py-2">Reset</button>
                <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">Simpan Perubahan</button>
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
        .border-danger-thick { border-color: #dc3545 !important; border-width: 2px !important; }
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
                existingFiles: [], // Edit mode file baru starts empty
            });
            window.FilePondManager.attachFormSubmit('form-rehab', 'btn-submit');
        }
    });

    // Script Manual untuk Mark Delete (Tanpa Alpine complex state, direct DOM manipulation agar ringan)
    window.markForDeletion = function(id) {
        const cardInner = document.querySelector('#file-card-' + id + ' .file-card-inner');
        const overlay = cardInner.querySelector('.delete-overlay');
        const btnDelete = document.getElementById('btn-delete-' + id);
        const containerInputs = document.getElementById('delete-inputs-container');
        let input = document.getElementById('input-delete-' + id);
        
        if (input) { 
            // Undo Delete
            input.remove();
            overlay.classList.add('d-none');
            overlay.classList.remove('d-flex');
            cardInner.classList.remove('border-danger-thick');
            btnDelete.classList.remove('btn-secondary');
            btnDelete.classList.add('btn-outline-danger');
            btnDelete.innerHTML = 'Hapus';
        } else { 
            // Mark Delete
            input = document.createElement('input');
            input.type = 'hidden'; input.name = 'delete_files[]'; input.value = id; input.id = 'input-delete-' + id;
            containerInputs.appendChild(input);
            
            overlay.classList.remove('d-none');
            overlay.classList.add('d-flex');
            cardInner.classList.add('border-danger-thick');
            btnDelete.classList.remove('btn-outline-danger');
            btnDelete.classList.add('btn-secondary');
            btnDelete.innerHTML = 'Batal';
        }
    };
</script>
@endpush