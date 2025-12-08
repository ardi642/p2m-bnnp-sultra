@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0">Kegiatan Tes Urine</h1>
                            <p class="text-muted mb-0">Input Data Deteksi Dini</p>
                        </div>
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
                            <h5 class="card-title mb-2">Form Input Tes Urine</h5>
                        </div>
                        <div class="card-body">
                            {{-- TAMBAHKAN ID PADA FORM UNTUK JAVASCRIPT RESET --}}
                            <form id="form-create-p2m" action="{{ route('p2m.tes_urine.store') }}" method="POST">
                                @csrf
                                
                                <div class="row g-6 mb-5">
                                    {{-- 1. BAGIAN ATAS (DATA UMUM) --}}
                                    
                                    @if (Auth::user()->isAdmin())    
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Satuan Kerja</label>
                                            <select class="form-select @error('satuan_kerja_id') is-invalid @enderror" name="satuan_kerja_id">
                                                <option value="" selected>Pilih Satuan Kerja</option>
                                                @foreach ($satuanKerjas as $satker)
                                                    <option value="{{ $satker->id }}" @selected(old('satuan_kerja_id') == $satker->id)>{{ $satker->satuan_kerja }}</option>
                                                @endforeach
                                            </select>
                                            @error('satuan_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                    @endif

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Anggaran Pelaksanaan</label>
                                            <select class="form-select @error('anggaran_pelaksanaan') is-invalid @enderror" name="anggaran_pelaksanaan">
                                                <option value="" disabled selected>Pilih Anggaran</option>
                                                <option value="DIPA" @selected(old('anggaran_pelaksanaan') == 'DIPA')>DIPA</option>
                                                <option value="NON DIPA" @selected(old('anggaran_pelaksanaan') == 'NON DIPA')>NON DIPA</option>
                                            </select>
                                            @error('anggaran_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Nama Instansi Pelaksana</label>
                                            <input type="text" class="form-control @error('nama_instansi_pelaksana') is-invalid @enderror" 
                                                   placeholder="Contoh: PT. Maju Jaya / Dinas Pendidikan" name="nama_instansi_pelaksana" value="{{ old('nama_instansi_pelaksana') }}">
                                            @error('nama_instansi_pelaksana') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- PERBAIKAN LAYOUT: KIRI (Sasaran) & KANAN (Tanggal) --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Sasaran Kegiatan</label>
                                            <select class="form-select @error('sasaran_kegiatan') is-invalid @enderror" name="sasaran_kegiatan">
                                                <option value="" selected>Pilih Sasaran</option>
                                                <option value="Instansi Pemerintah" @selected(old('sasaran_kegiatan') == 'Instansi Pemerintah')>Instansi Pemerintah</option>
                                                <option value="Lingkungan Pendidikan" @selected(old('sasaran_kegiatan') == 'Lingkungan Pendidikan')>Lingkungan Pendidikan</option>
                                                <option value="Pekerja Swasta" @selected(old('sasaran_kegiatan') == 'Pekerja Swasta')>Pekerja Swasta</option>
                                                <option value="Lingkungan Masyarakat" @selected(old('sasaran_kegiatan') == 'Lingkungan Masyarakat')>Lingkungan Masyarakat</option>
                                            </select>
                                            @error('sasaran_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- Tanggal Pelaksanaan ubah jadi col-lg-6 agar sejajar --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Tanggal Pelaksanaan</label>
                                            <input type="date" class="form-control @error('tanggal_pelaksanaan') is-invalid @enderror" name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan') }}">
                                            @error('tanggal_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- LAYOUT: KIRI (Tempat) & KANAN (Katim) --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Tempat Kegiatan (Alamat)</label>
                                            <textarea class="form-control @error('tempat_kegiatan') is-invalid @enderror" rows="3" name="tempat_kegiatan">{{ old('tempat_kegiatan') }}</textarea>
                                            @error('tempat_kegiatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="mb-0">
                                            <label class="form-label">Katim / Anggota Tim</label>
                                            <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Pilih Pegawai..." autocomplete="off" class="form-control @error('pegawai_nips') is-invalid @enderror">
                                                @foreach ($pegawais as $pegawai)
                                                    <option value="{{ $pegawai->nip }}" @selected(collect(old('pegawai_nips'))->contains($pegawai->nip))>
                                                        {{ $pegawai->nama }} - NIP {{ $pegawai->nip }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('pegawai_nips') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    {{-- 2. BAGIAN HASIL TES (Kiri Keterangan, Kanan Jumlah) --}}
                                    <div class="col-12 col-lg-6">
                                        <div class="h-100 p-3 border rounded bg-light">
                                            <label class="form-label fw-bold">Keterangan parameter terindikasi positif</label>
                                            <textarea class="form-control @error('keterangan_positif') is-invalid @enderror" 
                                                      rows="5" 
                                                      placeholder="Contoh: Inisial AA (THC), Inisial BB (BZO)..."
                                                      name="keterangan_positif">{{ old('keterangan_positif') }}</textarea>
                                            <div class="form-text text-muted">Kosongkan jika hasil tes negatif semua.</div>
                                            @error('keterangan_positif') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="d-flex flex-column gap-3 h-100">
                                            <div>
                                                <label class="form-label fw-bold">Jumlah Peserta</label>
                                                <input type="number" class="form-control @error('jumlah_peserta') is-invalid @enderror" name="jumlah_peserta" value="{{ old('jumlah_peserta') }}">
                                                @error('jumlah_peserta') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>

                                            <div>
                                                <label class="form-label fw-bold text-danger">Jumlah Peserta terindikasi positif *</label>
                                                <input type="number" class="form-control @error('jumlah_positif') is-invalid @enderror" name="jumlah_positif" value="{{ old('jumlah_positif', 0) }}">
                                                <div class="form-text text-muted fst-italic">
                                                    Jikalau tidak ada yang terindikasi, di isi dengan angka 0
                                                </div>
                                                @error('jumlah_positif') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 3. BAGIAN LINK --}}
                                    <div class="col-12 col-lg-12">
                                        <div class="mb-0">
                                            <label class="form-label">Link Kelengkapan & Dokumentasi</label>
                                            <input type="text" class="form-control @error('link_kelengkapan_dokumentasi') is-invalid @enderror" name="link_kelengkapan_dokumentasi" value="{{ old('link_kelengkapan_dokumentasi') }}">
                                            @error('link_kelengkapan_dokumentasi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        </div>
                                    </div>

                                </div> 

                                <div class="row justify-content-end">
                                    <div class="col-12 col-lg-auto">
                                        <button type="submit" class="btn btn-primary w-100 mb-4 mb-lg-0">Simpan Data</button>
                                    </div>
                                    <div class="col-12 col-lg-auto">
                                        {{-- BUTTON RESET TYPE=RESET --}}
                                        <button type="reset" class="btn btn-secondary w-100">Reset</button>
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
            // 1. Simpan instance TomSelect ke dalam variabel
            const tomSelectInstance = new TomSelect("#select-pegawai", {
                create: false,
                sortField: { field: "text", direction: "asc" },
                maxItems: null,
                placeholder: "Cari pegawai...",
                plugins: ['remove_button'],
            });

            // 2. Ambil elemen Form berdasarkan ID
            const form = document.getElementById('form-create-p2m');

            // 3. Tambahkan Event Listener untuk tombol Reset
            if (form) {
                form.addEventListener('reset', function() {
                    // Gunakan method clear() dari TomSelect untuk menghapus pilihan
                    tomSelectInstance.clear();
                });
            }
        }
    });
</script>
@endpush