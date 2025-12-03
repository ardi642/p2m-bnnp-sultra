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
                                    <h5 class="card-title mb-2" id="judul">Input Data Kegiatan P2M Desa Bersinar</h5>
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
                                                    <option value="{{ $satuanKerja->id }}" @selected(old('satker') == $satuanKerja->id)>
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
                                                <option value="NON DIPA" @selected(old('anggaran_pembentukan') == 'NON DIPA')>Non DIPA</option>
                                            </select>
                                            @error('anggaran_pembentukan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Desa</label>
                                            <input type="text" class="form-control @error('nama_desa') is-invalid @enderror"
                                                name="nama_desa" placeholder="Masukkan nama desa" value="{{ old('nama_desa') }}">
                                            @error('nama_desa')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Kecamatan</label>
                                            <input type="text" class="form-control @error('nama_kecamatan') is-invalid @enderror"
                                                name="nama_kecamatan" placeholder="Masukkan kecamatan" value="{{ old('nama_kecamatan') }}">
                                            @error('nama_kecamatan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Kabupaten / Kota</label>
                                            <select class="form-select @error('kabupaten_kota') is-invalid @enderror" name="kabupaten_kota">
                                                <option value="" selected>Pilih Kabupaten/Kota</option>
                                                @foreach ([
                                                    'Kabupaten Bombana','Kabupaten Buton','Kabupaten Buton Selatan','Kabupaten Buton Tengah',
                                                    'Kabupaten Buton Utara','Kabupaten Kolaka','Kabupaten Kolaka Timur','Kabupaten Kolaka Utara',
                                                    'Kabupaten Konawe','Kabupaten Konawe Kepulauan','Kabupaten Konawe Selatan','Kabupaten Konawe Utara',
                                                    'Kabupaten Muna','Kabupaten Muna Barat','Kabupaten Wakatobi','Kota Baubau','Kota Kendari'
                                                ] as $kota)
                                                    <option value="{{ $kota }}" @selected(old('kabupaten_kota') == $kota)>{{ $kota }}</option>
                                                @endforeach
                                            </select>
                                            @error('kabupaten_kota')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal Pencanangan</label>
                                            <input type="date" class="form-control @error('tanggal_pencanangan') is-invalid @enderror"
                                                name="tanggal_pencanangan" value="{{ old('tanggal_pencanangan') }}">
                                            @error('tanggal_pencanangan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Jumlah Penggiat P4GN</label>
                                            <input type="number" class="form-control @error('jumlah_penggiat_p4gn') is-invalid @enderror"
                                                name="jumlah_penggiat_p4gn" placeholder="Masukkan jumlah" value="{{ old('jumlah_penggiat_p4gn') }}">
                                            @error('jumlah_penggiat_p4gn')
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
                                                name="nama_penanggung_jawab" placeholder="Masukkan nama" value="{{ old('nama_penanggung_jawab') }}">
                                            @error('nama_penanggung_jawab')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nomor HP Penanggung Jawab</label>
                                            <input type="text" class="form-control @error('nomor_hp_penanggung_jawab') is-invalid @enderror"
                                                name="nomor_hp_penanggung_jawab" placeholder="Masukkan nomor HP" value="{{ old('nomor_hp_penanggung_jawab') }}">
                                            @error('nomor_hp_penanggung_jawab')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Link Kelengkapan Dokumentasi</label>
                                    <input type="text" class="form-control @error('link_kelengkapan_dokumentasi') is-invalid @enderror"
                                        name="link_kelengkapan_dokumentasi" placeholder="Masukkan link dokumentasi"
                                        value="{{ old('link_kelengkapan_dokumentasi') }}">
                                    @error('link_kelengkapan_dokumentasi')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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

@section('scripts')
<script>

    const p2mSelect = document.getElementById("p2m-select")
    const selectedOption = this.options[this.selectedIndex];
    document.getElementById("#judul").inn

</script>
@endsection
