@extends('admin')
@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0">Kegiatan P2M</h1>
                            <p class="text-muted mb-0">Input Data Kegiatan P2M</p>
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
                                    <h5 class="card-title mb-2" id="judul">Input Data Kegiatan P2M Sosialisasi Tatap Muka/Konvensional</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('p2m.sosialisasi.store') }}" method="POST">
                                @csrf
                                
                                {{-- PERBAIKAN: Gunakan SATU row container untuk semua input --}}
                                {{-- 'g-4' memberikan jarak antar kolom dan baris yang konsisten --}}
                                <div class="row g-6 mb-5">
                                    
                                    {{-- Input 1: Satuan Kerja (Hanya jika admin) --}}
                                    @if (auth()->user()->pegawai == null)    
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

                                    {{-- Input 2: Anggaran --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="anggaran_pelaksanaan" class="form-label">Anggaran Pelaksanaan</label>
                                            <select class="form-select @error('anggaran_pelaksanaan') is-invalid @enderror" name="anggaran_pelaksanaan">
                                                <option value="" disabled selected>pilih anggaran pelaksanaan</option>
                                                <option value="DIPA" @selected(old('anggaran_pelaksanaan') == 'DIPA')>DIPA</option>
                                                <option value="NON DIPA" @selected(old('anggaran_pelaksanaan') == 'NON DIPA')>NON DIPA</option>
                                            </select>
                                            @error('anggaran_pelaksanaan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Input 3: Nama Kegiatan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="nama_kegiatan" class="form-label">Nama Kegiatan</label>
                                            <input type="text" class="form-control @error('nama_kegiatan') is-invalid @enderror" placeholder="masukkan nama kegiatan" name="nama_kegiatan" value="{{ old('nama_kegiatan') }}">
                                            @error('nama_kegiatan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Input 4: Sasaran Kegiatan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="sasaran_kegiatan" class="form-label">Sasaran Kegiatan</label>
                                            <select class="form-select @error('sasaran_kegiatan') is-invalid @enderror" name="sasaran_kegiatan">
                                                <option value="" selected>pilih sasaran kegiatan</option>
                                                <option value="lingkungan pendidikan" @selected(old('sasaran_kegiatan') == 'lingkungan pendidikan')>Lingkungan Pendidikan</option>
                                                <option value="lingkungan kerja" @selected(old('sasaran_kegiatan') == 'lingkungan kerja')>Lingkungan Kerja (Pemerintah / Swasta)</option>
                                                <option value="lingkungan masyarakat" @selected(old('sasaran_kegiatan') == 'lingkungan masyarakat')>Lingkungan Masyarakat</option>
                                            </select>
                                            @error('sasaran_kegiatan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Input 5: Tanggal Pelaksanaan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="tanggal_pelaksanaan" class="form-label">Tanggal Pelaksanaan</label>
                                            <input type="date" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan') }}">
                                            @error('tanggal_pelaksanaan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Input 6: Tempat Kegiatan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="tempat_kegiatan" class="form-label">Tempat Kegiatan</label>
                                            <textarea class="form-control @error('tempat_kegiatan') is-invalid @enderror" rows="1" placeholder="masukkan tempat kegiatan" name="tempat_kegiatan">{{ old('tempat_kegiatan') }}</textarea>
                                            @error('tempat_kegiatan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Input 7: Pegawai (Tom Select) --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="select-pegawai" class="form-label">Nama Pegawai yang ditugaskan</label>
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

                                    {{-- Input 8: Jumlah Peserta --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="jumlah_peserta" class="form-label">Jumlah Peserta</label>
                                            <input type="number" class="form-control @error('jumlah_peserta') is-invalid @enderror" placeholder="masukkan jumlah peserta" name="jumlah_peserta" value="{{ old('jumlah_peserta') }}">
                                            @error('jumlah_peserta')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Input 9: Link Kelengkapan (Full Width / col-12) --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="link_kelengkapan_dokumentasi" class="form-label">Link Kelengkapan & Dokumentasi</label>
                                            <input type="text" class="form-control @error('link_kelengkapan_dokumentasi') is-invalid @enderror" placeholder="masukkan link" name="link_kelengkapan_dokumentasi" value="{{ old('link_kelengkapan_dokumentasi') }}">
                                            @error('link_kelengkapan_dokumentasi')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                </div> 
                                {{-- END ROW CONTAINER --}}

                                <div class="row justify-content-end">
                                    <div class="col-12 col-lg-auto">
                                        <button type="submit" class="btn btn-primary w-100 mb-4 mb-lg-0">tambah data</button>
                                    </div>
                                    <div class="col-12 col-lg-auto">
                                        <button type="reset" class="btn btn-secondary w-100 mb-4 mb-lg-0">reset</button>
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