@extends('admin')
@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0">Kegiatan P2M</h1>
                            <p class="text-muted mb-0">Input Data Kegiatan P2M</p>
                        </div>
                    </div>
                </div>
            </div>
            @include('p2m.partials.select-p2m-create')

            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="card shadow-lg p-5">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="card-title mb-2" id="judul">Input Data Kegiatan P2M Sosialisasi Tatap Muka/Konvensional</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('p2m.sosialisasi.store') }}" method="POST" enctype="multipart/form-data" id="form-p2m">
                                @csrf
                                
                                <div class="row g-4 mb-5">
                                    
                                    {{-- Input 1: Satuan Kerja --}}
                                    @if (auth()->user()->pegawai == null)    
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="satuan_kerja_id" class="form-label">Satuan Kerja</label>
                                            <select class="form-select @error('satuan_kerja_id') is-invalid @enderror" name="satuan_kerja_id">
                                                <option value="" selected>pilih satuan kerja</option>
                                                @foreach ($satuanKerjas as $satuanKerja)
                                                    <option value="{{ $satuanKerja->id }}" @selected(old('satuan_kerja_id') == $satuanKerja->id)>
                                                    {{ $satuanKerja->satuan_kerja }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('satuan_kerja_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    @endif

                                    {{-- Input 2: Anggaran --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="anggaran_pelaksanaan" class="form-label">Anggaran Pelaksanaan</label>
                                            <select class="form-select @error('anggaran_pelaksanaan') is-invalid @enderror" name="anggaran_pelaksanaan">
                                                <option value="" disabled selected>pilih anggaran pelaksanaan</option>
                                                <option value="DIPA" @selected(old('anggaran_pelaksanaan') == 'DIPA')>DIPA</option>
                                                <option value="NON DIPA" @selected(old('anggaran_pelaksanaan') == 'NON DIPA')>NON DIPA</option>
                                            </select>
                                            @error('anggaran_pelaksanaan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Input 3: Nama Kegiatan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="nama_kegiatan" class="form-label">Nama Kegiatan</label>
                                            <input type="text" class="form-control @error('nama_kegiatan') is-invalid @enderror" placeholder="masukkan nama kegiatan" name="nama_kegiatan" value="{{ old('nama_kegiatan') }}">
                                            @error('nama_kegiatan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Input 4: Sasaran Kegiatan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="sasaran_kegiatan" class="form-label">Sasaran Kegiatan</label>
                                            <select class="form-select @error('sasaran_kegiatan') is-invalid @enderror" name="sasaran_kegiatan">
                                                <option value="" selected>pilih sasaran kegiatan</option>
                                                <option value="lingkungan pendidikan" @selected(old('sasaran_kegiatan') == 'lingkungan pendidikan')>Lingkungan Pendidikan</option>
                                                <option value="lingkungan kerja" @selected(old('sasaran_kegiatan') == 'lingkungan kerja')>Lingkungan Kerja (Pemerintah / Swasta)</option>
                                                <option value="lingkungan masyarakat" @selected(old('sasaran_kegiatan') == 'lingkungan masyarakat')>Lingkungan Masyarakat</option>
                                            </select>
                                            @error('sasaran_kegiatan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Input 5: Tanggal Pelaksanaan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="tanggal_pelaksanaan" class="form-label">Tanggal Pelaksanaan</label>
                                            <input type="date" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan') }}">
                                            @error('tanggal_pelaksanaan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Input 6: Tempat Kegiatan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="tempat_kegiatan" class="form-label">Tempat Kegiatan</label>
                                            <textarea class="form-control @error('tempat_kegiatan') is-invalid @enderror" rows="1" placeholder="masukkan tempat kegiatan" name="tempat_kegiatan">{{ old('tempat_kegiatan') }}</textarea>
                                            @error('tempat_kegiatan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Input 7: Pegawai (Tom Select) --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="select-pegawai" class="form-label">Nama Pegawai yang ditugaskan</label>
                                            <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Pilih Pegawai..." autocomplete="off" class="form-control @error('pegawai_nips') is-invalid @enderror">
                                                <option value="">Pilih pegawai...</option>
                                                @foreach ($pegawais as $pegawai_item)
                                                    <option value="{{ $pegawai_item->nip }}" 
                                                        @selected(collect(old('pegawai_nips'))->contains($pegawai_item->nip))>
                                                        {{ $pegawai_item->nama }} - NIP: {{ $pegawai_item->nip }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('pegawai_nips') 
                                                <div class="invalid-feedback d-block">{{ $message }}</div> 
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Input 8: Jumlah Peserta --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="jumlah_peserta" class="form-label">Jumlah Peserta</label>
                                            <input type="number" class="form-control @error('jumlah_peserta') is-invalid @enderror" placeholder="masukkan jumlah peserta" name="jumlah_peserta" value="{{ old('jumlah_peserta') }}">
                                            @error('jumlah_peserta')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Input 9: Dokumentasi (FilePond) --}}
                                    <div class="col-12">
                                        <div class="mb-5">
                                            <label class="form-label fw-bold h5 border-bottom pb-2 d-block">
                                                <i class="bi bi-paperclip me-2"></i>Upload Dokumentasi
                                            </label>
                                            <p class="text-muted small">
                                                Silakan upload <strong>Foto Kegiatan</strong> (.jpg, .png) dan <strong>Laporan/Dokumen</strong> (.pdf, .docx). 
                                                <br>File akan diupload sementara, tekan tombol <b>Simpan</b> di bawah untuk memproses secara permanen.
                                            </p>
                                            
                                            <input type="file" 
                                                class="filepond"
                                                name="dokumentasi[]" 
                                                multiple 
                                                data-allow-reorder="true"
                                                data-max-file-size="10MB"
                                                data-max-files="10">

                                            @error('dokumentasi')
                                                <div class="text-danger small mt-2">
                                                    {{ $message }} (Pastikan file berwarna HIJAU sebelum simpan)
                                                </div>
                                            @enderror
                                            @error('dokumentasi.*')
                                                <div class="text-danger small mt-2">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                    </div>

                                </div> 

                                <div class="row justify-content-end">
                                    <div class="col-12 col-lg-auto">
                                        {{-- ID tombol submit penting untuk JS --}}
                                        <button type="submit" id="btn-submit" class="btn btn-primary w-100 mb-4 mb-lg-0">
                                            <i class="bi bi-save me-1"></i> Tambah Data
                                        </button>
                                    </div>
                                    <div class="col-12 col-lg-auto">
                                        <button type="reset" class="btn btn-secondary w-100 mb-4 mb-lg-0">Reset</button>
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
        // --- 1. Konfigurasi Tom Select ---
        if(typeof TomSelect !== 'undefined'){
            new TomSelect("#select-pegawai", {
                create: false,
                sortField: { field: "text", direction: "asc" },
                maxItems: null,
                placeholder: "Cari atau pilih pegawai...",
                plugins: ['remove_button'],
            });
        }

        // --- 2. Konfigurasi FilePond ---
        const inputElement = document.querySelector('input.filepond');
        const submitBtn = document.getElementById('btn-submit'); // Ambil tombol submit

        const pond = FilePond.create(inputElement, {
            acceptedFileTypes: ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            labelIdle: 'Drag & Drop file Anda atau <span class="filepond--label-action">Cari File</span>',
            imagePreviewHeight: 120,
            credits: false,

            // A. LOGIC FILE LAMA (Gagal Validasi)
            // Ini akan memuat ulang file yang sudah diupload ke temp jika validasi form gagal
            files: [
                @if(old('dokumentasi'))
                    @foreach(old('dokumentasi') as $file)
                    {
                        source: '{{ $file }}',
                        options: {
                            type: 'local', // Menandakan file ini sudah ada di server (folder temp)
                        }
                    },
                    @endforeach
                @endif
            ],

            // B. KONFIGURASI SERVER
            server: {
                process: {
                    url: '{{ route('upload.temp') }}',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    onload: (response) => {
                        // Response dari server (biasanya nama file temp)
                        return response; 
                    },
                    onerror: (response) => {
                        // Jika error, kembalikan tombol submit
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-save me-1"></i> Tambah Data';
                        return response;
                    }
                },
                revert: {
                    url: '{{ route('revert.temp') }}',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                },
                // Load digunakan untuk menampilkan preview file 'local' (file lama saat validasi gagal)
                load: {
                    url: '{{ route('load.temp') }}/?file=', // Pastikan route ini ada: Route::get('load-temp', ...)->name('load.temp');
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                },
            },

            // C. EVENT LISTENER UNTUK TOMBOL SUBMIT
            onprocessstart: (file) => {
                // Saat upload dimulai, matikan tombol submit
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Mengupload...';
            },
            onprocessfiles: () => {
                // Saat SEMUA file selesai diproses, aktifkan tombol submit
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-save me-1"></i> Tambah Data';
            },
            onwarning: (error, file, status) => {
                // Jika user mencoba upload > max files
                alert('Maksimal file terlampaui');
            }
        });
    });
</script>
@endpush