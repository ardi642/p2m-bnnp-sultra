@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0">Edit Tes Urine</h1>
                            <p class="text-muted mb-0">Perbarui Data Deteksi Dini</p>
                        </div>
                        {{-- PERBAIKAN: Tombol Kembali Langsung ke Index Murni --}}
                        <a href="{{ route('p2m.tes_urine.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="card shadow-lg p-5">
                        <div class="card-header">
                            <h5 class="card-title mb-2">Form Edit</h5>
                        </div>
                        <div class="card-body">
                            {{-- Form Edit --}}
                            <form id="form-edit-p2m" action="{{ route('p2m.tes_urine.update', $kegiatan->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                
                                <div class="row g-6 mb-5">
                                    {{-- === BAGIAN 1: DATA UMUM === --}}
                                    
                                    {{-- Admin Only --}}
                                    @if (Auth::user()->isAdmin())    
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Satuan Kerja</label>
                                            <select class="form-select @error('satuan_kerja_id') is-invalid @enderror" name="satuan_kerja_id">
                                                <option value="">Pilih Satuan Kerja</option>
                                                @foreach ($satuanKerjas as $satker)
                                                    <option value="{{ $satker->id }}" @selected(old('satuan_kerja_id', $kegiatan->satuan_kerja_id) == $satker->id)>{{ $satker->satuan_kerja }}</option>
                                                @endforeach
                                            </select>
                                            @error('satuan_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    @endif

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Anggaran</label>
                                            <select class="form-select @error('anggaran_pelaksanaan') is-invalid @enderror" name="anggaran_pelaksanaan">
                                                <option value="DIPA" @selected(old('anggaran_pelaksanaan', $kegiatan->anggaran_pelaksanaan) == 'DIPA')>DIPA</option>
                                                <option value="NON DIPA" @selected(old('anggaran_pelaksanaan', $kegiatan->anggaran_pelaksanaan) == 'NON DIPA')>NON DIPA</option>
                                            </select>
                                            @error('anggaran_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Nama Instansi Pelaksana</label>
                                            <input type="text" class="form-control @error('nama_instansi_pelaksana') is-invalid @enderror" 
                                                   name="nama_instansi_pelaksana" 
                                                   value="{{ old('nama_instansi_pelaksana', $kegiatan->nama_instansi_pelaksana) }}">
                                            @error('nama_instansi_pelaksana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- LAYOUT: Sasaran (Kiri) & Tanggal (Kanan) --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Sasaran Kegiatan</label>
                                            <select class="form-select @error('sasaran_kegiatan') is-invalid @enderror" name="sasaran_kegiatan">
                                                @php $val = old('sasaran_kegiatan', $kegiatan->sasaran_kegiatan); @endphp
                                                <option value="Instansi Pemerintah" @selected($val == 'Instansi Pemerintah')>Instansi Pemerintah</option>
                                                <option value="Lingkungan Pendidikan" @selected($val == 'Lingkungan Pendidikan')>Lingkungan Pendidikan</option>
                                                <option value="Pekerja Swasta" @selected($val == 'Pekerja Swasta')>Pekerja Swasta</option>
                                                <option value="Lingkungan Masyarakat" @selected($val == 'Lingkungan Masyarakat')>Lingkungan Masyarakat</option>
                                            </select>
                                            @error('sasaran_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Tanggal Pelaksanaan</label>
                                            <input type="date" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" 
                                                   name="tanggal_pelaksanaan" 
                                                   value="{{ old('tanggal_pelaksanaan', $kegiatan->tanggal_pelaksanaan->format('Y-m-d')) }}">
                                            @error('tanggal_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- LAYOUT: Tempat (Kiri) & Katim (Kanan) --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Tempat Kegiatan</label>
                                            <textarea class="form-control @error('tempat_kegiatan') is-invalid @enderror" rows="3" name="tempat_kegiatan">{{ old('tempat_kegiatan', $kegiatan->tempat_kegiatan) }}</textarea>
                                            @error('tempat_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Katim / Anggota Tim</label>
                                            <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Pilih Pegawai..." autocomplete="off" class="form-control @error('pegawai_nips') is-invalid @enderror">
                                                @foreach ($pegawais as $pgw)
                                                    <option value="{{ $pgw->nip }}" @selected(in_array($pgw->nip, old('pegawai_nips', $selectedPegawaiNips)))>
                                                        {{ $pgw->nama }} - {{ $pgw->nip }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('pegawai_nips') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- === BAGIAN 2: HASIL TES (Kiri Keterangan, Kanan Jumlah) === --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="h-100 p-3 border rounded bg-light">
                                            <label class="form-label fw-bold">Keterangan parameter terindikasi positif</label>
                                            <textarea class="form-control @error('keterangan_positif') is-invalid @enderror" 
                                                      rows="5" 
                                                      placeholder="Contoh: Inisial AA (THC)..."
                                                      name="keterangan_positif">{{ old('keterangan_positif', $kegiatan->keterangan_positif) }}</textarea>
                                            <div class="form-text text-muted">Kosongkan jika hasil tes negatif semua.</div>
                                            @error('keterangan_positif') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="d-flex flex-column gap-3 h-100">
                                            {{-- Jumlah Peserta --}}
                                            <div>
                                                <label class="form-label fw-bold">Jumlah Peserta</label>
                                                <input type="number" class="form-control @error('jumlah_peserta') is-invalid @enderror" 
                                                       name="jumlah_peserta" 
                                                       value="{{ old('jumlah_peserta', $kegiatan->jumlah_peserta) }}">
                                                @error('jumlah_peserta') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>

                                            {{-- Jumlah Positif (Default ke data DB) --}}
                                            <div>
                                                <label class="form-label fw-bold text-danger">Jumlah Peserta terindikasi positif *</label>
                                                <input type="number" class="form-control @error('jumlah_positif') is-invalid @enderror" 
                                                       name="jumlah_positif" 
                                                       value="{{ old('jumlah_positif', $kegiatan->jumlah_positif) }}">
                                                <div class="form-text text-muted fst-italic">
                                                    Jikalau tidak ada yang terindikasi, di isi dengan angka 0
                                                </div>
                                                @error('jumlah_positif') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    </div>

                                    {{-- === BAGIAN 3: LINK === --}}
                                    <div class="col-12 col-lg-12">
                                        <div class="mb-0">
                                            <label class="form-label">Link Dokumentasi</label>
                                            <input type="text" class="form-control @error('link_kelengkapan_dokumentasi') is-invalid @enderror" 
                                                   name="link_kelengkapan_dokumentasi" 
                                                   value="{{ old('link_kelengkapan_dokumentasi', $kegiatan->link_kelengkapan_dokumentasi) }}">
                                            @error('link_kelengkapan_dokumentasi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div> 

                                <div class="row justify-content-end">
                                    <div class="col-12 col-lg-auto">
                                        <button type="submit" class="btn btn-success w-100 mb-4 mb-lg-0">Simpan Perubahan</button>
                                    </div>
                                    <div class="col-12 col-lg-auto">
                                        {{-- PERBAIKAN: Tombol Reset pakai LINK RELOAD --}}
                                        <a href="{{ route('p2m.tes_urine.edit', $kegiatan->id) }}" class="btn btn-secondary w-100 mb-4 mb-lg-0">
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
            // Init TomSelect seperti biasa (tanpa logic reset manual karena tombol sudah diganti link)
            const tomSelectInstance = new TomSelect("#select-pegawai", {
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