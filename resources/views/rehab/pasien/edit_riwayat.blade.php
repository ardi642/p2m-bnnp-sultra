@extends('admin')

@section('content')
@php
    $ref = request('ref', 'show'); 
    $backUrl = $ref === 'index' ? route('rehab.pasien.index') : route('rehab.pasien.show', $riwayat->rehab_pasien_id);
@endphp

<main class="admin-main">
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Edit Riwayat Rehab</h4>
                <p class="text-secondary small mb-0">Pasien: <strong class="text-primary">{{ $riwayat->pasien->no_rekam_medis }}</strong></p>
            </div>
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><strong>Periksa Kembali Inputan!</strong> Pastikan semua data wajib terisi dengan benar.</div>
                </div>
            </div>
        @endif

        <form action="{{ route('rehab.pasien.riwayat.update', ['id' => $riwayat->id, 'ref' => $ref]) }}" method="POST">
            @csrf @method('PUT')
            <div class="row g-4">
                {{-- IDENTITAS PASIEN (TERKUNCI FULL WIDTH) --}}
                <div class="col-12">
                    <div class="card shadow-sm border-0 bg-light opacity-75">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-secondary border-bottom pb-2 mb-4"><i class="bi bi-lock-fill me-2"></i>Identitas Dasar (Terkunci)</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">No Rekam Medis</label>
                                    <input type="text" class="form-control fw-bold bg-white" value="{{ $riwayat->pasien->no_rekam_medis }}" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Nama Pasien</label>
                                    <input type="text" class="form-control bg-white" value="{{ $riwayat->pasien->nama_pasien }}" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Jenis Kelamin</label>
                                    <input type="text" class="form-control bg-white" value="{{ $riwayat->pasien->jenis_kelamin }}" disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DATA KEDATANGAN EDIT (FULL WIDTH) --}}
                <div class="col-12">
                    <div class="card shadow-sm border-0 border-primary-subtle">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-4"><i class="bi bi-pencil-square me-2"></i>Edit Data Kedatangan Ini</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Tanggal Rehab <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_rehab" value="{{ old('tanggal_rehab', $riwayat->tanggal_rehab->format('Y-m-d')) }}" class="form-control py-2" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Sumber Pasien <span class="text-danger">*</span></label>
                                    <select name="sumber_pasien" class="form-select py-2" required>
                                        <option value="Voluntary" {{ old('sumber_pasien', $riwayat->sumber_pasien) == 'Voluntary' ? 'selected' : '' }}>Voluntary (Sukarela)</option>
                                        <option value="Compulsory" {{ old('sumber_pasien', $riwayat->sumber_pasien) == 'Compulsory' ? 'selected' : '' }}>Compulsory (Wajib)</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Usia Saat Kedatangan <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="usia" class="form-control py-2" value="{{ old('usia', $riwayat->usia) }}" required min="1">
                                        <span class="input-group-text bg-light">Tahun</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Pendidikan <span class="text-danger">*</span></label>
                                    <select name="pendidikan" class="form-select py-2" required>
                                        @foreach(\App\Constants\Pendidikan::ALL as $p) 
                                            <option value="{{ $p }}" {{ old('pendidikan', $riwayat->pendidikan) == $p ? 'selected' : '' }}>{{ $p }}</option> 
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Pekerjaan <span class="text-danger">*</span></label>
                                    <select name="pekerjaan" class="form-select py-2" required>
                                        @foreach(\App\Constants\Pekerjaan::ALL as $p) 
                                            <option value="{{ $p }}" {{ old('pekerjaan', $riwayat->pekerjaan) == $p ? 'selected' : '' }}>{{ $p }}</option> 
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 mt-4" wire:ignore>
                                    <label class="form-label small fw-semibold">Jenis Narkotika yang Digunakan <span class="text-danger">*</span></label>
                                    <select id="select-narko" name="narkotika_ids[]" multiple required placeholder="Pilih satu atau lebih...">
                                        @php $selectedNarko = $riwayat->narkotika->pluck('id')->toArray(); @endphp
                                        @foreach($masterNarkotika as $n) 
                                            <option value="{{ $n->id }}" {{ in_array($n->id, old('narkotika_ids', $selectedNarko)) ? 'selected' : '' }}>{{ $n->nama_narkotika }}</option> 
                                        @endforeach
                                    </select>
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

@push('styles')
<style>
    .ts-control { border: 1px solid #ced4da; border-radius: 0.375rem; padding: 0.5rem 0.75rem; }
    .ts-wrapper.focus .ts-control { border-color: #6c757d; box-shadow: 0 0 0 0.25rem rgba(108, 117, 125, 0.15); }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() { 
        new TomSelect('#select-narko', { plugins: ['remove_button', 'clear_button'], create: false }); 
    });
</script>
@endpush