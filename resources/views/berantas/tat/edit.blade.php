@extends('admin')

@section('content')
<main class="admin-main" x-data>
    <div class="container-fluid p-4 p-lg-5">
        
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1 fw-bold text-dark">Edit Data TAT</h1>
                        <p class="text-muted mb-0">Perbarui Data Tim Asesmen Terpadu</p>
                    </div>
                    <a href="{{ route('berantas.tat.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">Form Edit Data</h5>
                    </div>

                    <div class="card-body p-4 p-lg-5">
                        <form action="{{ route('berantas.tat.update', $tat->id) }}" method="POST" enctype="multipart/form-data" id="form-edit">
                            @csrf
                            @method('PUT')

                            @if(Auth::user()->isAdmin())
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-secondary">Satuan Kerja <span class="text-danger">*</span></label>
                                    <select name="satuan_kerja_id" class="form-select @error('satuan_kerja_id') is-invalid @enderror">
                                        <option value="" disabled>-- Pilih Satuan Kerja --</option>
                                        @foreach(\App\Models\SatuanKerja::orderBy('satuan_kerja')->get() as $satker)
                                            <option value="{{ $satker->id }}" @selected(old('satuan_kerja_id', $tat->satuan_kerja_id) == $satker->id)>{{ $satker->satuan_kerja }}</option>
                                        @endforeach
                                    </select>
                                    @error('satuan_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endif

                            {{-- 1. DATA UMUM & TERSANGKA --}}
                            <h6 class="text-uppercase text-secondary fw-bold small mb-4 border-bottom pb-2">Data Umum & Tersangka</h6>
                            
                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">No. Register TAT <span class="text-danger">*</span></label>
                                    <input type="text" name="no_register" class="form-control @error('no_register') is-invalid @enderror" value="{{ old('no_register', $tat->no_register) }}" placeholder="Masukkan nomor register">
                                    @error('no_register') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_pelaksanaan" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" value="{{ old('tanggal_pelaksanaan', $tat->tanggal_pelaksanaan->format('Y-m-d')) }}">
                                    @error('tanggal_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nama Tersangka <span class="text-danger">*</span></label>
                                    <textarea name="nama_tersangka" 
                                              class="form-control @error('nama_tersangka') is-invalid @enderror" 
                                              rows="3" 
                                              x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' } }"
                                              x-init="resize()"
                                              @input="resize()"
                                              placeholder="Masukkan nama lengkap tersangka">{{ old('nama_tersangka', $tat->nama_tersangka) }}</textarea>
                                    @error('nama_tersangka') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label small fw-bold">NIK</label>
                                            <input type="text" name="nik" class="form-control @error('nik') is-invalid @enderror" value="{{ old('nik', $tat->nik) }}" placeholder="Masukkan NIK">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-bold">Jenis Kelamin <span class="text-danger">*</span></label>
                                            <select name="jenis_kelamin" class="form-select @error('jenis_kelamin') is-invalid @enderror">
                                                <option value="Laki-laki" @selected(old('jenis_kelamin', $tat->jenis_kelamin) == 'Laki-laki')>Laki-laki</option>
                                                <option value="Perempuan" @selected(old('jenis_kelamin', $tat->jenis_kelamin) == 'Perempuan')>Perempuan</option>
                                            </select>
                                            @error('jenis_kelamin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">Usia <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number" name="usia" class="form-control @error('usia') is-invalid @enderror" value="{{ old('usia', $tat->usia) }}" placeholder="0">
                                                <span class="input-group-text bg-light text-secondary small">Thn</span>
                                                @error('usia') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-bold">Pendidikan <span class="text-danger">*</span></label>
                                            <input type="text" name="pendidikan" class="form-control @error('pendidikan') is-invalid @enderror" value="{{ old('pendidikan', $tat->pendidikan) }}" placeholder="Masukkan pendidikan">
                                            @error('pendidikan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">No Telepon</label>
                                    <input type="text" name="no_telepon" class="form-control" value="{{ old('no_telepon', $tat->no_telepon) }}" placeholder="Masukkan nomor telepon">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold">Pekerjaan</label>
                                    <input type="text" name="pekerjaan" class="form-control" value="{{ old('pekerjaan', $tat->pekerjaan) }}" placeholder="Masukkan pekerjaan tersangka">
                                </div>
                            </div>

                            {{-- 2. DATA KASUS --}}
                            <h6 class="text-uppercase text-secondary fw-bold small mb-4 border-bottom pb-2">Data Kasus</h6>
                            
                            <div class="row g-4 mb-5">
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Pasal Disangkakan <span class="text-danger">*</span></label>
                                    <textarea name="pasal_disangkakan" 
                                              class="form-control @error('pasal_disangkakan') is-invalid @enderror" 
                                              rows="3" 
                                              x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' } }"
                                              x-init="resize()"
                                              @input="resize()"
                                              placeholder="Masukkan pasal yang disangkakan">{{ old('pasal_disangkakan', $tat->pasal_disangkakan) }}</textarea>
                                    @error('pasal_disangkakan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Instansi Pengirim <span class="text-danger">*</span></label>
                                    <input type="text" name="instansi_pengirim" class="form-control @error('instansi_pengirim') is-invalid @enderror" value="{{ old('instansi_pengirim', $tat->instansi_pengirim) }}" placeholder="Masukkan nama instansi pengirim">
                                    @error('instansi_pengirim') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Tanggal Permohonan <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_permohonan" class="form-control @error('tanggal_permohonan') is-invalid @enderror" value="{{ old('tanggal_permohonan', $tat->tanggal_permohonan?->format('Y-m-d')) }}">
                                    @error('tanggal_permohonan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Jenis Narkoba <span class="text-danger">*</span></label>
                                    <input type="text" name="jenis_narkoba" class="form-control @error('jenis_narkoba') is-invalid @enderror" value="{{ old('jenis_narkoba', $tat->jenis_narkoba) }}" placeholder="Masukkan jenis narkoba">
                                    @error('jenis_narkoba') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Jumlah Satuan (Gram)</label>
                                    <input type="text" name="jumlah_barang_bukti" class="form-control" value="{{ old('jumlah_barang_bukti', $tat->jumlah_barang_bukti) }}" placeholder="Masukkan jumlah satuan">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold">Tanggal Penangkapan <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_penangkapan" class="form-control @error('tanggal_penangkapan') is-invalid @enderror" value="{{ old('tanggal_penangkapan', $tat->tanggal_penangkapan?->format('Y-m-d')) }}">
                                    @error('tanggal_penangkapan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            {{-- 3. HASIL ASESMEN --}}
                            <h6 class="text-uppercase text-secondary fw-bold small mb-4 border-bottom pb-2">Hasil Asesmen & Rekomendasi</h6>
                            
                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Tim Hukum <span class="text-danger">*</span></label>
                                    <textarea name="tim_hukum" class="form-control @error('tim_hukum') is-invalid @enderror" rows="3" 
                                        x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' } }"
                                        x-init="resize()" @input="resize()" placeholder="Masukkan nama tim hukum">{{ old('tim_hukum', $tat->tim_hukum) }}</textarea>
                                    @error('tim_hukum') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Tim Medis <span class="text-danger">*</span></label>
                                    <textarea name="tim_medis" class="form-control @error('tim_medis') is-invalid @enderror" rows="3"
                                        x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' } }"
                                        x-init="resize()" @input="resize()" placeholder="Masukkan nama tim medis">{{ old('tim_medis', $tat->tim_medis) }}</textarea>
                                    @error('tim_medis') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Lembaga Rehab</label>
                                    <input type="text" name="lembaga_rehab" class="form-control" value="{{ old('lembaga_rehab', $tat->lembaga_rehab) }}" placeholder="Masukkan lembaga rehab">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Tindak Lanjut Rekomendasi</label>
                                    <select name="tindak_lanjut_rekomendasi" class="form-select">
                                        <option value="">-- Pilih --</option>
                                        <option value="dilaksanakan" @selected(old('tindak_lanjut_rekomendasi', $tat->tindak_lanjut_rekomendasi) == 'dilaksanakan')>Dilaksanakan</option>
                                        <option value="tidak dilaksanakan" @selected(old('tindak_lanjut_rekomendasi', $tat->tindak_lanjut_rekomendasi) == 'tidak dilaksanakan')>Tidak Dilaksanakan</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Biaya yang Dikeluarkan</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-secondary">Rp</span>
                                        <input type="number" name="biaya" class="form-control" value="{{ old('biaya', $tat->biaya) }}" placeholder="0">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold">Proses Hukum Lanjut</label>
                                    <textarea name="proses_hukum_lanjut" 
                                              class="form-control" 
                                              rows="3"
                                              x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' } }"
                                              x-init="resize()" @input="resize()"
                                              placeholder="Masukkan proses hukum lanjut">{{ old('proses_hukum_lanjut', $tat->proses_hukum_lanjut) }}</textarea>
                                </div>
                            </div>

                            {{-- 5. LAMPIRAN --}}
                            <h6 class="text-uppercase text-secondary fw-bold small mb-4 border-bottom pb-2">Lampiran</h6>
                            
                            <div class="bg-body-tertiary p-4 rounded-3 border border-dashed mb-4">
                                {{-- A. File Tersimpan --}}
                                @if($tat->dokumentasi->count() > 0)
                                    <p class="small fw-bold text-secondary mb-2">File Tersimpan:</p>
                                    <div class="row g-3 mb-4" id="existing-files-container">
                                        @foreach($tat->dokumentasi as $doc)
                                            @php
                                                $isMarkedDeleted = old('delete_files') && in_array($doc->id, old('delete_files'));
                                            @endphp
                                            <div class="col-6 col-md-4 col-lg-3 file-item" id="file-card-{{ $doc->id }}">
                                                <div class="card h-100 shadow-sm border border-secondary-subtle position-relative overflow-hidden file-card-inner transition-all {{ $isMarkedDeleted ? 'border-danger-subtle-thick' : '' }}">
                                                    <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" style="background-color: rgba(255, 255, 255, 0.9); z-index: 5;">
                                                        <div class="text-danger mb-1"><i class="bi bi-trash3-fill fs-1"></i></div>
                                                        <span class="text-danger fw-bold small text-uppercase">Akan Dihapus</span>
                                                    </div>
                                                    <div class="ratio ratio-16x9 bg-secondary bg-opacity-10 border-bottom d-flex align-items-center justify-content-center overflow-hidden">
                                                        @if(Str::contains($doc->tipe_file, 'image'))
                                                            <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100">
                                                        @else
                                                            <div class="text-secondary"><i class="bi bi-file-earmark-text-fill display-4"></i></div>
                                                        @endif
                                                    </div>
                                                    <div class="card-body p-2 text-center d-flex flex-column justify-content-between">
                                                        <div class="small text-truncate fw-bold">{{ $doc->nama_file_asli }}</div>
                                                        <button type="button" id="btn-delete-{{ $doc->id }}" class="btn btn-sm w-100 py-0 mt-2 {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" onclick="markForDeletion({{ $doc->id }})">
                                                            @if($isMarkedDeleted) Batal @else Hapus @endif
                                                        </button>
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

                                <label class="form-label fw-bold h6 mb-1 text-dark">
                                    <i class="bi bi-cloud-arrow-up me-2"></i>Upload Dokumen TAT
                                </label>
                                <p class="text-muted small mb-3">Laporan Hasil Asesmen, Foto Kegiatan, dll (Max 10MB)</p>
                                
                                <input type="file" 
                                       class="filepond" 
                                       name="dokumentasi[]" 
                                       multiple 
                                       data-allow-reorder="true" 
                                       data-max-file-size="10MB"
                                       data-max-files="10">

                                @error('dokumentasi')
                                    <div class="alert alert-danger py-2 mt-2 small border-0 shadow-sm">
                                        <i class="bi bi-exclamation-circle me-1"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- FOOTER BUTTONS --}}
                            <div class="d-flex flex-column-reverse flex-lg-row justify-content-end gap-2 pt-4 border-top mt-5">
                                <button type="button" onclick="window.location.reload()" class="btn btn-light border text-secondary px-4">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                </button>
                                <button type="submit" id="btn-submit" class="btn btn-primary px-5 shadow-sm">
                                    Simpan Data
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
    @vite(['resources/css/filepond.css', 'resources/js/filepond.js'])
    <style>
        .filepond--panel-root { background-color: #fff; border: 1px solid #dee2e6; }
        .border-dashed { border-style: dashed !important; border-width: 2px !important; }
        .border-danger-subtle-thick { border-color: #dc3545 !important; border-width: 2px !important; }
        .transition-all { transition: all 0.3s ease; }
    </style>
@endpush

@push('scripts')
<script>
    // Logic Hapus File Lama (Vanilla JS)
    window.markForDeletion = function(id) {
        const cardInner = document.querySelector('#file-card-' + id + ' .file-card-inner');
        const overlay = cardInner.querySelector('.delete-overlay');
        const btnDelete = document.getElementById('btn-delete-' + id);
        const containerInputs = document.getElementById('delete-inputs-container');
        
        if (!overlay.classList.contains('d-none')) {
            overlay.classList.add('d-none'); overlay.classList.remove('d-flex');
            cardInner.classList.remove('border-danger-subtle-thick');
            btnDelete.classList.remove('btn-secondary'); btnDelete.classList.add('btn-outline-danger');
            btnDelete.innerHTML = 'Hapus';
            const input = document.getElementById('input-delete-' + id);
            if(input) input.remove();
        } else {
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

<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const inputElement = document.querySelector('input.filepond');
        const submitBtn = document.getElementById('btn-submit');
        const form = document.getElementById('form-edit');
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
                process: { url: '{{ route('upload.temp') }}', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, onload: (response) => { return response; }, onerror: (response) => { setButtonState(false); return response; } },
                revert: { url: '{{ route('revert.temp') }}', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } },
                load: { url: '{{ route('load.temp') }}/?file=', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } },
            },
            onprocessstart: () => { setButtonState(true, 'Mengupload...'); },
            onprocessfiles: () => { setButtonState(false); },
            onwarning: () => { setButtonState(false); },
            onerror: () => { setButtonState(false); },
            onremovefile: () => {
                const files = pond.getFiles();
                if (!files.some(f => f.status === 3 || f.status === 9)) { setButtonState(false); }
            }
        });

        form.addEventListener('submit', function(e) {
            const files = pond.getFiles();
            if (files.some(file => file.status !== 2 && file.status !== 5)) {
                e.preventDefault();
                alert('Upload Belum Selesai. Silakan tunggu atau hapus file yang macet.');
            } else {
                setButtonState(true, 'Menyimpan...');
            }
        });
    });
</script>
@endpush