@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Tambah Riwayat Rehab</h4>
                <p class="text-secondary small mb-0">Pasien: <strong class="text-primary">{{ $pasien->no_rekam_medis }}</strong></p>
            </div>
            <a href="{{ route('rehab.pasien.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>

        <form action="{{ route('rehab.pasien.riwayat.store', $pasien->id) }}" method="POST" id="form-riwayat">
            @csrf
            <div class="row g-4">
                {{-- IDENTITAS PASIEN (TERKUNCI FULL WIDTH) --}}
                <div class="col-12">
                    <div class="card shadow-sm border-0 bg-light opacity-75">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-secondary border-bottom pb-2 mb-4"><i class="bi bi-lock-fill me-2"></i>Identitas Dasar (Terkunci)</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">No Rekam Medis</label>
                                    <input type="text" class="form-control fw-bold bg-white" value="{{ $pasien->no_rekam_medis }}" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Nama Pasien</label>
                                    <input type="text" class="form-control bg-white" value="{{ $pasien->nama_pasien }}" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Jenis Kelamin</label>
                                    <input type="text" class="form-control bg-white" value="{{ $pasien->jenis_kelamin }}" disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DATA KEDATANGAN BARU (FULL WIDTH) --}}
                <div class="col-12">
                    <div class="card shadow-sm border-0 border-success-subtle">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-success border-bottom pb-2 mb-4"><i class="bi bi-clipboard2-pulse me-2"></i>Data Kedatangan Saat Ini</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Tanggal Rehab <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_rehab" value="{{ date('Y-m-d') }}" class="form-control py-2" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Sumber Pasien <span class="text-danger">*</span></label>
                                    <select name="sumber_pasien" class="form-select py-2" required>
                                        <option value="" selected disabled>Pilih...</option>
                                        <option value="Voluntary">Voluntary (Sukarela)</option>
                                        <option value="Compulsory">Compulsory (Wajib)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Usia Saat Ini <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="usia" class="form-control py-2" required min="1" placeholder="Usia...">
                                        <span class="input-group-text bg-light">Tahun</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Pendidikan <span class="text-danger">*</span></label>
                                    <select name="pendidikan" class="form-select py-2" required>
                                        <option value="" selected disabled>Pilih Pendidikan...</option>
                                        @foreach(\App\Constants\Pendidikan::ALL as $p) <option value="{{ $p }}">{{ $p }}</option> @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Pekerjaan <span class="text-danger">*</span></label>
                                    <select name="pekerjaan" class="form-select py-2" required>
                                        <option value="" selected disabled>Pilih Pekerjaan...</option>
                                        @foreach(\App\Constants\Pekerjaan::ALL as $p) <option value="{{ $p }}">{{ $p }}</option> @endforeach
                                    </select>
                                </div>

                                <div class="col-12 mt-4" wire:ignore>
                                    <label class="form-label small fw-semibold">Jenis Narkotika yang Digunakan <span class="text-danger">*</span></label>
                                    <select id="select-narko" name="narkotika_ids[]" multiple required placeholder="Cari Narkotika...">
                                        @foreach($masterNarkotika as $n) <option value="{{ $n->id }}">{{ $n->nama_narkotika }}</option> @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- UPLOAD FILE DOKUMEN --}}
                <div class="col-12">
                    <div class="bg-light p-4 rounded-3 border border-dashed">
                        <label class="form-label fw-bold h6 mb-3 text-dark d-block border-bottom pb-2">
                            <i class="bi bi-cloud-arrow-up me-2"></i>Upload File & Link (Opsional)
                        </label>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="bg-white p-3 rounded border h-100 d-flex flex-column shadow-sm">
                                    <label class="form-label fw-bold small text-primary mb-1"><i class="bi bi-folder2-open me-2"></i>Dokumentasi Berkas</label>
                                    <div class="mb-3"><input type="file" id="fp-dokumentasi" name="dokumentasi[]" multiple></div>
                                    <hr class="border-secondary-subtle my-3">
                                    <div x-data="linkManager( {{ \Illuminate\Support\Js::from(array_values(old('dokumentasi_links', []))) }} )">
                                        <label class="form-label fw-bold small text-primary mb-2"><i class="bi bi-link-45deg me-1"></i>Tautkan Link</label>
                                        <template x-for="(link, index) in links" :key="index">
                                            <div class="input-group mb-2 input-group-sm">
                                                <input type="text" class="form-control" :name="`dokumentasi_links[${index}][nama]`" placeholder="Nama" x-model="link.nama" required>
                                                <input type="url" class="form-control" :name="`dokumentasi_links[${index}][url]`" placeholder="https://" x-model="link.url" required>
                                                <button type="button" class="btn btn-outline-danger" @click="removeLink(index)"><i class="bi bi-x"></i></button>
                                            </div>
                                        </template>
                                        <button type="button" class="btn btn-xs btn-outline-primary w-100 mt-1" @click="addLink()">Tambah Link</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="bg-white p-3 rounded border h-100 d-flex flex-column shadow-sm">
                                    <label class="form-label fw-bold small text-danger mb-1"><i class="bi bi-paperclip me-2"></i>Lampiran Tambahan</label>
                                    <div class="mb-3"><input type="file" id="fp-lampiran" name="lampiran[]" multiple></div>
                                    <hr class="border-secondary-subtle my-3">
                                    <div x-data="linkManager( {{ \Illuminate\Support\Js::from(array_values(old('lampiran_links', []))) }} )">
                                        <label class="form-label fw-bold small text-danger mb-2"><i class="bi bi-link-45deg me-1"></i>Tautkan Link</label>
                                        <template x-for="(link, index) in links" :key="index">
                                            <div class="input-group mb-2 input-group-sm">
                                                <input type="text" class="form-control" :name="`lampiran_links[${index}][nama]`" placeholder="Nama" x-model="link.nama" required>
                                                <input type="url" class="form-control" :name="`lampiran_links[${index}][url]`" placeholder="https://" x-model="link.url" required>
                                                <button type="button" class="btn btn-outline-danger" @click="removeLink(index)"><i class="bi bi-x"></i></button>
                                            </div>
                                        </template>
                                        <button type="button" class="btn btn-xs btn-outline-danger w-100 mt-1" @click="addLink()">Tambah Link</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pb-5">
                <button type="reset" class="btn btn-light border px-4 py-2">Reset Form</button>
                <button type="submit" id="btn-submit" class="btn btn-success px-5 py-2 fw-bold shadow-sm">Simpan Riwayat Baru</button>
            </div>
        </form>
    </div>
