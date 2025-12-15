@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4">
        
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Tambah Pegawai</h1>
                <p class="text-muted mb-0">Formulir pendaftaran pegawai baru.</p>
            </div>
            <a href="{{ route('admin.pegawai.index') }}" class="btn btn-outline-secondary px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                
                <form action="{{ route('admin.pegawai.store') }}" method="POST">
                    @csrf

                    {{-- === BAGIAN 1: IDENTITAS === --}}
                    <div class="mb-8">
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                            <i class="bi bi-person-vcard text-primary me-2"></i>1. Identitas Pegawai
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nip" class="form-label fw-bold">NIP (Nomor Induk) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nip') is-invalid @enderror" 
                                       id="nip" name="nip" value="{{ old('nip') }}" 
                                       placeholder="Masukkan NIP..." required>
                                @error('nip') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="nama" class="form-label fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nama') is-invalid @enderror" 
                                       id="nama" name="nama" value="{{ old('nama') }}" 
                                       placeholder="Nama Lengkap & Gelar" required>
                                @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- === BAGIAN 2: KONTAK === --}}
                    <div class="mb-8">
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                            <i class="bi bi-chat-text text-primary me-2"></i>2. Kontak & Komunikasi
                        </h5>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-bold">Alamat Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email') }}" 
                                           placeholder="email@instansi.com" required>
                                </div>
                                @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="nomor_hp" class="form-label fw-bold">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-whatsapp"></i></span>
                                    <input type="text" class="form-control @error('nomor_hp') is-invalid @enderror" 
                                           id="nomor_hp" name="nomor_hp" value="{{ old('nomor_hp') }}" 
                                           placeholder="0812xxxx" required>
                                </div>
                                @error('nomor_hp') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- === BAGIAN 3: PENEMPATAN === --}}
                    <div class="mb-8">
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                            <i class="bi bi-building text-primary me-2"></i>3. Lokasi Penempatan
                        </h5>

                        <div class="row">
                            <div class="col-12">
                                <label class="form-label fw-bold mb-2">Pilih Satuan Kerja <span class="text-danger">*</span></label>
                                
                                @if(auth()->user()->role === 'admin')
                                    {{-- ADMIN UTAMA: Dropdown Standard --}}
                                    <select class="form-select @error('satuan_kerja_id') is-invalid @enderror" 
                                            id="satuan_kerja_id" name="satuan_kerja_id" required>
                                        <option value="" selected disabled>-- Silakan Pilih Satker --</option>
                                        @foreach($satuanKerjas as $satker)
                                            <option value="{{ $satker->id }}" {{ old('satuan_kerja_id') == $satker->id ? 'selected' : '' }}>
                                                {{ $satker->satuan_kerja }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('satuan_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror

                                @else
                                    {{-- ADMIN SATKER: Info Simple & Rapi --}}
                                    <div class="d-flex align-items-center p-3 border rounded bg-light">
                                        <div class="me-3 text-secondary">
                                            <i class="bi bi-lock-fill fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="small text-muted fw-bold text-uppercase">Lokasi Terkunci</div>
                                            <div class="fw-bold text-dark fs-6">
                                                {{ auth()->user()->pegawai->satuanKerja->satuan_kerja ?? 'Satuan Kerja Anda' }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-text mt-1">
                                        <i class="bi bi-info-circle me-1"></i> Sistem otomatis mendaftarkan pegawai baru ke satuan kerja ini.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- TOMBOL AKSI --}}
                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <button type="reset" class="btn btn-secondary px-4">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save me-1"></i> Simpan Data
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</main>
@endsection