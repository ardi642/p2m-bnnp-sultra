@extends('admin')
@section('content')
    <!-- Main Content -->
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Kegiatan P2M</h1>
                    <p class="text-muted mb-0">Input Data Kegiatan P2M</p>
                </div>
            </div>
            @include('p2m.partials.select-p2m-create')

            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="card shadow-lg p-5">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="card-title mb-3" id="judul">Kegiatan Tes Urine</h5>
                                    <p>Input data pelaksanaan kegiatan tes urine berdasarkan informasi pelaksanaan di lapangan.</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('p2m.tesurine.store') }}" method="POST">
                                @csrf

                                <div class="row g-4 mb-4">

                                    <!-- SATKER -->
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Satuan Kerja</label>
                                        <select class="form-select @error('satker_id') is-invalid @enderror" name="satker_id">
                                            <option value="" selected>Pilih Satuan Kerja</option>
                                            @foreach ($satuanKerjas as $satuanKerja)
                                                <option value="{{ $satuanKerja->id }}" @selected(old('satker_id') == $satuanKerja->id)>
                                                    {{ $satuanKerja->satuan_kerja }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('satker_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- ANGGARAN -->
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Anggaran Pelaksanaan</label>
                                        <select class="form-select @error('anggaran_pelaksanaan') is-invalid @enderror" name="anggaran_pelaksanaan">
                                            <option value="" selected>Pilih Anggaran</option>
                                            <option value="DIPA" @selected(old('anggaran_pelaksanaan') == 'DIPA')>DIPA</option>
                                            <option value="NON DIPA" @selected(old('anggaran_pelaksanaan') == 'NON DIPA')>NON DIPA</option>
                                        </select>
                                        @error('anggaran_pelaksanaan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                </div>


                                <div class="row g-4 mb-4">

                                    <!-- SASARAN KEGIATAN -->
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Sasaran Kegiatan</label>
                                        <select class="form-select @error('sasaran_kegiatan') is-invalid @enderror" name="sasaran_kegiatan">
                                            <option value="" selected>Pilih Sasaran</option>
                                            <option value="Instansi Pemerintah" @selected(old('sasaran_kegiatan') == 'Instansi Pemerintah')>
                                                Instansi Pemerintah
                                            </option>
                                            <option value="Lingkungan Pendidikan" @selected(old('sasaran_kegiatan') == 'Lingkungan Pendidikan')>
                                                Lingkungan Pendidikan
                                            </option>
                                            <option value="Pekerja Swasta" @selected(old('sasaran_kegiatan') == 'Pekerja Swasta')>
                                                Pekerja Swasta
                                            </option>
                                            <option value="Lingkungan Masyarakat" @selected(old('sasaran_kegiatan') == 'Lingkungan Masyarakat')>
                                                Lingkungan Masyarakat
                                            </option>
                                        </select>
                                        @error('sasaran_kegiatan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- NAMA INSTANSI -->
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Nama Instansi Pelaksana</label>
                                        <input type="text" class="form-control @error('nama_instansi_pelaksana') is-invalid @enderror"
                                            name="nama_instansi_pelaksana" placeholder="Masukkan Nama Instansi"
                                            value="{{ old('nama_instansi_pelaksana') }}">
                                        @error('nama_instansi_pelaksana')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                </div>


                                <div class="row g-4 mb-4">

                                    <!-- TANGGAL PELAKSANAAN -->
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Tanggal Pelaksanaan</label>
                                        <input type="date" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror"
                                            name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan') }}">
                                        @error('tanggal_pelaksanaan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- NAMA KATIM -->
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Nama Katim</label>
                                        <input type="text" class="form-control @error('nama_katim') is-invalid @enderror"
                                            name="nama_katim" placeholder="Masukkan Nama Katim"
                                            value="{{ old('nama_katim') }}">
                                        @error('nama_katim')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                </div>


                                <div class="row g-4 mb-4">

                                    <!-- LINK DOKUMENTASI -->
                                    <div class="col-12">
                                        <label class="form-label">Link Kelengkapan & Dokumentasi</label>
                                        <input type="text" class="form-control @error('link_kelengkapan_dokumentasi') is-invalid @enderror"
                                            name="link_kelengkapan_dokumentasi" placeholder="Masukkan Link Google Drive"
                                            value="{{ old('link_kelengkapan_dokumentasi') }}">
                                        @error('link_kelengkapan_dokumentasi')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                </div>


                                <div class="row g-4 mb-4">

                                    <!-- JUMLAH PESERTA -->
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Jumlah Peserta Test Urine</label>
                                        <input type="number" min="0" class="form-control @error('jumlah_peserta_test_urin') is-invalid @enderror"
                                            name="jumlah_peserta_test_urin" placeholder="Masukkan Jumlah Peserta"
                                            value="{{ old('jumlah_peserta_test_urin') }}">
                                        @error('jumlah_peserta_test_urin')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- TERINDIKASI POSITIF -->
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Jumlah Terindikasi Positif</label>
                                        <input type="number" min="0" class="form-control @error('jumlah_terindikasi_positif') is-invalid @enderror"
                                            name="jumlah_terindikasi_positif" placeholder="Masukkan Angka (0 jika tidak ada)"
                                            value="{{ old('jumlah_terindikasi_positif') }}">
                                        @error('jumlah_terindikasi_positif')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                </div>


                                <div class="row g-4 mb-4">

                                    <!-- KETERANGAN POSITIF -->
                                    <div class="col-12">
                                        <label class="form-label">Keterangan Parameter Terindikasi Positif (Optional)</label>
                                        <textarea class="form-control @error('keterangan_parameter_positif') is-invalid @enderror"
                                            name="keterangan_parameter_positif" rows="3"
                                            placeholder="Contoh: Amphetamine (+), Benzodiazepine (+), dll">{{ old('keterangan_parameter_positif') }}</textarea>
                                        @error('keterangan_parameter_positif')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                </div>


                                <!-- BUTTON -->
                                <div class="row mb-5 justify-content-end">
                                    <div class="col-12 col-lg-auto">
                                        <button type="submit" class="btn btn-primary w-100 mb-3 mb-lg-0">Tambah Data</button>
                                    </div>
                                    <div class="col-12 col-lg-auto">
                                        <button type="reset" class="btn btn-secondary w-100">Reset</button>
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
{{-- CSS Tom Select (Theme Bootstrap 5) --}}
    @vite('resources/css/tom-select.css')
@endpush

@push('scripts')
{{-- Script Tom Select --}}
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        // Pastikan library Tom Select sudah di-load di layout utama (admin.blade.php)
        if(typeof TomSelect !== 'undefined'){
            console.log("test dulu berjalan")
            new TomSelect("#select-pegawai", {
                create: false, // User tidak boleh buat nama baru (harus pilih dari list)
                sortField: {
                    field: "text",
                    direction: "asc"
                },
                maxItems: null, // <--- MENAMBAHKAN INI AGAR SELECT BISA TANPA BATAS
                placeholder: "Cari atau pilih pegawai...",
                plugins: ['remove_button'], // Tombol 'x' untuk menghapus pilihan
            });
        } else {
            console.error("Library Tom Select belum terinstall/terload");
        }
    });
</script>
@endpush
