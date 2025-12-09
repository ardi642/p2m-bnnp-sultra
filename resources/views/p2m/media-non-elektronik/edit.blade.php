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
                        <a href="{{ route('p2m.media_non_elektronik.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>

                    <div class="card shadow-lg p-5">
                        <div class="card-header">
                            <h5 class="card-title mb-2">Edit Data Media Non Elektronik</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('p2m.media_non_elektronik.update', $data->id) }}" method="POST">
                                @csrf @method('PUT')
                                <div class="row g-6 mb-5">
                                    @if (Auth::user()->isAdmin())    
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Satuan Kerja</label>
                                            <select id="select-satker" class="form-select @error('satuan_kerja_id') is-invalid @enderror" name="satuan_kerja_id">
                                                @foreach ($satuanKerjas as $satker)
                                                    <option value="{{ $satker->id }}" @selected(old('satuan_kerja_id', $data->satuan_kerja_id) == $satker->id)>{{ $satker->satuan_kerja }}</option>
                                                @endforeach
                                            </select>
                                            @error('satuan_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    @endif

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Anggaran Pelaksanaan</label>
                                            <select class="form-select @error('anggaran_pelaksanaan') is-invalid @enderror" name="anggaran_pelaksanaan">
                                                <option value="DIPA" @selected(old('anggaran_pelaksanaan', $data->anggaran_pelaksanaan) == 'DIPA')>DIPA</option>
                                                <option value="NON DIPA" @selected(old('anggaran_pelaksanaan', $data->anggaran_pelaksanaan) == 'NON DIPA')>NON DIPA</option>
                                            </select>
                                            @error('anggaran_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Media yang Digunakan</label>
                                            <select class="form-select @error('jenis_media') is-invalid @enderror" name="jenis_media">
                                                <option value="Media Cetak" @selected(old('jenis_media', $data->jenis_media) == 'Media Cetak')>Media Cetak (Banner, Brosur, Stiker, dll)</option>
                                                <option value="Media Luar Ruang" @selected(old('jenis_media', $data->jenis_media) == 'Media Luar Ruang')>Media Luar Ruang (Baliho, Spanduk, Umbul-umbul)</option>
                                                <option value="Branding Sarana Publik" @selected(old('jenis_media', $data->jenis_media) == 'Branding Sarana Publik')>Branding Sarana Publik</option>
                                            </select>
                                            @error('jenis_media') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Durasi Pelaksanaan (Hari)</label>
                                            <input type="number" class="form-control @error('durasi_pelaksanaan') is-invalid @enderror" name="durasi_pelaksanaan" value="{{ old('durasi_pelaksanaan', $data->durasi_pelaksanaan) }}">
                                            @error('durasi_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Tanggal Mulai</label>
                                            <input type="date" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan', $data->tanggal_pelaksanaan->format('Y-m-d')) }}">
                                            @error('tanggal_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Tempat Pemasangan</label>
                                            <textarea class="form-control @error('tempat_kegiatan') is-invalid @enderror" rows="3" name="tempat_kegiatan">{{ old('tempat_kegiatan', $data->tempat_kegiatan) }}</textarea>
                                            @error('tempat_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Link Dokumentasi</label>
                                            <input type="text" class="form-control @error('link_kelengkapan_dokumentasi') is-invalid @enderror" name="link_kelengkapan_dokumentasi" value="{{ old('link_kelengkapan_dokumentasi', $data->link_kelengkapan_dokumentasi) }}">
                                            @error('link_kelengkapan_dokumentasi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div> 

                                <div class="row justify-content-end">
                                    <div class="col-12 col-lg-auto">
                                        <button type="submit" class="btn btn-success w-100 mb-4 mb-lg-0">Simpan Perubahan</button>
                                    </div>
                                    <div class="col-12 col-lg-auto">
                                        <a href="{{ route('p2m.media_non_elektronik.edit', $data->id) }}" class="btn btn-secondary w-100 mb-4 mb-lg-0">
                                            Reset Data
                                        </a>
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

@push('styles') @vite('resources/css/tom-select.css') @endpush
@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        if(document.getElementById('select-satker')) new TomSelect("#select-satker");
    });
</script>
@endpush