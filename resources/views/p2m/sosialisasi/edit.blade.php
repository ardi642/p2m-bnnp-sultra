@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0">Kegiatan P2M</h1>
                            <p class="text-muted mb-0">Edit Data Kegiatan</p>
                        </div>
                        <a href="{{ route('p2m.sosialisasi.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>

                    <div class="card shadow-lg p-5">
                        <div class="card-header">
                            <h5 class="card-title mb-2">Edit Data Sosialisasi Tatap Muka/Konvensional</h5>
                        </div>
                        <div class="card-body">
                            <form id="form-edit-p2m" action="{{ route('p2m.sosialisasi.update', $kegiatan->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                
                                <div class="row g-6 mb-5">
                                    
                                    {{-- Input Satuan Kerja, Anggaran, Nama, Sasaran, Tanggal, Tempat, Peserta --}}
                                    {{-- ... (Kode input text sama persis dengan yang Anda kirim sebelumnya) ... --}}
                                    {{-- Saya skip bagian ini agar tidak terlalu panjang, copy paste saja bagian input text dari kode Anda --}}
                                    
                                    @if (Auth::user()->isAdmin())     
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="satuan_kerja_id" class="form-label">Satuan Kerja</label>
                                            <select class="form-select @error('satuan_kerja_id') is-invalid @enderror" name="satuan_kerja_id">
                                                <option value="">pilih satuan kerja</option>
                                                @foreach ($satuanKerjas as $satuanKerja)
                                                    <option value="{{ $satuanKerja->id }}" @selected(old('satuan_kerja_id', $kegiatan->satuan_kerja_id) == $satuanKerja->id)>
                                                        {{ $satuanKerja->satuan_kerja }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('satuan_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    @endif

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Anggaran</label>
                                            <select class="form-select" name="anggaran_pelaksanaan">
                                                <option value="DIPA" @selected(old('anggaran_pelaksanaan', $kegiatan->anggaran_pelaksanaan) == 'DIPA')>DIPA</option>
                                                <option value="NON DIPA" @selected(old('anggaran_pelaksanaan', $kegiatan->anggaran_pelaksanaan) == 'NON DIPA')>NON DIPA</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Nama Kegiatan</label>
                                        <input type="text" class="form-control" name="nama_kegiatan" value="{{ old('nama_kegiatan', $kegiatan->nama_kegiatan) }}">
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Sasaran</label>
                                        <select class="form-select" name="sasaran_kegiatan">
                                            @php $sasaran = old('sasaran_kegiatan', $kegiatan->sasaran_kegiatan); @endphp
                                            <option value="lingkungan pendidikan" @selected($sasaran == 'lingkungan pendidikan')>Lingkungan Pendidikan</option>
                                            <option value="lingkungan kerja" @selected($sasaran == 'lingkungan kerja')>Lingkungan Kerja</option>
                                            <option value="lingkungan masyarakat" @selected($sasaran == 'lingkungan masyarakat')>Lingkungan Masyarakat</option>
                                        </select>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Tanggal</label>
                                        <input type="date" class="form-control" name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan', $kegiatan->tanggal_pelaksanaan->format('Y-m-d')) }}">
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Tempat</label>
                                        <textarea class="form-control" name="tempat_kegiatan" rows="1">{{ old('tempat_kegiatan', $kegiatan->tempat_kegiatan) }}</textarea>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Pegawai</label>
                                        <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Pilih Pegawai..." autocomplete="off">
                                            @foreach ($pegawais as $pgw)
                                                <option value="{{ $pgw->nip }}" @selected(in_array($pgw->nip, old('pegawai_nips', $selectedPegawaiNips)))>
                                                    {{ $pgw->nama }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Jumlah Peserta</label>
                                        <input type="number" class="form-control" name="jumlah_peserta" value="{{ old('jumlah_peserta', $kegiatan->jumlah_peserta) }}">
                                    </div>

                                    {{-- === BAGIAN FILE === --}}
                                    <div class="col-12">
                                        <hr class="my-5 text-secondary">

                                        {{-- 1. FILE LAMA (Untuk Dihapus) --}}
                                        <div class="mb-5">
                                            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-collection me-2"></i>File Tersimpan</h5>
                                            
                                            @if($kegiatan->dokumentasi->count() > 0)
                                                <div class="row g-3">
                                                    @foreach($kegiatan->dokumentasi as $doc)
                                                        <div class="col-6 col-md-4 col-lg-3">
                                                            <div class="card h-100 border shadow-sm position-relative">
                                                                
                                                                {{-- Preview --}}
                                                                <div class="ratio ratio-16x9 bg-light border-bottom d-flex align-items-center justify-content-center">
                                                                    @if(Str::contains($doc->tipe_file, 'image'))
                                                                        <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100" alt="img">
                                                                    @elseif(Str::contains($doc->tipe_file, 'pdf'))
                                                                        <div class="text-danger"><i class="bi bi-file-earmark-pdf-fill display-5"></i></div>
                                                                    @else
                                                                        <div class="text-primary"><i class="bi bi-file-earmark-word-fill display-5"></i></div>
                                                                    @endif
                                                                </div>

                                                                <div class="card-body p-2">
                                                                    <div class="small fw-bold text-truncate mb-1">{{ $doc->nama_file_asli }}</div>
                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                        <a href="{{ Storage::url($doc->path_file) }}" target="_blank" class="btn btn-xs btn-outline-primary py-0" style="font-size: 0.7rem">Lihat</a>
                                                                        
                                                                        {{-- Checkbox Hapus --}}
                                                                        <div class="form-check form-switch">
                                                                            <input class="form-check-input bg-danger border-danger" type="checkbox" name="delete_files[]" value="{{ $doc->id }}" id="del_{{ $doc->id }}">
                                                                            <label class="form-check-label small text-danger fw-bold" for="del_{{ $doc->id }}" style="font-size: 0.75rem">Hapus</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <div class="alert alert-warning mt-3 py-2 small">
                                                    <i class="bi bi-exclamation-triangle me-1"></i> Centang "Hapus" pada file yang ingin dibuang, lalu klik Simpan Perubahan.
                                                </div>
                                            @else
                                                <p class="text-muted fst-italic">Tidak ada file dokumentasi sebelumnya.</p>
                                            @endif
                                        </div>

                                        {{-- 2. UPLOAD FILE BARU --}}
                                        <div class="mb-5">
                                            <h5 class="fw-bold text-success mb-3"><i class="bi bi-cloud-upload me-2"></i>Tambah File Baru</h5>
                                            <input type="file" 
                                                   class="filepond"
                                                   name="dokumentasi[]" 
                                                   multiple 
                                                   data-allow-reorder="true"
                                                   data-max-file-size="10MB">
                                            <div class="form-text">Biarkan kosong jika tidak ingin menambah file baru.</div>
                                            
                                            {{-- Error Message --}}
                                            @error('dokumentasi') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                            @error('dokumentasi.*') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                </div> 

                                <div class="row justify-content-end">
                                    <div class="col-12 col-lg-auto">
                                        <button type="submit" class="btn btn-success w-100 mb-4 mb-lg-0">Simpan Perubahan</button>
                                    </div>
                                    <div class="col-12 col-lg-auto">
                                        <a href="{{ route('p2m.sosialisasi.edit', $kegiatan->id) }}" class="btn btn-secondary w-100 mb-4 mb-lg-0">Reset Data</a>
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
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        // Setup TomSelect
        if(typeof TomSelect !== 'undefined'){
            const selectEl = document.getElementById('select-pegawai');
            if(selectEl) {
                new TomSelect(selectEl, {
                    create: false,
                    sortField: { field: "text", direction: "asc" },
                    maxItems: null,
                    placeholder: "Cari atau pilih pegawai...",
                    plugins: ['remove_button'],
                });
            }
        }

        // Setup FilePond
        const inputElement = document.querySelector('input.filepond');
        if (inputElement && typeof FilePond !== 'undefined') {
            FilePond.create(inputElement, {
                acceptedFileTypes: ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                labelIdle: 'Drag & Drop file tambahan atau <span class="filepond--label-action">Cari File</span>',
                credits: false,
                server: {
                    process: { url: '{{ route('upload.temp') }}', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } },
                    revert: { url: '{{ route('revert.temp') }}', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }
                }
            });
        }
    });
</script>
@endpush