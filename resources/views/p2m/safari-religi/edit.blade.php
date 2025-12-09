@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Edit Safari Religi</h1>
                    <p class="text-muted mb-0">Perbarui Data Kegiatan</p>
                </div>
                {{-- Tombol Kembali ke Index --}}
                <a href="{{ route('p2m.safari_religi.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="card shadow-lg p-5">
                        <div class="card-header">
                            <h5 class="card-title mb-2">Form Edit</h5>
                        </div>
                        <div class="card-body">
                            {{-- Form Start --}}
                            <form id="form-edit-p2m" action="{{ route('p2m.safari_religi.update', $kegiatan->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                
                                <div class="row g-6 mb-5">
                                    
                                    {{-- 1. Satuan Kerja (Admin Only) --}}
                                    @if (Auth::user()->isAdmin())    
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Satuan Kerja</label>
                                            <select class="form-select @error('satuan_kerja_id') is-invalid @enderror" name="satuan_kerja_id">
                                                <option value="">Pilih Satuan Kerja</option>
                                                @foreach ($satuanKerjas as $satker)
                                                    <option value="{{ $satker->id }}" @selected(old('satuan_kerja_id', $kegiatan->satuan_kerja_id) == $satker->id)>
                                                        {{ $satker->satuan_kerja }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('satuan_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    @endif

                                    {{-- 2. Tanggal Pelaksanaan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Tanggal Pelaksanaan</label>
                                            <input type="date" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" 
                                                   name="tanggal_pelaksanaan" 
                                                   value="{{ old('tanggal_pelaksanaan', $kegiatan->tanggal_pelaksanaan->format('Y-m-d')) }}">
                                            @error('tanggal_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 3. Tempat Kegiatan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Tempat Kegiatan</label>
                                            <textarea class="form-control @error('tempat_kegiatan') is-invalid @enderror" 
                                                      rows="1" 
                                                      name="tempat_kegiatan">{{ old('tempat_kegiatan', $kegiatan->tempat_kegiatan) }}</textarea>
                                            @error('tempat_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 4. Nama Pegawai (TomSelect) --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Nama Pegawai</label>
                                            <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Pilih Pegawai..." autocomplete="off" class="form-control @error('pegawai_nips') is-invalid @enderror">
                                                @foreach ($pegawais as $pegawai)
                                                    <option value="{{ $pegawai->nip }}" @selected(in_array($pegawai->nip, old('pegawai_nips', $selectedPegawaiNips)))>
                                                        {{ $pegawai->nama }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('pegawai_nips') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 5. Jumlah Masyarakat (Ganti nama kolom disini) --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Jumlah Masyarakat</label>
                                            <input type="number" class="form-control @error('jumlah_masyarakat') is-invalid @enderror" 
                                                   name="jumlah_masyarakat" 
                                                   value="{{ old('jumlah_masyarakat', $kegiatan->jumlah_masyarakat) }}">
                                            @error('jumlah_masyarakat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 6. Link Dokumentasi --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Link Dokumentasi</label>
                                            <input type="text" class="form-control @error('link_kelengkapan_dokumentasi') is-invalid @enderror" 
                                                   name="link_kelengkapan_dokumentasi" 
                                                   value="{{ old('link_kelengkapan_dokumentasi', $kegiatan->link_kelengkapan_dokumentasi) }}">
                                            @error('link_kelengkapan_dokumentasi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div> 

                                {{-- Buttons --}}
                                <div class="row justify-content-end">
                                    <div class="col-12 col-lg-auto">
                                        <button type="submit" class="btn btn-success w-100 mb-4 mb-lg-0">Simpan Perubahan</button>
                                    </div>
                                    <div class="col-12 col-lg-auto">
                                        {{-- Reset menggunakan Link Reload --}}
                                        <a href="{{ route('p2m.safari_religi.edit', $kegiatan->id) }}" class="btn btn-secondary w-100 mb-4 mb-lg-0">
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
        if(typeof TomSelect !== 'undefined'){
            new TomSelect("#select-pegawai", {
                create: false,
                sortField: { field: "text", direction: "asc" },
                maxItems: null,
                placeholder: "Cari pegawai...",
                plugins: ['remove_button'],
            });
        }
    });
</script>
@endpush