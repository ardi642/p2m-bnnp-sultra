@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0">Kegiatan P2M</h1>
                            <p class="text-muted mb-0">Edit Data Kegiatan</p>
                        </div>
                        
                        {{-- Tombol Kembali langsung ke Route Index --}}
                        <a href="{{ route('p2m.sosialisasi.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>

                    <div class="card shadow-lg p-5">
                        <div class="card-header">
                            <h5 class="card-title mb-2">Edit Data Sosialisasi Tatap Muka/Konvensional</h5>
                        </div>
                        <div class="card-body">
                            {{-- Tambahkan ID pada form untuk selector JS --}}
                            <form id="form-edit-p2m" action="{{ route('p2m.sosialisasi.update', $kegiatan->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                
                                <div class="row g-6 mb-5">
                                    
                                    {{-- Input 1: Satuan Kerja (Hanya jika admin) --}}
                                    @if (Auth::user()->isAdmin())     
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="satuan_kerja_id" class="form-label">Satuan Kerja</label>
                                            <select class="form-select @error('satuan_kerja_id') is-invalid @enderror" name="satuan_kerja_id">
                                                <option value="">pilih satuan kerja</option>
                                                @foreach ($satuanKerjas as $satuanKerja)
                                                    <option value="{{ $satuanKerja->id }}" @selected(old('satuan_kerja_id', $kegiatan->satuan_kerja_id) == $satuanKerja->id)>
                                                        {{ $satuanKerja->satuan_kerja }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('satuan_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    @endif

                                    {{-- Input 2: Anggaran --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="anggaran_pelaksanaan" class="form-label">Anggaran Pelaksanaan</label>
                                            <select class="form-select @error('anggaran_pelaksanaan') is-invalid @enderror" name="anggaran_pelaksanaan">
                                                <option value="" disabled>pilih anggaran pelaksanaan</option>
                                                <option value="DIPA" @selected(old('anggaran_pelaksanaan', $kegiatan->anggaran_pelaksanaan) == 'DIPA')>DIPA</option>
                                                <option value="NON DIPA" @selected(old('anggaran_pelaksanaan', $kegiatan->anggaran_pelaksanaan) == 'NON DIPA')>NON DIPA</option>
                                            </select>
                                            @error('anggaran_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- Input 3: Nama Kegiatan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="nama_kegiatan" class="form-label">Nama Kegiatan</label>
                                            <input type="text" class="form-control @error('nama_kegiatan') is-invalid @enderror" name="nama_kegiatan" value="{{ old('nama_kegiatan', $kegiatan->nama_kegiatan) }}">
                                            @error('nama_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- Input 4: Sasaran Kegiatan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="sasaran_kegiatan" class="form-label">Sasaran Kegiatan</label>
                                            <select class="form-select @error('sasaran_kegiatan') is-invalid @enderror" name="sasaran_kegiatan">
                                                @php $valSasaran = old('sasaran_kegiatan', $kegiatan->sasaran_kegiatan); @endphp
                                                <option value="lingkungan pendidikan" @selected($valSasaran == 'lingkungan pendidikan')>Lingkungan Pendidikan</option>
                                                <option value="lingkungan kerja" @selected($valSasaran == 'lingkungan kerja')>Lingkungan Kerja (Pemerintah / Swasta)</option>
                                                <option value="lingkungan masyarakat" @selected($valSasaran == 'lingkungan masyarakat')>Lingkungan Masyarakat</option>
                                            </select>
                                            @error('sasaran_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- Input 5: Tanggal Pelaksanaan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="tanggal_pelaksanaan" class="form-label">Tanggal Pelaksanaan</label>
                                            <input type="date" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan', $kegiatan->tanggal_pelaksanaan->format('Y-m-d')) }}">
                                            @error('tanggal_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- Input 6: Tempat Kegiatan --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="tempat_kegiatan" class="form-label">Tempat Kegiatan</label>
                                            <textarea class="form-control @error('tempat_kegiatan') is-invalid @enderror" rows="3" name="tempat_kegiatan">{{ old('tempat_kegiatan', $kegiatan->tempat_kegiatan) }}</textarea>
                                            @error('tempat_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- Input 7: Pegawai (Tom Select) --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="select-pegawai" class="form-label">Nama Pegawai yang ditugaskan</label>
                                            <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Pilih Pegawai..." autocomplete="off" class="form-control @error('pegawai_nips') is-invalid @enderror">
                                                @foreach ($pegawais as $pegawai_item)
                                                    <option value="{{ $pegawai_item->nip }}" 
                                                        @selected(in_array($pegawai_item->nip, old('pegawai_nips', $selectedPegawaiNips)))>
                                                        {{ $pegawai_item->nama }} - NIP: {{ $pegawai_item->nip }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('pegawai_nips') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- Input 8: Jumlah Peserta --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="jumlah_peserta" class="form-label">Jumlah Peserta</label>
                                            <input type="number" class="form-control @error('jumlah_peserta') is-invalid @enderror" name="jumlah_peserta" value="{{ old('jumlah_peserta', $kegiatan->jumlah_peserta) }}">
                                            @error('jumlah_peserta') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- Input 9: Link Kelengkapan (Full Width / col-12 - Sama seperti create) --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label for="link_kelengkapan_dokumentasi" class="form-label">Link Kelengkapan & Dokumentasi</label>
                                            <input type="text" class="form-control @error('link_kelengkapan_dokumentasi') is-invalid @enderror" name="link_kelengkapan_dokumentasi" value="{{ old('link_kelengkapan_dokumentasi', $kegiatan->link_kelengkapan_dokumentasi) }}">
                                            @error('link_kelengkapan_dokumentasi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                </div> 

                                <div class="row justify-content-end">
                                    <div class="col-12 col-lg-auto">
                                        <button type="submit" class="btn btn-success w-100 mb-4 mb-lg-0">Simpan Perubahan</button>
                                    </div>
                                    <div class="col-12 col-lg-auto">
                                        {{-- Tombol Reset Data Menggunakan Link ke Route Edit (Reload Clean) --}}
                                        <a href="{{ route('p2m.sosialisasi.edit', $kegiatan->id) }}" class="btn btn-secondary w-100 mb-4 mb-lg-0">Reset Data</a>
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
            // Simpan instance Tom Select ke variabel agar bisa diakses
            const tomSelectInstance = new TomSelect("#select-pegawai", {
                create: false,
                sortField: { field: "text", direction: "asc" },
                maxItems: null,
                placeholder: "Cari atau pilih pegawai...",
                plugins: ['remove_button'],
            });
        }
    });
</script>
@endpush