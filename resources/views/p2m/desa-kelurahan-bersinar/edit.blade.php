@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            <div class="row justify-content-center mb-4">
                <div class="col-12 col-lg-10">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-1 fw-bold text-dark">Edit Desa / Kelurahan Bersinar</h1>
                            <p class="text-muted mb-0">Perbarui Data Pencanangan</p>
                        </div>
                        <a href="{{ route('p2m.desa-kelurahan-bersinar.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
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
                            <form action="{{ route('p2m.desa-kelurahan-bersinar.update', $desa->id) }}" method="POST" enctype="multipart/form-data" id="form-edit">
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
                                    <div class="col-12">
                                        <label class="form-label fw-semibold text-secondary small">Nama Desa / Kelurahan<span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('nama_desa_kelurahan') is-invalid @enderror" name="nama_desa_kelurahan" value="{{ old('nama_desa_kelurahan', $desa->nama_desa_kelurahan) }}" placeholder="Masukkan nama desa">
                                        @error('nama_desa_kelurahan') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">Personil & Kelengkapan</h6>
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

                                    {{-- AREA PENGELOLAAN FILE --}}
                                    <div class="col-12 mt-5">
                                        
                                        {{-- 1. KOTAK KHUSUS DOKUMENTASI LAMA --}}
                                        @php
                                            $oldFotos = $desa->dokumen->where('kategori', 'dokumentasi');
                                        @endphp
                                        
                                        @if($oldFotos->count() > 0)
                                            <div class="card bg-light border border-dashed mb-4">
                                                <div class="card-body">
                                                    <h6 class="fw-bold text-primary mb-3">
                                                        <i class="bi bi-images me-2"></i>Dokumentasi Tersimpan
                                                    </h6>
                                                    
                                                    <div class="row g-3">
                                                        @foreach($oldFotos as $doc)
                                                            {{-- INLINE CARD COMPONENT --}}
                                                            @php $isMarkedDeleted = old('delete_files') && in_array($doc->id, old('delete_files')); @endphp
                                                            <div class="col-6 col-md-4 col-lg-3 file-item" id="file-card-{{ $doc->id }}">
                                                                <div class="card h-100 shadow-sm border border-secondary-subtle position-relative overflow-hidden file-card-inner transition-all {{ $isMarkedDeleted ? 'border-danger-subtle-thick' : '' }}">
                                                                    
                                                                    <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" style="background-color: rgba(255, 255, 255, 0.9); z-index: 5;">
                                                                        <div class="text-danger mb-1"><i class="bi bi-trash3-fill fs-1"></i></div>
                                                                        <span class="text-danger fw-bold small text-uppercase">Akan Dihapus</span>
                                                                    </div>

                                                                    {{-- PREVIEW AREA --}}
                                                                    <div class="ratio ratio-16x9 bg-secondary bg-opacity-10 border-bottom d-flex align-items-center justify-content-center overflow-hidden">
                                                                        @if($doc->is_link)
                                                                            <div class="text-info"><i class="bi bi-link-45deg display-4"></i></div>
                                                                        @elseif(Str::contains($doc->tipe_file, 'image'))
                                                                            <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100">
                                                                        @else
                                                                            <div class="text-primary"><i class="bi bi-file-earmark-text-fill display-4"></i></div>
                                                                        @endif
                                                                    </div>

                                                                    <div class="card-body p-2 text-center d-flex flex-column justify-content-between">
                                                                        <div class="mb-2">
                                                                            <div class="small text-truncate fw-bold" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</div>
                                                                            @if($doc->is_link)
                                                                                <div class="text-muted small fst-italic text-truncate"><a href="{{ $doc->path_url }}" target="_blank">{{ $doc->path_url }}</a></div>
                                                                            @endif
                                                                        </div>
                                                                        
                                                                        <div class="d-flex gap-1 justify-content-center position-relative" style="z-index: 10;">
                                                                            @if(!$doc->is_link)
                                                                                <a href="{{ route('dokumen.download', $doc->id) }}" class="btn btn-outline-primary btn-sm w-100 py-0" title="Unduh"><i class="bi bi-download"></i></a>
                                                                            @else
                                                                                <a href="{{ $doc->path_url }}" target="_blank" class="btn btn-outline-info btn-sm w-100 py-0" title="Buka"><i class="bi bi-box-arrow-up-right"></i></a>
                                                                            @endif
                                                                            <button type="button" id="btn-delete-{{ $doc->id }}" class="btn btn-sm w-100 py-0 {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" onclick="markForDeletion({{ $doc->id }})">@if($isMarkedDeleted) Batal @else Hapus @endif</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endif


                                        {{-- 2. KOTAK KHUSUS LAMPIRAN LAMA --}}
                                        @php
                                            $oldLampirans = $desa->dokumen->where('kategori', 'lampiran');
                                        @endphp

                                        @if($oldLampirans->count() > 0)
                                            <div class="card bg-light border border-dashed mb-4">
                                                <div class="card-body">
                                                    <h6 class="fw-bold text-danger mb-3">
                                                        <i class="bi bi-paperclip me-2"></i>Lampiran Tersimpan
                                                    </h6>

                                                    <div class="row g-3">
                                                        @foreach($oldLampirans as $doc)
                                                            {{-- INLINE CARD COMPONENT --}}
                                                            @php $isMarkedDeleted = old('delete_files') && in_array($doc->id, old('delete_files')); @endphp
                                                            <div class="col-6 col-md-4 col-lg-3 file-item" id="file-card-{{ $doc->id }}">
                                                                <div class="card h-100 shadow-sm border border-secondary-subtle position-relative overflow-hidden file-card-inner transition-all {{ $isMarkedDeleted ? 'border-danger-subtle-thick' : '' }}">
                                                                    
                                                                    <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" style="background-color: rgba(255, 255, 255, 0.9); z-index: 5;">
                                                                        <div class="text-danger mb-1"><i class="bi bi-trash3-fill fs-1"></i></div>
                                                                        <span class="text-danger fw-bold small text-uppercase">Akan Dihapus</span>
                                                                    </div>

                                                                    {{-- PREVIEW AREA --}}
                                                                    <div class="ratio ratio-16x9 bg-secondary bg-opacity-10 border-bottom d-flex align-items-center justify-content-center overflow-hidden">
                                                                        @if($doc->is_link)
                                                                            <div class="text-info"><i class="bi bi-link-45deg display-4"></i></div>
                                                                        @elseif(Str::contains($doc->tipe_file, 'image'))
                                                                            <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100">
                                                                        @elseif(Str::contains($doc->tipe_file, 'pdf'))
                                                                            <div class="text-danger"><i class="bi bi-file-earmark-pdf-fill display-4"></i></div>
                                                                        @else
                                                                            <div class="text-secondary"><i class="bi bi-file-earmark-text-fill display-4"></i></div>
                                                                        @endif
                                                                    </div>

                                                                    <div class="card-body p-2 text-center d-flex flex-column justify-content-between">
                                                                        <div class="mb-2">
                                                                            <div class="small text-truncate fw-bold" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</div>
                                                                            @if($doc->is_link)
                                                                                <div class="text-muted small fst-italic text-truncate"><a href="{{ $doc->path_url }}" target="_blank">{{ $doc->path_url }}</a></div>
                                                                            @endif
                                                                        </div>
                                                                        
                                                                        <div class="d-flex gap-1 justify-content-center position-relative" style="z-index: 10;">
                                                                            @if(!$doc->is_link)
                                                                                <a href="{{ route('dokumen.download', $doc->id) }}" class="btn btn-outline-primary btn-sm w-100 py-0" title="Unduh"><i class="bi bi-download"></i></a>
                                                                            @else
                                                                                <a href="{{ $doc->path_url }}" target="_blank" class="btn btn-outline-info btn-sm w-100 py-0" title="Buka"><i class="bi bi-box-arrow-up-right"></i></a>
                                                                            @endif
                                                                            <button type="button" id="btn-delete-{{ $doc->id }}" class="btn btn-sm w-100 py-0 {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" onclick="markForDeletion({{ $doc->id }})">@if($isMarkedDeleted) Batal @else Hapus @endif</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- HIDDEN INPUTS UNTUK LOGIC HAPUS --}}
                                        <div id="delete-inputs-container">
                                            @if(old('delete_files'))
                                                @foreach(old('delete_files') as $deletedId)
                                                    <input type="hidden" name="delete_files[]" value="{{ $deletedId }}" id="input-delete-{{ $deletedId }}">
                                                @endforeach
                                            @endif
                                        </div>


                                        {{-- ========================================== --}}
                                        {{-- BAGIAN 3: UPLOAD FILE & LINK BARU (HYBRID) --}}
                                        {{-- ========================================== --}}
                                        <div class="bg-light p-4 rounded-3 border border-dashed">
                                            <label class="form-label fw-bold h6 mb-3 text-dark d-block border-bottom pb-2">
                                                <i class="bi bi-cloud-arrow-up me-2"></i>Upload File & Link Baru (Opsional)
                                            </label>
                                            
                                            <div class="row g-3">
                                                {{-- KIRI: DOKUMENTASI BARU --}}
                                                <div class="col-12 col-md-6">
                                                    <div class="bg-white p-3 rounded border h-100 d-flex flex-column shadow-sm">
                                                        <label class="form-label fw-bold small text-primary mb-1">
                                                            <i class="bi bi-folder2-open me-2"></i>Dokumentasi Baru
                                                        </label>
                                                        
                                                        {{-- 1. File Upload --}}
                                                        <div class="mb-3">
                                                            <p class="text-muted small mb-2" style="font-size: 0.75rem">Upload dokumentasi. Maksimal 10MB.</p>
                                                            <input type="file" id="fp-dokumentasi" name="dokumentasi[]" multiple>
                                                        </div>

                                                        <hr class="border-secondary-subtle my-3">

                                                        {{-- 2. Link Input (Alpine) --}}
                                                        <div x-data="linkManager( {{ \Illuminate\Support\Js::from(array_values(old('dokumentasi_links', []))) }} )">
                                                            <label class="form-label fw-bold small text-primary mb-2">
                                                                <i class="bi bi-link-45deg me-1"></i>Atau Tautkan Link
                                                            </label>
                                                            
                                                            <template x-for="(link, index) in links" :key="index">
                                                                <div class="input-group mb-2 input-group-sm">
                                                                    <input type="text" class="form-control" :name="`dokumentasi_links[${index}][nama]`" placeholder="Nama Tautan / File" x-model="link.nama" required>
                                                                    <input type="url" class="form-control" :name="`dokumentasi_links[${index}][url]`" placeholder="https://" x-model="link.url" required>
                                                                    <button type="button" class="btn btn-outline-danger" @click="removeLink(index)"><i class="bi bi-x"></i></button>
                                                                </div>
                                                            </template>

                                                            @error('dokumentasi_links.*') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                                                            <button type="button" class="btn btn-xs btn-outline-primary dashed-border w-100 mt-1" @click="addLink()">
                                                                <i class="bi bi-plus-circle me-1"></i> Tambah Link
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- KANAN: LAMPIRAN BARU --}}
                                                <div class="col-12 col-md-6">
                                                    <div class="bg-white p-3 rounded border h-100 d-flex flex-column shadow-sm">
                                                        <label class="form-label fw-bold small text-danger mb-1">
                                                            <i class="bi bi-paperclip me-2"></i>Lampiran Pendukung Baru
                                                        </label>
                                                        
                                                        {{-- 1. File Upload --}}
                                                        <div class="mb-3">
                                                            <p class="text-muted small mb-2" style="font-size: 0.75rem">Upload file pendukung. Maksimal 10MB.</p>
                                                            <input type="file" id="fp-lampiran" name="lampiran[]" multiple>
                                                        </div>

                                                        <hr class="border-secondary-subtle my-3">

                                                        {{-- 2. Link Input (Alpine) --}}
                                                        <div x-data="linkManager( {{ \Illuminate\Support\Js::from(array_values(old('lampiran_links', []))) }} )">
                                                            <label class="form-label fw-bold small text-danger mb-2">
                                                                <i class="bi bi-link-45deg me-1"></i>Atau Tautkan Link
                                                            </label>
                                                            
                                                            <template x-for="(link, index) in links" :key="index">
                                                                <div class="input-group mb-2 input-group-sm">
                                                                    <input type="text" class="form-control" :name="`lampiran_links[${index}][nama]`" placeholder="Nama Tautan / File" x-model="link.nama" required>
                                                                    <input type="url" class="form-control" :name="`lampiran_links[${index}][url]`" placeholder="https://" x-model="link.url" required>
                                                                    <button type="button" class="btn btn-outline-danger" @click="removeLink(index)"><i class="bi bi-x"></i></button>
                                                                </div>
                                                            </template>

                                                            @error('lampiran_links.*') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                                                            <button type="button" class="btn btn-xs btn-outline-danger dashed-border w-100 mt-1" @click="addLink()">
                                                                <i class="bi bi-plus-circle me-1"></i> Tambah Link
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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

        // 2. FILEPOND MANAGER
        const commonConfig = {
            uploadRoute: '{{ route('upload.temp') }}',
            revertRoute: '{{ route('revert.temp') }}',
            loadRoute:   '{{ route('load.temp') }}',
            csrfToken:   '{{ csrf_token() }}',
            submitBtnId: 'btn-submit'
        };

        if (window.FilePondManager) {
            
            // A. Init Dokumentasi Baru
            window.FilePondManager.create('#fp-dokumentasi', {
                ...commonConfig,
                maxSize: '10MB',
                // Existing Files disini hanya untuk file BARU yang gagal validasi saat submit, bukan file lama dari DB
                existingFiles: @json(old('dokumentasi', [])), 
            });

            // B. Init Lampiran Baru
            window.FilePondManager.create('#fp-lampiran', {
                ...commonConfig,
                maxSize: '10MB',
                existingFiles: @json(old('lampiran', [])), 
            });

            // C. Validasi Submit
            window.FilePondManager.attachFormSubmit('form-edit', 'btn-submit');

        } else {
            console.error("FilePondManager belum dimuat. Pastikan 'npm run build' atau 'npm run dev' berjalan.");
        }

    });

    // 3. ALPINE JS LINK MANAGER
    document.addEventListener('alpine:init', () => {
        Alpine.data('linkManager', (initialData = []) => ({
            links: Array.isArray(initialData) ? initialData : [], 
            addLink() {
                this.links.push({ nama: '', url: '' });
            },
            removeLink(index) {
                this.links.splice(index, 1);
            }
        }));
    });

    // 4. FUNGSI HAPUS FILE LAMA (MARK FOR DELETION)
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