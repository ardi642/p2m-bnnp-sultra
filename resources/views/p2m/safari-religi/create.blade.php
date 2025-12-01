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
                                    <h5 class="card-title mb-2" id="judul">Input Data Kegiatan P2M Safari Religi</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('p2m.safarireligi.store') }}" method="POST">
                                @csrf
                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Satuan Kerja</label>
                                            <select class="form-select @error('satker') is-invalid @enderror" name="satker" aria-label="Pilih satuan kerja">
                                                <option value="" selected>Pilih Satuan Kerja</option>
                                                @foreach ($satuanKerjas as $satuanKerja)
                                                    <option value="{{ $satuanKerja->id }}" @selected(old('satker') == $satuanKerja->id)>
                                                        {{ $satuanKerja->satuan_kerja ?? 'Nama Satuan Kerja' }}
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
                                            <label class="form-label">Bulan Pelaksanaan</label>
                                            <select class="form-select @error('bulan_pelaksanaan') is-invalid @enderror" name="bulan_pelaksanaan">
                                                <option value="" selected>Pilih Bulan</option>
                                                <option value="JANUARI" @selected(old('bulan_pelaksanaan') == 'JANUARI')>Januari</option>
                                                <option value="FEBRUARI" @selected(old('bulan_pelaksanaan') == 'FEBRUARI')>Februari</option>
                                                <option value="MARET" @selected(old('bulan_pelaksanaan') == 'MARET')>Maret</option>
                                                <option value="APRIL" @selected(old('bulan_pelaksanaan') == 'APRIL')>April</option>
                                                <option value="MEI" @selected(old('bulan_pelaksanaan') == 'MEI')>Mei</option>
                                                <option value="JUNI" @selected(old('bulan_pelaksanaan') == 'JUNI')>Juni</option>
                                                <option value="JULI" @selected(old('bulan_pelaksanaan') == 'JULI')>Juli</option>
                                                <option value="AGUSTUS" @selected(old('bulan_pelaksanaan') == 'AGUSTUS')>Agustus</option>
                                                <option value="SEPTEMBER" @selected(old('bulan_pelaksanaan') == 'SEPTEMBER')>September</option>
                                                <option value="OKTOBER" @selected(old('bulan_pelaksanaan') == 'OKTOBER')>Oktober</option>
                                                <option value="NOVEMBER" @selected(old('bulan_pelaksanaan') == 'NOVEMBER')>November</option>
                                                <option value="DESEMBER" @selected(old('bulan_pelaksanaan') == 'DESEMBER')>Desember</option>

                                            </select>
                                            @error('bulan_pelaksanaan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                </div>

                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tanggal Pelaksanaan</label>
                                            <input type="date" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan') }}">
                                            @error('tanggal_pelaksanaan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Tempat Kegiatan</label>
                                            <input
                                                type="text"
                                                class="form-control @error('tempat_kegiatan') is-invalid @enderror"
                                                name="tempat_kegiatan"
                                                placeholder="Masukkan tempat kegiatan"
                                                value="{{ old('tempat_kegiatan') }}"
                                            >

                                            @error('tempat_kegiatan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-8 mb-5">
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Nama Pegawai yang Ditugaskan</label>
                                            <select class="form-select @error('pegawai') is-invalid @enderror" name="pegawai" aria-label="Pilih Pegawai">
                                                <option value="" selected>Pilih Pegawai</option>
                                                @foreach ($pegawais as $pegawai)
                                                    <option value="{{ $pegawai->nip }}" @selected(old('pegawai') == $pegawai->nip)>
                                                        {{ $pegawai->nama ?? 'Nama Pegawai' }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            @error('nama_pegawai')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">Jumlah Masyarakat</label>
                                            <input type="number" class="form-control @error('jumlah_masyarakat') is-invalid @enderror" name="jumlah_masyarakat" placeholder="Masukkan jumlah masyarakat" value="{{ old('jumlah_masyarakat') }}">
                                            @error('jumlah_masyarakat')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Link Dokumentasi</label>
                                    <input type="text" class="form-control @error('link_dokumentasi') is-invalid @enderror" name="link_dokumentasi" placeholder="Masukkan link dokumentasi" value="{{ old('link_dokumentasi') }}">
                                    @error('link_dokumentasi')
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
