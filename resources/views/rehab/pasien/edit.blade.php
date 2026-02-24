@extends('admin')

@section('content')
@php
    $ref = request('ref', 'show'); 
    $backUrl = $ref === 'index' ? route('rehab.pasien.index') : route('rehab.pasien.show', $pasien->id);
@endphp

<main class="admin-main">
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Edit Identitas Pasien</h4>
                <p class="text-secondary small mb-0">Jika nama, Tgl Lahir, atau JK diubah, ID Pasien akan ter-update otomatis.</p>
            </div>
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Batal
            </a>
        </div>

        <form action="{{ route('rehab.pasien.update', ['pasien' => $pasien->id, 'ref' => $ref]) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-4">
                                <i class="bi bi-person-badge me-2"></i>Identitas Dasar
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">ID Pasien Saat Ini</label>
                                    <input type="text" class="form-control py-2 font-monospace bg-light text-muted" value="{{ $pasien->id_pasien }}" disabled>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Nama Pasien / Inisial <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_pasien" class="form-control py-2 @error('nama_pasien') is-invalid @enderror" value="{{ old('nama_pasien', $pasien->nama_pasien) }}">
                                    @error('nama_pasien') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Tanggal Lahir <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_lahir" class="form-control py-2 @error('tanggal_lahir') is-invalid @enderror" value="{{ old('tanggal_lahir', $pasien->tanggal_lahir->format('Y-m-d')) }}">
                                    @error('tanggal_lahir') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
                                    <select name="jenis_kelamin" class="form-select py-2 @error('jenis_kelamin') is-invalid @enderror">
                                        <option value="Laki-laki" {{ old('jenis_kelamin', $pasien->jenis_kelamin) == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                        <option value="Perempuan" {{ old('jenis_kelamin', $pasien->jenis_kelamin) == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                                    </select>
                                    @error('jenis_kelamin') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pb-5">
                <button type="reset" class="btn btn-light border px-4 py-2">Kembalikan Semula</button>
                <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</main>
@endsection