</main>
@endsection

@push('styles')
    @vite(['resources/css/filepond.css', 'resources/js/filepond.js'])
    <style>
        .ts-control { border: 1px solid #ced4da; border-radius: 0.375rem; padding: 0.5rem 0.75rem; }
        .border-dashed { border: 1px dashed #ced4da !important; }
    </style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        new TomSelect('#select-narko', { plugins: ['remove_button', 'clear_button'], create: false });

        if (window.FilePondManager) {
            const commonConfig = { uploadRoute: '{{ route('upload.temp') }}', revertRoute: '{{ route('revert.temp') }}', loadRoute: '{{ route('load.temp') }}', csrfToken: '{{ csrf_token() }}', submitBtnId: 'btn-submit' };
            window.FilePondManager.create('#fp-dokumentasi', { ...commonConfig, maxSize: '10MB', existingFiles: @json(old('dokumentasi', [])) });
            window.FilePondManager.create('#fp-lampiran', { ...commonConfig, maxSize: '10MB', existingFiles: @json(old('lampiran', [])) });
            window.FilePondManager.attachFormSubmit('form-riwayat', 'btn-submit');
        }
    });

    document.addEventListener('alpine:init', () => {
        Alpine.data('linkManager', (initialData = []) => ({
            links: Array.isArray(initialData) ? initialData : [], 
            addLink() { this.links.push({ nama: '', url: '' }); },
            removeLink(index) { this.links.splice(index, 1); }
        }));
    });
</script>
@endpush