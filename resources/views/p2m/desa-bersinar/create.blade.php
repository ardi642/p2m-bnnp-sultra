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
                                    <h5 class="card-title mb-3" id="judul">Kegiatan Desa Bersinar</h5>
                                    <p>Input Data Kegiatan Informasi dan Edukasi dengan Membuka Stand Layanan di Lokasi Desa Bersinar</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('p2m.desabersinar.store') }}" method="POST">
                                @csrf

                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Satuan Kerja</label>
                                            <select class="form-select @error('satker') is-invalid @enderror" name="satker">
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
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Anggaran Pembentukan</label>
                                            <select class="form-select @error('anggaran_pembentukan') is-invalid @enderror" name="anggaran_pembentukan">
                                                <option value="" selected>Pilih Anggaran</option>
                                                <option value="DIPA" @selected(old('anggaran_pembentukan') == 'DIPA')>DIPA</option>
                                                <option value="NON DIPA" @selected(old('anggaran_pembentukan') == 'NON DIPA')>NON DIPA</option>
                                            </select>
                                            @error('anggaran_pembentukan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>


                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Desa</label>
                                            <input type="text" class="form-control @error('nama_desa') is-invalid @enderror"
                                                name="nama_desa" value="{{ old('nama_desa') }}" placeholder="Masukkan Nama Desa">
                                            @error('nama_desa')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Kecamatan</label>
                                            <input type="text" class="form-control @error('nama_kecamatan') is-invalid @enderror"
                                                name="nama_kecamatan" value="{{ old('nama_kecamatan') }}" placeholder="Masukkan Nama Kecamatan">
                                            @error('nama_kecamatan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-4">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Kota/Kabupaten</label>
                                            <select class="form-select @error('nama_kota_kabupaten') is-invalid @enderror"
                                                name="nama_kota_kabupaten">
                                                <option value="" selected>Pilih Kota/Kabupaten</option>

                                                <option value="Kota Kendari" @selected(old('nama_kota_kabupaten') == 'Kota Kendari')>Kota Kendari</option>
                                                <option value="Kota Baubau" @selected(old('nama_kota_kabupaten') == 'Kota Baubau')>Kota Baubau</option>
                                                <option value="Kabupaten Konawe" @selected(old('nama_kota_kabupaten') == 'Kabupaten Konawe')>Kabupaten Konawe</option>
                                                <option value="Kabupaten Konawe Selatan" @selected(old('nama_kota_kabupaten') == 'Kabupaten Konawe Selatan')>Kabupaten Konawe Selatan</option>
                                                <option value="Kabupaten Konawe Utara" @selected(old('nama_kota_kabupaten') == 'Kabupaten Konawe Utara')>Kabupaten Konawe Utara</option>
                                                <option value="Kabupaten Kolaka" @selected(old('nama_kota_kabupaten') == 'Kabupaten Kolaka')>Kabupaten Kolaka</option>
                                                <option value="Kabupaten Kolaka Timur" @selected(old('nama_kota_kabupaten') == 'Kabupaten Kolaka Timur')>Kabupaten Kolaka Timur</option>
                                                <option value="Kabupaten Kolaka Utara" @selected(old('nama_kota_kabupaten') == 'Kabupaten Kolaka Utara')>Kabupaten Kolaka Utara</option>
                                                <option value="Kabupaten Bombana" @selected(old('nama_kota_kabupaten') == 'Kabupaten Bombana')>Kabupaten Bombana</option>
                                                <option value="Kabupaten Buton" @selected(old('nama_kota_kabupaten') == 'Kabupaten Buton')>Kabupaten Buton</option>
                                                <option value="Kabupaten Buton Utara" @selected(old('nama_kota_kabupaten') == 'Kabupaten Buton Utara')>Kabupaten Buton Utara</option>
                                                <option value="Kabupaten Buton Selatan" @selected(old('nama_kota_kabupaten') == 'Kabupaten Buton Selatan')>Kabupaten Buton Selatan</option>
                                                <option value="Kabupaten Buton Tengah" @selected(old('nama_kota_kabupaten') == 'Kabupaten Buton Tengah')>Kabupaten Buton Tengah</option>
                                                <option value="Kabupaten Muna" @selected(old('nama_kota_kabupaten') == 'Kabupaten Muna')>Kabupaten Muna</option>
                                                <option value="Kabupaten Muna Barat" @selected(old('nama_kota_kabupaten') == 'Kabupaten Muna Barat')>Kabupaten Muna Barat</option>
                                                <option value="Kabupaten Wakatobi" @selected(old('nama_kota_kabupaten') == 'Kabupaten Wakatobi')>Kabupaten Wakatobi</option>

                                            </select>

                                            @error('nama_kota_kabupaten')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                </div>


                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal Pencanangan/Pengukuhan</label>
                                            <input type="date" class="form-control @error('tanggal_pencanangan') is-invalid @enderror"
                                                name="tanggal_pencanangan" value="{{ old('tanggal_pencanangan') }}">
                                            @error('tanggal_pencanangan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Bulan Pelaksanaan</label>
                                            <input type="month" class="form-control @error('bulan_pelaksanaan') is-invalid @enderror"
                                                name="bulan_pelaksanaan" value="{{ old('bulan_pelaksanaan') }}">
                                            @error('bulan_pelaksanaan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>


                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Jumlah Penggiat P4GN yang Terbentuk</label>
                                            <input type="number" class="form-control @error('jumlah_penggiat') is-invalid @enderror"
                                                name="jumlah_penggiat_p4gn" value="{{ old('jumlah_penggiat') }}" placeholder="Masukkan Jumlah Penggiat">
                                            @error('jumlah_penggiat')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Keberadaan IBM</label>
                                            <select class="form-select @error('keberadaan_ibm') is-invalid @enderror" name="keberadaan_ibm">
                                                <option value="" selected>Pilih Status</option>
                                                <option value="Ada" @selected(old('keberadaan_ibm') == 'Ada')>Ada</option>
                                                <option value="Belum Ada" @selected(old('keberadaan_ibm') == 'Belum Ada')>Belum Ada</option>
                                            </select>
                                            @error('keberadaan_ibm')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>


                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Penanggung Jawab</label>
                                            <input type="text" class="form-control @error('nama_penanggung_jawab') is-invalid @enderror"
                                                name="nama_penanggung_jawab" value="{{ old('nama_penanggung_jawab') }}" placeholder="Masukkan Nama Penanggung Jawab">
                                            @error('nama_penanggung_jawab')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nomor HP Penanggung Jawab</label>
                                            <input type="text" class="form-control @error('nomor_hp_penanggung_jawab') is-invalid @enderror"
                                                name="nomor_hp_penanggung_jawab" value="{{ old('nomor_hp_penanggung_jawab') }}" placeholder="Contoh: 0812xxxxxxx">
                                            @error('nomor_hp_penanggung_jawab')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>


                                <div class="row g-8 mb-5">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label">Link Kelengkapan & Dokumentasi</label>
                                            <input type="text" class="form-control @error('link_kelengkapan_dokumentasi') is-invalid @enderror"
                                                name="link_kelengkapan_dokumentasi" placeholder="Masukkan Link Google Drive atau lainnya"
                                                value="{{ old('link_kelengkapan_dokumentasi') }}">
                                            @error('link_kelengkapan_dokumentasi')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>


                                <div class="row mb-5 justify-content-end">
                                    <div class="col-12 col-lg-auto">
                                        <button type="submit" class="btn btn-primary w-100 mb-4 mb-lg-0">Tambah Data</button>
                                    </div>
                                    <div class="col-12 col-lg-auto">
                                        <button type="reset" class="btn btn-secondary w-100 mb-4 mb-lg-0">Reset</button>
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
