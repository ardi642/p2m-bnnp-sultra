@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Pendaftaran Pasien Baru</h4>
                <p class="text-secondary small mb-0">Sistem akan otomatis meng-generate No. Rekam Medis.</p>
            </div>
            <a href="{{ route('rehab.pasien.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>

        <form action="{{ route('rehab.pasien.store') }}" method="POST">
            @csrf
            <div class="row g-4">
                {{-- IDENTITAS PASIEN (FULL WIDTH) --}}
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-4"><i class="bi bi-person-badge me-2"></i>Identitas Dasar</h6>
                            <div class="row g-3">
                                @if(Auth::user()->isAdmin())
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Satuan Kerja <span class="text-danger">*</span></label>
                                    <select name="satuan_kerja_id" class="form-select py-2" required>
                                        <option value="" selected disabled>Pilih...</option>
                                        @foreach($satuanKerjas as $s) <option value="{{ $s->id }}">{{ $s->satuan_kerja }}</option> @endforeach
                                    </select>
                                </div>
                                @endif
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Nama Pasien / Inisial <span class="text-danger">*</span></label>
                                    <input type="text" name="nama_pasien" class="form-control py-2" required placeholder="Masukkan nama...">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Jenis Kelamin <span class="text-danger">*</span></label>
                                    <select name="jenis_kelamin" class="form-select py-2" required>
                                        <option value="" selected disabled>Pilih...</option>
                                        <option value="Laki-laki">Laki-laki</option>
                                        <option value="Perempuan">Perempuan</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DATA KEDATANGAN / REHAB (FULL WIDTH BARIS BARU) --}}
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
                                    <label class="form-label small fw-semibold">Usia <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="usia" class="form-control py-2" required min="1" placeholder="Umur...">
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
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pb-5">
                <button type="reset" class="btn btn-light border px-4 py-2">Reset Form</button>
                <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">Simpan & Generate RM</button>
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