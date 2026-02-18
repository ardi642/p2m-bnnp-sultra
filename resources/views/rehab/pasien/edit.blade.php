@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">

            {{-- Header Title --}}
            <div class="row justify-content-center mb-4">
                <div class="col-12 col-lg-8">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-1 fw-bold text-dark">Edit Data Pasien</h1>
                            <p class="text-muted mb-0">Perbarui Data Pasien Rehabilitasi Narkotika</p>
                        </div>
                        <a href="{{ route('rehab.pasien.index') }}"
                            class="btn btn-outline-secondary d-flex align-items-center gap-2">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-lg-8">
                    <div class="card border-0 shadow-lg">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h5 class="card-title mb-0 fw-bold">
                                <i class="bi bi-pencil-square me-2"></i>Form Edit Pasien
                            </h5>
                        </div>

                        <div class="card-body p-4 p-lg-5">

                            <form action="{{ route('rehab.pasien.update', $pasien->id) }}" method="POST" id="form-edit">
                                @csrf
                                @method('PUT')

                                {{-- SECTION 1: DATA DIRI PASIEN --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">
                                    <i class="bi bi-person-circle me-2"></i>Data Diri Pasien
                                </h6>

                                <div class="row g-4 mb-5">
                                    {{-- Satuan Kerja (Hanya Admin) --}}
                                    @if (auth()->user()->isAdmin())
                                        <div class="col-12">
                                            <label class="form-label fw-semibold text-secondary small">Satuan Kerja <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select @error('satuan_kerja_id') is-invalid @enderror"
                                                name="satuan_kerja_id" required>
                                                <option value="" selected disabled>-- Pilih Satuan Kerja --</option>
                                                @foreach ($satuanKerjas as $satker)
                                                    <option value="{{ $satker->id }}" @selected(old('satuan_kerja_id', $pasien->satuan_kerja_id) == $satker->id)>
                                                        {{ $satker->satuan_kerja }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('satuan_kerja_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @else
                                        <div class="col-6">
                                            <label class="form-label fw-semibold text-secondary small">Satuan Kerja</label>
                                            <input type="hidden" name="satuan_kerja_id"
                                                value="{{ auth()->user()->getSatkerId() }}">
                                            <input type="text" class="form-control"
                                                value="{{ auth()->user()->pegawai?->satuanKerja?->satuan_kerja ?? '-' }}"
                                                disabled>
                                        </div>
                                    @endif

                                    <div class="col-6">
                                        <label class="form-label fw-semibold text-secondary small">No. Rekam Medis</label>
                                        <input type="text" class="form-control" value="{{ $pasien->rekam_medis ?? '-' }}"
                                            disabled>
                                    </div>

                                    {{-- Nama Pasien --}}
                                    <div class="col-12 {{ auth()->user()->isAdmin() ? 'col-md-6' : '' }}">
                                        <label class="form-label fw-semibold text-secondary small">Nama Pasien <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control form-control-lg @error('nama_pasien') is-invalid @enderror"
                                            name="nama_pasien" value="{{ old('nama_pasien', $pasien->nama_pasien) }}"
                                            placeholder="Masukkan nama pasien" required>
                                        @error('nama_pasien')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Jenis Kelamin --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Jenis Kelamin <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select @error('jenis_kelamin') is-invalid @enderror"
                                            name="jenis_kelamin" required>
                                            <option value="" selected disabled>-- Pilih Jenis Kelamin --</option>
                                            <option value="Laki-laki" @selected(old('jenis_kelamin', $pasien->jenis_kelamin) == 'Laki-laki')>Laki-laki</option>
                                            <option value="Perempuan" @selected(old('jenis_kelamin', $pasien->jenis_kelamin) == 'Perempuan')>Perempuan</option>
                                        </select>
                                        @error('jenis_kelamin')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Usia --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Usia <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control @error('usia') is-invalid @enderror"
                                                name="usia" value="{{ old('usia', $pasien->usia) }}" placeholder="0"
                                                min="0" max="120" required>
                                            <span class="input-group-text bg-light text-secondary">Tahun</span>
                                        </div>
                                        @error('usia')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Pekerjaan --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Pekerjaan <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select @error('pekerjaan') is-invalid @enderror"
                                            name="pekerjaan" required>
                                            <option value="" selected disabled>-- Pilih Pekerjaan --</option>
                                            @foreach (\App\Models\RehabPasien::Pekerjaan as $p)
                                                <option value="{{ $p }}" @selected(old('pekerjaan', $pasien->pekerjaan) == $p)>
                                                    {{ $p }}</option>
                                            @endforeach
                                        </select>
                                        @error('pekerjaan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Pendidikan --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Pendidikan <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select @error('pendidikan') is-invalid @enderror"
                                            name="pendidikan" required>
                                            <option value="" selected disabled>-- Pilih Pendidikan --</option>
                                            @foreach (\App\Models\RehabPasien::Pendidikan as $p)
                                                <option value="{{ $p }}" @selected(old('pendidikan', $pasien->pendidikan) == $p)>
                                                    {{ $p }}</option>
                                            @endforeach
                                        </select>
                                        @error('pendidikan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>


                                {{-- SECTION 2: DATA NARKOTIKA & SUMBER --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">
                                    <i class="bi bi-capsule me-2"></i>Informasi Narkotika & Sumber
                                </h6>

                                <div class="row g-4 mb-5">
                                    {{-- Jenis Narkotika --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Jenis Narkotika <span
                                                class="text-danger">*</span></label>
                                        <select id="select-narkotika" name="narkotika_id[]" multiple autocomplete="off"
                                            required>
                                            <option value="">-- Pilih Jenis Narkotika --</option>


                                            @foreach ($narkotikas as $narkotika)
                                                @php
                                                    $selectedNarkotika = collect(
                                                        old(
                                                            'narkotika_id',
                                                            $pasien->narkotikas->pluck('id')->toArray(),
                                                        ),
                                                    )->contains($narkotika->id);
                                                @endphp
                                                <option value="{{ $narkotika->id }}" @selected($selectedNarkotika)>
                                                    {{ $narkotika->nama_narkotika }} ({{ $narkotika->id }})
                                                </option>
                                            @endforeach

                                        </select>
                                        @error('narkotika_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Sumber Pasien --}}
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold text-secondary small">Sumber Pasien <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select @error('sumber_pasien') is-invalid @enderror"
                                            name="sumber_pasien" required>
                                            {{-- <option value="" selected disabled>-- Pilih Sumber Pasien --</option> --}}
                                            @foreach (\App\Models\RehabPasien::Sumber_pasien as $p)
                                                <option value="{{ $p }}" @selected(old('sumber_pasien', $pasien->sumber_pasien) == $p)>
                                                    {{ $p }}</option>
                                            @endforeach
                                        </select>
                                        @error('sumber_pasien')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>


                                {{-- BUTTON AKSI --}}
                                <div
                                    class="d-flex flex-column-reverse flex-lg-row justify-content-end gap-2 pt-3 border-top mt-4">
                                    <a href="{{ route('rehab.pasien.index') }}"
                                        class="btn btn-light border text-secondary px-4">
                                        <i class="bi bi-x-circle me-1"></i> Batal
                                    </a>
                                    <button type="submit" class="btn btn-primary px-5 shadow-sm">
                                        <i class="bi bi-save me-1"></i> Perbarui Data
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
    <style>
        .form-control:focus,
        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .form-control.is-invalid:focus,
        .form-select.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
    </style>
@endpush

@push('scripts')
    <script type="module">
        document.addEventListener("DOMContentLoaded", function() {
            // 1. TOM SELECT
            if (typeof TomSelect !== 'undefined') {
                new TomSelect("#select-narkotika", {
                    create: false,
                    sortField: {
                        field: "text",
                        direction: "asc"
                    },
                    maxItems: null,
                    placeholder: "Pilih Narkotika...",
                    plugins: ['remove_button', 'clear_button'],
                    render: {
                        option: function(data, escape) {
                            return '<div class="d-flex align-items-center"><i class="bi bi-person me-2 text-muted"></i>' +
                                escape(data.text) + '</div>';
                        },
                        item: function(data, escape) {
                            return '<div>' + escape(data.text) + '</div>';
                        }
                    }
                });
            }

            // Simple form validation
            const form = document.getElementById('form-edit');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!form.checkValidity()) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            }
        });
    </script>
@endpush



@push('styles')
    @vite(['resources/css/filepond.css'])
    <style>
        .dashed-border {
            border-style: dashed !important;
            border-width: 1px !important;
        }

        .ts-control {
            border: 1px solid #dee2e6;
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            box-shadow: none;
        }

        .ts-control.focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .filepond--panel-root {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
        }

        .border-dashed {
            border-style: dashed !important;
            border-width: 2px !important;
        }

        .transition-all {
            transition: all 0.3s ease;
        }

        .border-danger-subtle-thick {
            border-color: #dc3545 !important;
            border-width: 2px !important;
        }

        .filepond--item {
            width: 100%;
        }
    </style>
@endpush

@push('scripts')
    {{-- Load JS Global via Vite --}}
    @vite(['resources/js/filepond.js'])

    <script type="module">
        document.addEventListener("DOMContentLoaded", function() {

            // 1. TOM SELECT
            if (typeof TomSelect !== 'undefined') {
                new TomSelect("#select-pegawai", {
                    create: false,
                    sortField: {
                        field: "text",
                        direction: "asc"
                    },
                    maxItems: null,
                    placeholder: "Pilih pegawai...",
                    plugins: ['remove_button', 'clear_button'],
                    render: {
                        option: function(data, escape) {
                            return '<div class="d-flex align-items-center"><i class="bi bi-person me-2 text-muted"></i>' +
                                escape(data.text) + '</div>';
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
                loadRoute: '{{ route('load.temp') }}',
                csrfToken: '{{ csrf_token() }}',
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
                console.error(
                    "FilePondManager belum dimuat. Pastikan 'npm run build' atau 'npm run dev' berjalan.");
            }
        });

        // 3. ALPINE JS LINK MANAGER
        document.addEventListener('alpine:init', () => {
            Alpine.data('linkManager', (initialData = []) => ({
                links: Array.isArray(initialData) ? initialData : [],
                addLink() {
                    this.links.push({
                        nama: '',
                        url: ''
                    });
                },
                removeLink(index) {
                    this.links.splice(index, 1);
                }
            }));
        });

        // 4. LOGIC HAPUS FILE LAMA
        window.markForDeletion = function(id) {
            const cardInner = document.querySelector('#file-card-' + id + ' .file-card-inner');
            const overlay = cardInner.querySelector('.delete-overlay');
            const btnDelete = document.getElementById('btn-delete-' + id);
            const containerInputs = document.getElementById('delete-inputs-container');

            if (!overlay.classList.contains('d-none')) {
                // BATAL HAPUS
                overlay.classList.add('d-none');
                overlay.classList.remove('d-flex');
                cardInner.classList.remove('border-danger-subtle-thick');
                btnDelete.classList.remove('btn-secondary');
                btnDelete.classList.add('btn-outline-danger');
                btnDelete.innerHTML = 'Hapus';
                const input = document.getElementById('input-delete-' + id);
                if (input) input.remove();
            } else {
                // TANDAI HAPUS
                overlay.classList.remove('d-none');
                overlay.classList.add('d-flex');
                cardInner.classList.add('border-danger-subtle-thick');
                btnDelete.classList.remove('btn-outline-danger');
                btnDelete.classList.add('btn-secondary');
                btnDelete.innerHTML = 'Batal';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_files[]';
                input.value = id;
                input.id = 'input-delete-' + id;
                containerInputs.appendChild(input);
            }
        };
    </script>
@endpush
