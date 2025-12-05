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
                                    <h5 class="card-title mb-3" id="judul">Kegiatan Safari Religi</h5>
                                    <p>Input Data Kegiatan Informasi dan Edukasi dengan Membuka Stand Layanan di Lokasi Safari Religi</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('p2m.safarireligi.store') }}" method="POST">
                                @csrf

                                <div class="row g-8 mb-5">
                                    {{-- SATKER --}}
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Satuan Kerja</label>
                                        <select class="form-select @error('satker') is-invalid @enderror"
                                                name="satker">
                                            <option value="" selected>Pilih Satuan Kerja</option>
                                            @foreach ($satuanKerjas as $satuanKerja)
                                                <option value="{{ $satuanKerja->id }}"
                                                    @selected(old('satker') == $satuanKerja->id)>
                                                    {{ $satuanKerja->satuan_kerja }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('satker')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- ANGGARAN PEMBENTUKAN --}}
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Anggaran Pembentukan</label>
                                        <select class="form-select @error('anggaran_pembentukan') is-invalid @enderror"
                                                name="anggaran_pembentukan">
                                            <option value="" selected>Pilih Anggaran Pembentukan</option>
                                            <option value="DIPA" @selected(old('anggaran_pembentukan') == 'DIPA')>DIPA</option>
                                            <option value="NON DIPA" @selected(old('anggaran_pembentukan') == 'NON DIPA')>NON DIPA</option>
                                        </select>
                                        @error('anggaran_pembentukan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>


                                {{-- DESA, KECAMATAN, KOTA --}}
                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-4">
                                        <label class="form-label">Nama Desa</label>
                                        <input type="text" class="form-control @error('nama_desa') is-invalid @enderror"
                                            name="nama_desa" placeholder="Masukkan Nama Desa"
                                            value="{{ old('nama_desa') }}">
                                        @error('nama_desa')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-lg-4">
                                        <label class="form-label">Nama Kecamatan</label>
                                        <input type="text" class="form-control @error('nama_kecamatan') is-invalid @enderror"
                                            name="nama_kecamatan" placeholder="Masukkan Nama Kecamatan"
                                            value="{{ old('nama_kecamatan') }}">
                                        @error('nama_kecamatan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-lg-4">
                                    <label class="form-label">Nama Kota/Kabupaten</label>
                                    <select class="form-control @error('nama_kota_kabupaten') is-invalid @enderror"
                                        name="nama_kota_kabupaten">
                                        <option value="" disabled selected>Pilih Kota/Kabupaten</option>

                                        <option value="Kota Kendari" {{ old('nama_kota_kabupaten') == 'Kota Kendari' ? 'selected' : '' }}>Kota Kendari</option>
                                        <option value="Kota Baubau" {{ old('nama_kota_kabupaten') == 'Kota Baubau' ? 'selected' : '' }}>Kota Baubau</option>

                                        <option value="Kabupaten Konawe" {{ old('nama_kota_kabupaten') == 'Kabupaten Konawe' ? 'selected' : '' }}>Kabupaten Konawe</option>
                                        <option value="Kabupaten Konawe Selatan" {{ old('nama_kota_kabupaten') == 'Kabupaten Konawe Selatan' ? 'selected' : '' }}>Kabupaten Konawe Selatan</option>
                                        <option value="Kabupaten Konawe Utara" {{ old('nama_kota_kabupaten') == 'Kabupaten Konawe Utara' ? 'selected' : '' }}>Kabupaten Konawe Utara</option>

                                        <option value="Kabupaten Kolaka" {{ old('nama_kota_kabupaten') == 'Kabupaten Kolaka' ? 'selected' : '' }}>Kabupaten Kolaka</option>
                                        <option value="Kabupaten Kolaka Timur" {{ old('nama_kota_kabupaten') == 'Kabupaten Kolaka Timur' ? 'selected' : '' }}>Kabupaten Kolaka Timur</option>
                                        <option value="Kabupaten Kolaka Utara" {{ old('nama_kota_kabupaten') == 'Kabupaten Kolaka Utara' ? 'selected' : '' }}>Kabupaten Kolaka Utara</option>

                                        <option value="Kabupaten Muna" {{ old('nama_kota_kabupaten') == 'Kabupaten Muna' ? 'selected' : '' }}>Kabupaten Muna</option>
                                        <option value="Kabupaten Muna Barat" {{ old('nama_kota_kabupaten') == 'Kabupaten Muna Barat' ? 'selected' : '' }}>Kabupaten Muna Barat</option>

                                        <option value="Kabupaten Buton" {{ old('nama_kota_kabupaten') == 'Kabupaten Buton' ? 'selected' : '' }}>Kabupaten Buton</option>
                                        <option value="Kabupaten Buton Selatan" {{ old('nama_kota_kabupaten') == 'Kabupaten Buton Selatan' ? 'selected' : '' }}>Kabupaten Buton Selatan</option>
                                        <option value="Kabupaten Buton Tengah" {{ old('nama_kota_kabupaten') == 'Kabupaten Buton Tengah' ? 'selected' : '' }}>Kabupaten Buton Tengah</option>
                                        <option value="Kabupaten Buton Utara" {{ old('nama_kota_kabupaten') == 'Kabupaten Buton Utara' ? 'selected' : '' }}>Kabupaten Buton Utara</option>

                                        <option value="Kabupaten Bombana" {{ old('nama_kota_kabupaten') == 'Kabupaten Bombana' ? 'selected' : '' }}>Kabupaten Bombana</option>
                                        <option value="Kabupaten Wakatobi" {{ old('nama_kota_kabupaten') == 'Kabupaten Wakatobi' ? 'selected' : '' }}>Kabupaten Wakatobi</option>
                                    </select>

                                    @error('nama_kota_kabupaten')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                </div>

                                {{-- TANGGAL & BULAN --}}
                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Tanggal Pencanangan</label>
                                        <input type="date" class="form-control @error('tanggal_pencanangan') is-invalid @enderror"
                                            name="tanggal_pencanangan" value="{{ old('tanggal_pencanangan') }}">
                                        @error('tanggal_pencanangan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Bulan Pelaksanaan</label>
                                        <input type="month" class="form-control @error('bulan_pelaksanaan') is-invalid @enderror"
                                            name="bulan_pelaksanaan" value="{{ old('bulan_pelaksanaan') }}">
                                        @error('bulan_pelaksanaan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- JUMLAH PENGGIAT & IBM --}}
                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Jumlah Penggiat P4GN</label>
                                        <input type="number" class="form-control @error('jumlah_penggiat_p4gn') is-invalid @enderror"
                                            name="jumlah_penggiat_p4gn" placeholder="Masukkan jumlah penggiat"
                                            value="{{ old('jumlah_penggiat_p4gn') }}">
                                        @error('jumlah_penggiat_p4gn')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Keberadaan IBM</label>
                                        <select class="form-select @error('keberadaan_ibm') is-invalid @enderror"
                                                name="keberadaan_ibm">
                                            <option value="" selected>Pilih Status IBM</option>
                                            <option value="Ada" @selected(old('keberadaan_ibm') == 'Ada')>Ada</option>
                                            <option value="Belum Ada" @selected(old('keberadaan_ibm') == 'Belum Ada')>Belum Ada</option>
                                        </select>
                                        @error('keberadaan_ibm')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- PENANGGUNG JAWAB --}}
                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Nama Penanggung Jawab</label>
                                        <input type="text" class="form-control @error('nama_penanggung_jawab') is-invalid @enderror"
                                            name="nama_penanggung_jawab" placeholder="Masukkan Nama Penanggung Jawab"
                                            value="{{ old('nama_penanggung_jawab') }}">
                                        @error('nama_penanggung_jawab')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Nomor HP Penanggung Jawab</label>
                                        <input type="text" class="form-control @error('nomor_hp_penanggung_jawab') is-invalid @enderror"
                                            name="nomor_hp_penanggung_jawab" placeholder="Contoh: 081234567890"
                                            value="{{ old('nomor_hp_penanggung_jawab') }}">
                                        @error('nomor_hp_penanggung_jawab')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- LINK --}}
                                <div class="row g-8 mb-5">
                                    <div class="col-12">
                                        <label class="form-label">Link Kelengkapan Dokumentasi</label>
                                        <input type="text" class="form-control @error('link_kelengkapan_dokumentasi') is-invalid @enderror"
                                            name="link_kelengkapan_dokumentasi"
                                            placeholder="Masukkan link Google Drive atau tautan lainnya"
                                            value="{{ old('link_kelengkapan_dokumentasi') }}">
                                        @error('link_kelengkapan_dokumentasi')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- BUTTON --}}
                                <div class="row mb-5 justify-content-end">
                                    <div class="col-12 col-lg-auto">
                                        <button type="submit" class="btn btn-primary w-100 mb-4 mb-lg-0">Tambah Data</button>
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
