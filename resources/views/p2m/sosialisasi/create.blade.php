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
                            <h5 class="card-title mb-2">Input Data Kegiatan P2M Sosialisasi</h5>
                        </div>
                        <div class="card-body">
                            
                            {{-- ID Form "form-create" --}}
                            <form action="{{ route('p2m.sosialisasi.store') }}" method="POST" enctype="multipart/form-data" id="form-create">
                                @csrf
                                
                                <div class="row g-4 mb-5">
                                    {{-- 1. Satuan Kerja --}}
                                    @if (auth()->user()->isAdmin())    
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Satuan Kerja <span class="text-danger">*</span></label>
                                            <select class="form-select @error('satuan_kerja_id') is-invalid @enderror" name="satuan_kerja_id">
                                                <option value="" selected disabled>Pilih satuan kerja</option>
                                                @foreach ($satuanKerjas as $satuanKerja)
                                                    <option value="{{ $satuanKerja->id }}" @selected(old('satuan_kerja_id') == $satuanKerja->id)>
                                                        {{ $satuanKerja->satuan_kerja }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('satuan_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    @endif

                                    {{-- 2. Anggaran --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Anggaran Pelaksanaan <span class="text-danger">*</span></label>
                                            <select class="form-select @error('anggaran_pelaksanaan') is-invalid @enderror" name="anggaran_pelaksanaan">
                                                <option value="" disabled selected>Pilih anggaran</option>
                                                <option value="DIPA" @selected(old('anggaran_pelaksanaan') == 'DIPA')>DIPA</option>
                                                <option value="NON DIPA" @selected(old('anggaran_pelaksanaan') == 'NON DIPA')>NON DIPA</option>
                                            </select>
                                            @error('anggaran_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 3. Nama Kegiatan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control @error('nama_kegiatan') is-invalid @enderror" name="nama_kegiatan" value="{{ old('nama_kegiatan') }}" placeholder="Masukkan nama kegiatan">
                                            @error('nama_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 4. Sasaran --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Sasaran Kegiatan <span class="text-danger">*</span></label>
                                            <select class="form-select @error('sasaran_kegiatan') is-invalid @enderror" name="sasaran_kegiatan">
                                                <option value="" selected disabled>Pilih sasaran</option>
                                                <option value="lingkungan pendidikan" @selected(old('sasaran_kegiatan') == 'lingkungan pendidikan')>Lingkungan Pendidikan</option>
                                                <option value="lingkungan kerja" @selected(old('sasaran_kegiatan') == 'lingkungan kerja')>Lingkungan Kerja</option>
                                                <option value="lingkungan masyarakat" @selected(old('sasaran_kegiatan') == 'lingkungan masyarakat')>Lingkungan Masyarakat</option>
                                            </select>
                                            @error('sasaran_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 5. Tanggal --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan') }}">
                                            @error('tanggal_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 6. Tempat --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Tempat Kegiatan <span class="text-danger">*</span></label>
                                            <textarea class="form-control @error('tempat_kegiatan') is-invalid @enderror" rows="1" name="tempat_kegiatan" placeholder="Masukkan lokasi">{{ old('tempat_kegiatan') }}</textarea>
                                            @error('tempat_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 7. Pegawai (Tom Select) --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Nama Pegawai <span class="text-danger">*</span></label>
                                            <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Cari pegawai..." autocomplete="off">
                                                <option value="">Pilih pegawai...</option>
                                                @foreach ($pegawais as $pgw)
                                                    <option value="{{ $pgw->nip }}" @selected(collect(old('pegawai_nips'))->contains($pgw->nip))>
                                                        {{ $pgw->nama }} - NIP: {{ $pgw->nip }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('pegawai_nips') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 8. Jumlah Peserta --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Jumlah Peserta <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('jumlah_peserta') is-invalid @enderror" name="jumlah_peserta" value="{{ old('jumlah_peserta') }}" placeholder="0">
                                            @error('jumlah_peserta') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 9. Dokumentasi (FilePond) --}}
                                    <div class="col-12">
                                        <div class="mb-5">
                                            <label class="form-label fw-bold h5 border-bottom pb-2 d-block">
                                                <i class="bi bi-paperclip me-2"></i>Upload Dokumentasi <span class="text-danger">*</span>
                                            </label>
                                            <p class="text-muted small">
                                                Silakan upload <strong>Foto Kegiatan</strong> (.jpg, .png) dan <strong>Laporan</strong> (.pdf, .docx). 
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
                                        </div>
                                    </div>
                                </div> 

                                {{-- BUTTONS --}}
                                <div class="row justify-content-end">
                                    <div class="col-12 col-lg-auto">
                                        {{-- ID tombol submit penting untuk JS --}}
                                        <button type="submit" id="btn-submit" class="btn btn-primary w-100 mb-4 mb-lg-0">
                                            <i class="bi bi-save me-1"></i> Tambah Data
                                        </button>
                                    </div>
                                    <div class="col-12 col-lg-auto">
                                        {{-- Tombol Reset menggunakan Reload Page agar bersih total --}}
                                        <button type="button" onclick="window.location.reload()" class="btn btn-secondary w-100 mb-4 mb-lg-0">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Form
                                        </button>
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
        
        // 1. Tom Select
        if(typeof TomSelect !== 'undefined'){
            new TomSelect("#select-pegawai", {
                create: false,
                sortField: { field: "text", direction: "asc" },
                maxItems: null,
                placeholder: "Cari atau pilih pegawai...",
                plugins: ['remove_button'],
            });
        }

        // 2. Definisi Elemen
        const inputElement = document.querySelector('input.filepond');
        const form = document.getElementById('form-create');
        const submitBtn = document.getElementById('btn-submit');
        const originalBtnText = submitBtn.innerHTML;

        // Fungsi Helper State Tombol
        const setButtonState = (isLoading, text = null) => {
            if (isLoading) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + (text || 'Mengupload...');
            } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        };

        // 3. Konfigurasi FilePond
        const pond = FilePond.create(inputElement, {
            acceptedFileTypes: ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            labelIdle: 'Drag & Drop file Anda atau <span class="filepond--label-action">Cari File</span>',
            imagePreviewHeight: 120,
            credits: false,
            allowMultiple: true,

            // Logic Restore File jika Validasi Gagal
            files: [
                @if(old('dokumentasi'))
                    @foreach(old('dokumentasi') as $file)
                    {
                        source: '{{ $file }}', 
                        options: { type: 'local' } 
                    },
                    @endforeach
                @endif
            ],

            server: {
                process: {
                    url: '{{ route('upload.temp') }}',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    onload: (response) => { return response; },
                    onerror: (response) => {
                        setButtonState(false); 
                        return response;
                    }
                },
                revert: {
                    url: '{{ route('revert.temp') }}',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                },
                load: {
                    url: '{{ route('load.temp') }}/?file=',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                },
            },

            // --- PERBAIKAN PENTING: HAPUS ONADDFILE ---
            // Kita hanya mengunci tombol jika BENAR-BENAR upload (onprocessstart).
            // Ini mencegah tombol terkunci saat file lama di-load kembali oleh blade (old input).
            onprocessstart: () => { setButtonState(true); },
            onprocessfiles: () => { setButtonState(false); },
            onwarning: () => { setButtonState(false); },
            onerror: () => { setButtonState(false); },
            
            // Handle jika user menghapus file yang sedang loading
            onremovefile: () => {
                const files = pond.getFiles();
                const isStillBusy = files.some(file => file.status === 3 || file.status === 9);
                if (!isStillBusy) { setButtonState(false); }
            }
        });

        // 4. Intercept Submit (Proteksi Paksa)
        form.addEventListener('submit', function(e) {
            const files = pond.getFiles();
            
            // Cek Status File
            // Status 2 = Idle (File yang sudah sukses diupload/load)
            // Status 5 = Process Complete (Baru sukses upload)
            // Selain itu (3 Uploading, 9 Queued, dll) dianggap BUSY/BAHAYA
            const isBusy = files.some(file => file.status !== 2 && file.status !== 5);

            if (isBusy) {
                e.preventDefault(); 
                e.stopPropagation();
                
                // Pastikan visual terkunci
                setButtonState(true, 'Tunggu Upload...');
                
                // Alert User
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Mohon Tunggu',
                        text: 'File sedang diproses. Tunggu hingga selesai/hijau.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert('Mohon tunggu, file sedang diupload ke server.');
                }
            } else {
                // Jika lolos cek, kunci tombol dengan status "Menyimpan..."
                setButtonState(true, 'Menyimpan...');
            }
        });

    });
</script>
@endpush