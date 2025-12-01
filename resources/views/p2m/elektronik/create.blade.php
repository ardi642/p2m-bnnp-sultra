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
                                    <h5 class="card-title mb-2" id="judul">Informasi Edukasi melalui Media Elektronik</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('p2m.elektronik.store') }}" method="POST">
                                @csrf
                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label for="exampleFormControlInput1" class="form-label">Satuan Kerja</label>
                                            <select class="form-select @error('satuan_kerja_id') is-invalid @enderror" aria-label="Default select example" name="satuan_kerja_id">
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
                                    <div class="col-12 col-lg-6">
                                         <div class="mb-3">
                                            <label for="exampleFormControlInput1" class="form-label">Anggaran Pelaksanaan</label>
                                            <select class="form-select @error('anggaran_pelaksanaan') is-invalid @enderror" aria-label="Default select example" name="anggaran_pelaksanaan">
                                            <option value="" disabled selected>pilih anggaran pelaksanaan</option>
                                            <option value="DIPA" @selected(old('anggaran_pelaksanaan') == 'DIPA')>DIPA</option>
                                            <option value="NON DIPA" @selected(old('anggaran_pelaksanaan') == 'NON DIPA')>NON DIPA</option>
                                            </select>
                                            @error('anggaran_pelaksanaan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label for="exampleFormControlInput1" class="form-label">Media Yang Digunakan</label>
                                            <select class="form-select @error('media') is-invalid @enderror" aria-label="Default select example" name="media">
                                            <option value="" disabled selected>pilih media yang digunakan</option>
                                            <option value="Televisi" @selected(old('media') == 'Televisi')>Televisi</option>
                                            <option value="Radio" @selected(old('media') == 'Radio')>Radio</option>
                                            <option value="Video Tron" @selected(old('media') == 'Video Tron')>Video Tron</option>
                                            <option value="Bioskop" @selected(old('media') == 'Bioskop')>Bioskop</option>
                                            <option value="Tv Plasma" @selected(old('media') == 'Tv Plasma')>Tv Plasma</option>
                                            <option value="Media Lain" @selected(old('media') == 'Media Lain')>Media Lain</option>
                                            </select>
                                            @error('media')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                    <div class="mb-3">
                                       <label for="exampleFormControlInput1" class="form-label">Masukan Durasi Pelaksanaan</label>
                                        <input type="text" class="form-control @error('durasi_pelaksanaan') is-invalid @enderror" placeholder="masukkan durasi pelaksanaan (hari)" name="durasi_pelaksanaan" value="{{ old('durasi_pelaksanaan') }}">
                                        @error('durasi_pelaksanaan')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    </div>
                                </div>
                               
                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                              <label for="exampleFormControlInput1" class="form-label">Tanggal Pelaksanaan</label>
                                            <input type="date" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" placeholder="masukkan tanggal pelaksanaan" name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan') }}">
                                            @error('tanggal_pelaksanaan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                    <div class="mb-3">
                                    <label for="exampleFormControlInput1" class="form-label">Masukan Nama Media</label>
                                    <input type="text" class="form-control @error('nama_media') is-invalid @enderror" placeholder="masukkan Nama Media" name="nama_media" value="{{ old('nama_media') }}">
                                    @error('nama_media')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    </div>
                                    </div>
                                </div>

                                  <div class="row g-8 mb-5">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="exampleFormControlInput1" class="form-label">Link Kelengkapan & Dokumentasi</label>
                                            <input type="text" class="form-control @error('link_kelengkapan_dokumentasi') is-invalid @enderror" placeholder="masukkan link" name="link_kelengkapan_dokumentasi" value="{{ old('link_kelengkapan_dokumentasi') }}">
                                            @error('link_kelengkapan_dokumentasi')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                             
                                <div class="row mb-5 justify-content-end">
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

@section('scripts')
<script>
    
    const p2mSelect = document.getElementById("p2m-select")
    const selectedOption = this.options[this.selectedIndex];
    document.getElementById("#judul").inn

</script>
@endsection