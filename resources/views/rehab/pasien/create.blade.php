@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">

            {{-- Header Title --}}
            <div class="row justify-content-center mb-4">
                <div class="col-12 col-lg-8">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-1 fw-bold text-dark">Tambah Data Pasien</h1>
                            <p class="text-muted mb-0">Input Data Pasien Rehabilitasi Narkotika Baru</p>
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
                                <i class="bi bi-person-plus me-2"></i>Form Input Pasien
                            </h5>
                        </div>

                        <div class="card-body p-4 p-lg-5">

                            <form action="{{ route('rehab.pasien.store') }}" method="POST" id="form-create">
                                @csrf

                                {{-- SECTION 1: DATA DIRI PASIEN --}}
                                <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">
                                    <i class="bi bi-person-circle me-2"></i>Data Diri Pasien
                                </h6>

                                <div class="row g-4 mb-5">
                                    {{-- Satuan Kerja (Hanya Admin) --}}
                                    @if (auth()->user()->isAdmin())
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold text-secondary small">Satuan Kerja <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select @error('satuan_kerja_id') is-invalid @enderror"
                                                name="satuan_kerja_id" required>
                                                <option value="" selected disabled>-- Pilih Satuan Kerja --</option>
                                                @foreach ($satuanKerjas as $satker)
                                                    <option value="{{ $satker->id }}" @selected(old('satuan_kerja_id') == $satker->id)>
                                                        {{ $satker->satuan_kerja }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('satuan_kerja_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @else
                                        <div class="col-12 col-md-6">
                                            <label class="form-label fw-semibold text-secondary small">Satuan Kerja</label>
                                            <input type="hidden" name="satuan_kerja_id"
                                                value="{{ auth()->user()->getSatkerId() }}">
                                            <input type="text" class="form-control"
                                                value="{{ auth()->user()->pegawai?->satuanKerja?->satuan_kerja ?? '-' }}"
                                                disabled>
                                        </div>
                                    @endif

                                    {{-- Nama Pasien --}}
                                    <div class="col-12 {{ auth()->user()->isAdmin() ? 'col-md-6' : '' }}">
                                        <label class="form-label fw-semibold text-secondary small">Nama Pasien <span
                                                class="text-danger">*</span></label>
                                        <input type="text"
                                            class="form-control form-control-lg @error('nama_pasien') is-invalid @enderror"
                                            name="nama_pasien" value="{{ old('nama_pasien') }}"
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
                                            <option value="Laki-laki" @selected(old('jenis_kelamin') == 'Laki-laki')>Laki-laki</option>
                                            <option value="Perempuan" @selected(old('jenis_kelamin') == 'Perempuan')>Perempuan</option>
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
                                                name="usia" value="{{ old('usia') }}" placeholder="0" min="0"
                                                max="120" required>
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
                                                <option value="{{ $p }}">{{ $p }}</option>
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
                                                <option value="{{ $p }}">{{ $p }}</option>
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
                                        <select class="form-select @error('narkotika_id') is-invalid @enderror"
                                            name="narkotika_id" required>
                                            <option value="" selected disabled>-- Pilih Jenis Narkotika --</option>
                                            @foreach ($narkotikas as $narkotika)
                                                <option value="{{ $narkotika->id }}" @selected(old('narkotika_id') == $narkotika->id)>
                                                    {{ $narkotika->nama_narkotika }}
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
                                            <option value="" selected disabled>-- Pilih Sumber Pasien --</option>
                                            <option value="Voluntary" @selected(old('sumber_pasien') == 'Voluntary')>Voluntary</option>
                                            <option value="Compulsory" @selected(old('sumber_pasien') == 'Compulsory')>Compulsory</option>
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
            // Simple form validation
            const form = document.getElementById('form-create');
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
