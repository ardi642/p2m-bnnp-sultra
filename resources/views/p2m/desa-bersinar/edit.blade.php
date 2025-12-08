@extends('admin')
@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0">P2M Desa Bersinar</h1>
                            <p class="text-muted mb-0">Edit Data Desa Bersinar</p>
                        </div>
                        {{-- KEMBALI KE INDEX MURNI --}}
                        <a href="{{ route('p2m.desa_bersinar.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>

                    <div class="card shadow-lg p-5">
                        <div class="card-header">
                            <h5 class="card-title mb-2">Edit Data Desa Bersinar</h5>
                        </div>
                        <div class="card-body">
                            {{-- Form --}}
                            <form id="form-edit-desabersinar" action="{{ route('p2m.desa_bersinar.update', $desa->id) }}" method="POST">
                                @csrf @method('PUT')

                                <div class="row g-6 mb-5">
                                    {{-- Anggaran Pembentukan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="anggaran_pembentukan" class="form-label">Anggaran Pembentukan</label>
                                            <select class="form-select @error('anggaran_pembentukan') is-invalid @enderror" name="anggaran_pembentukan">
                                                <option value="DIPA" @selected(old('anggaran_pembentukan', $desa->anggaran_pembentukan) == 'DIPA')>DIPA</option>
                                                <option value="NON DIPA" @selected(old('anggaran_pembentukan', $desa->anggaran_pembentukan) == 'NON DIPA')>NON DIPA</option>
                                            </select>
                                            @error('anggaran_pembentukan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Nama Desa --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="nama_desa" class="form-label">Nama Desa</label>
                                            <input type="text" class="form-control @error('nama_desa') is-invalid @enderror" name="nama_desa" value="{{ old('nama_desa', $desa->nama_desa) }}">
                                            @error('nama_desa')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Nama Kelurahan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="nama_kelurahan" class="form-label">Nama Kelurahan</label>
                                            <input type="text" class="form-control @error('nama_kelurahan') is-invalid @enderror" name="nama_kelurahan" value="{{ old('nama_kelurahan', $desa->nama_kelurahan) }}">
                                            @error('nama_kelurahan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Kabupaten/Kota --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="kabupaten_kota_id" class="form-label">Kabupaten/Kota</label>
                                            <select class="form-select @error('kabupaten_kota_id') is-invalid @enderror" name="kabupaten_kota_id">
                                                @foreach($kabupatenKotas as $k)
                                                    <option value="{{ $k->id }}" @selected(old('kabupaten_kota_id', $desa->kabupaten_kota_id) == $k->id)>
                                                        {{ $k->nama }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('kabupaten_kota_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Tanggal Pencanangan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="tanggal_pencanangan" class="form-label">Tanggal Pencanangan</label>
                                            <input type="date" class="form-control @error('tanggal_pencanangan') is-invalid @enderror" name="tanggal_pencanangan" value="{{ old('tanggal_pencanangan', $desa->tanggal_pencanangan->format('Y-m-d')) }}">
                                            @error('tanggal_pencanangan')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Jumlah Penggiat --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="jumlah_penggiat" class="form-label">Jumlah Penggiat P4GN</label>
                                            <input type="number" class="form-control @error('jumlah_penggiat') is-invalid @enderror" name="jumlah_penggiat" value="{{ old('jumlah_penggiat', $desa->jumlah_penggiat) }}">
                                            @error('jumlah_penggiat')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Keberadaan IBM --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="keberadaan_ibm" class="form-label">Keberadaan IBM</label>
                                            <select class="form-select @error('keberadaan_ibm') is-invalid @enderror" name="keberadaan_ibm">
                                                <option value="ada" @selected(old('keberadaan_ibm', $desa->keberadaan_ibm) == 'ada')>Ada</option>
                                                <option value="belum ada" @selected(old('keberadaan_ibm', $desa->keberadaan_ibm) == 'belum ada')>Belum Ada</option>
                                            </select>
                                            @error('keberadaan_ibm')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Penanggung Jawab --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="select-pegawai" class="form-label">Nama Penanggung Jawab</label>
                                            <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Pilih Pegawai..." autocomplete="off" class="form-control @error('pegawai_nips') is-invalid @enderror">
                                                @foreach ($pegawais as $p)
                                                    <option value="{{ $p->nip }}" @selected(in_array($p->nip, old('pegawai_nips', $selectedPegawaiNips ?? [])))>
                                                        {{ $p->nama }} - NIP: {{ $p->nip }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('pegawai_nips')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- No HP Penanggung Jawab --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="nomor_hp_penanggung_jawab" class="form-label">No HP Penanggung Jawab</label>
                                            <input type="text" class="form-control @error('nomor_hp_penanggung_jawab') is-invalid @enderror" name="nomor_hp_penanggung_jawab" value="{{ old('nomor_hp_penanggung_jawab', $desa->nomor_hp_penanggung_jawab) }}">
                                            @error('nomor_hp_penanggung_jawab')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Link Dokumentasi --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="link_kelengkapan_dokumentasi" class="form-label">Link Kelengkapan & Dokumentasi</label>
                                            <input type="text" class="form-control @error('link_kelengkapan_dokumentasi') is-invalid @enderror" name="link_kelengkapan_dokumentasi" value="{{ old('link_kelengkapan_dokumentasi', $desa->link_kelengkapan_dokumentasi) }}">
                                            @error('link_kelengkapan_dokumentasi')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row justify-content-end">
                                    <div class="col-12 col-lg-auto">
                                        <button type="submit" class="btn btn-success w-100 mb-4 mb-lg-0">Simpan Perubahan</button>
                                    </div>
                                    <div class="col-12 col-lg-auto">
                                        {{-- TOMBOL RESET: Me-reload halaman ini sendiri untuk mengambil data awal --}}
                                        <a href="{{ route('p2m.desa_bersinar.edit', $desa->id) }}" class="btn btn-secondary w-100 mb-4 mb-lg-0">
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

@push('styles')
    @vite('resources/css/tom-select.css')
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof TomSelect !== 'undefined') {
            const tom = new TomSelect("#select-pegawai", {
                create: false,
                sortField: { field: "text", direction: "asc" },
                maxItems: null,
                placeholder: "Cari atau pilih pegawai...",
                plugins: ['remove_button'],
            });
            // Tidak perlu listener 'reset' lagi karena tombol sudah diganti jadi link reload
        }
    });
</script>
@endpush