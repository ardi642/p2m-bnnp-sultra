@extends('admin')
@section('content')
    <!-- Main Content -->
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0">P2M Lingkungan Bersinar</h1>
                            <p class="text-muted mb-0">Input Data Lingkungan Bersinar</p>
                        </div>
                    </div>
                </div>
            </div>

            @include('p2m.partials.select-p2m-create')

            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="card shadow-lg p-5">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="card-title mb-2 text-center" id="judul">Input Data Lingkungan Bersinar
                                        yang telah Terbentuk</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('p2m.lingkungan.store') }}" method="POST">
                                @csrf

                                <div class="row g-6 mb-5">
                                    @if ($pegawai == null)    
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="satuan_kerja_id" class="form-label">Satuan Kerja</label>
                                            <select class="form-select @error('satuan_kerja_id') is-invalid @enderror" name="satuan_kerja_id">
                                                <option value="" selected>pilih satuan kerja</option>
                                                @foreach ($satuanKerjas as $satuanKerja)
                                                    <option value="{{ $satuanKerja->id }}" @selected(old('satuan_kerja_id') == $satuanKerja->id)>
                                                    {{ $satuanKerja->satuan_kerja }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('satuan_kerja_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    @endif

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="sasaran" class="form-label">Sasaran</label>
                                            <select class="form-select @error('sasaran') is-invalid @enderror"
                                                aria-label="Default select example" name="sasaran">
                                                <option value="" selected>pilih sasaran</option>
                                                <option value="Sekolah/Kampus Bersinar" @selected(old('sasaran') == 'Sekolah/Kampus Bersinar')>
                                                    Sekolah/Kampus Bersinar</option>
                                                <option value="Pondok Pesantren Bersinar" @selected(old('sasaran') == 'Pondok Pesantren Bersinar')>
                                                    Pondok Pesantren Bersinar</option>
                                                <option value="Tempat Hiburan Bersinar" @selected(old('sasaran') == 'Tempat Hiburan Bersinar')>Tempat
                                                    Hiburan Bersinar</option>
                                                <option value="Tempat Wisata Bersinar" @selected(old('sasaran') == 'Tempat Wisata Bersinar')>Tempat
                                                    Wisata Bersinar</option>
                                                <option value="Industri Bersinar" @selected(old('sasaran') == 'Industri Bersinar')>Industri
                                                    Bersinar</option>
                                            </select>
                                            @error('sasaran')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="nama_tempat" class="form-label">Nama
                                                Tempat/Wilayah/Instansi</label>
                                            <textarea class="form-control @error('nama_tempat') is-invalid @enderror" rows="2"
                                                placeholder="masukkan nama tempat" name="nama_tempat" value="{{ old('nama_tempat') }}"></textarea>
                                            @error('nama_tempat')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="tanggal_pelaksanaan" class="form-label">Tanggal
                                                Pencanangan/Pengukuhan</label>
                                            <input type="date"
                                                class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror"
                                                placeholder="masukkan tanggal pencanangan" name="tanggal_pelaksanaan"
                                                value="{{ old('tanggal_pelaksanaan') }}">
                                            @error('tanggal_pelaksanaan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="jumlah_penggiat" class="form-label">Jumlah Penggiat P4GN
                                                yang Terbentuk</label>
                                            <input type="number"
                                                class="form-control @error('jumlah_penggiat') is-invalid @enderror"
                                                placeholder="masukkan jumlah penggiat" name="jumlah_penggiat"
                                                value="{{ old('jumlah_penggiat') }}">
                                            @error('jumlah_penggiat')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="select-pegawai" class="form-label">Nama Penanggung Jawab Wilayah Bersinar</label>
                                            <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Pilih Pegawai..." autocomplete="off" class="form-control @error('pegawai_nips') is-invalid @enderror">
                                                <option value="">Pilih pegawai...</option>
                                                @foreach ($pegawais as $pegawai_item)
                                                    <option value="{{ $pegawai_item->nip }}" 
                                                        @selected(collect(old('pegawai_nips'))->contains($pegawai_item->nip))>
                                                        {{ $pegawai_item->nama }} - NIP: {{ $pegawai_item->nip }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('pegawai_nips') 
                                                <div class="invalid-feedback d-block">{{ $message }}</div> 
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label for="exampleFormControlInput1" class="form-label">Nomor HP Penanggung
                                                Jawab Wilayah Bersinar</label>
                                            <input type="text"
                                                class="form-control @error('nomor_hp') is-invalid @enderror"
                                                placeholder="masukkan nomor hp penanggung jawab" name="nomor_hp"
                                                value="{{ old('nomor_hp') }}">
                                            @error('nomor_hp')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-12">
                                        <div class="mb-0">
                                            <label for="link_kelengkapan_dokumentasi" class="form-label">Link Kelengkapan & Dokumentasi</label>
                                            <input type="text" class="form-control @error('link_kelengkapan_dokumentasi') is-invalid @enderror" placeholder="masukkan link" name="link_kelengkapan_dokumentasi" value="{{ old('link_kelengkapan_dokumentasi') }}">
                                            @error('link_kelengkapan_dokumentasi')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                </div>
                                <div class="row justify-content-end">
                                    <div class="col-12 col-lg-auto">
                                        <button type="submit" class="btn btn-primary w-100 mb-4 mb-lg-0">Tambah
                                            Data</button>
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
            if (typeof TomSelect !== 'undefined') {
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
