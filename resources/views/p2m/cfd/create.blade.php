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
                                    <h5 class="card-title mb-3" id="judul">Kegiatan Informasi dan Edukasi di lokasi CFD</h5>
                                    <p>Input Data Kegiatan Informasi Dan Edukasi Dengan Membuka Stand Layanan Di Lokasi CFD (Car Free Day) Atau Lokasi Keramaian Lainnya (Pasar Tumpah, Expo, Pasar Malam Dll)</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('p2m.cfd.store') }}" method="POST">
                                @csrf
                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label for="exampleFormControlInput1" class="form-label">Satuan Kerja</label>
                                            <select class="form-select @error('satuan_kerja_id') is-invalid @enderror" aria-label="Default select example" name="satuan_kerja_id">
                                                <option value="" selected>Pilih Satuan Kerja</option>
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
                                             <label for="exampleFormControlInput1" class="form-label">Tanggal Pelaksanaan</label>
                                            <input type="date" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" placeholder="Masukkan Tanggal Pelaksanaan" name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan') }}">
                                            @error('tanggal_pelaksanaan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                           <label for="exampleFormControlInput1" class="form-label">Nama Pegawai yang ditugaskan</label>
                                            <input type="text" class="form-control @error('nama_pegawai') is-invalid @enderror" placeholder="Masukan Nama Pegawai" name="nama_pegawai" value="{{ old('nama_pegawai') }}">
                                            @error('nama_pegawai')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label for="exampleFormControlInput1" class="form-label">Jumlah Peserta</label>
                                            <input type="number" class="form-control @error('jumlah_peserta') is-invalid @enderror" placeholder="Masukan Jumlah Peserta" name="jumlah_peserta" value="{{ old('jumlah_peserta') }}">
                                            @error('jumlah_peserta')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror



                                        </div>
                                    </div>
                                </div>
                               
                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label for="exampleFormControlInput1" class="form-label">Tempat Kegiatan</label>
                                            <textarea class="form-control @error('tempat_kegiatan') is-invalid @enderror" rows="3" placeholder="Masukkan Tempat Kegiatan" name="tempat_kegiatan">{{ old('tempat_kegiatan') }}</textarea>
                                            @error('tempat_kegiatan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label for="exampleFormControlInput1" class="form-label">Link Kelengkapan & Dokumentasi</label>
                                            <input type="text" class="form-control @error('link_kelengkapan_dokumentasi') is-invalid @enderror" placeholder="Masukkan Link" name="link_kelengkapan_dokumentasi" value="{{ old('link_kelengkapan_dokumentasi') }}">
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