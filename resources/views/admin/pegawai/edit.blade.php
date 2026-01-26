@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4">
        
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Edit Pegawai</h1>
                <p class="text-muted mb-0">Perubahan data: <strong>{{ $pegawai->nama }}</strong></p>
            </div>
            <a href="{{ route('admin.pegawai.index') }}" class="btn btn-outline-secondary px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        {{-- === BAGIAN PERBAIKAN: FLASH MESSAGES === --}}
        {{-- Menampilkan pesan sukses --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Menampilkan pesan error (Termasuk jika Transaction Gagal) --}}
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>Gagal!</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        {{-- === BATAS BAGIAN PERBAIKAN === --}}

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                
                <form action="{{ route('admin.pegawai.update', $pegawai->nip) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- === BAGIAN 1: IDENTITAS === --}}
                    <div class="mb-5">
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                            <i class="bi bi-person-lines-fill text-primary me-2"></i>1. Identitas Pegawai
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">NIP (Nomor Induk) <span class="text-danger">*</span></label>
                                
                                {{-- Alert Info Standard --}}
                                <div class="alert alert-info py-2 px-3 mb-2 small" role="alert">
                                    <i class="bi bi-info-circle me-1"></i> Mengubah NIP akan memperbarui seluruh riwayat data terkait.
                                </div>

                                <input type="text" class="form-control @error('nip') is-invalid @enderror" 
                                       name="nip" value="{{ old('nip', $pegawai->nip) }}" required>
                                @error('nip') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                                {{-- Spacer 50px untuk mensejajarkan dengan input NIP yang ada alert --}}
                                <div class="d-none d-md-block" style="height: 50px;"></div>
                                <input type="text" class="form-control @error('nama') is-invalid @enderror" 
                                       name="nama" value="{{ old('nama', $pegawai->nama) }}" required>
                                @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- === BAGIAN 2: KONTAK === --}}
                    <div class="mb-5">
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                            <i class="bi bi-chat-dots text-primary me-2"></i>2. Kontak & Komunikasi
                        </h5>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Alamat Email <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           name="email" value="{{ old('email', $pegawai->email) }}" required>
                                </div>
                                @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Nomor WhatsApp / HP <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-whatsapp"></i></span>
                                    <input type="text" class="form-control @error('nomor_hp') is-invalid @enderror" 
                                           name="nomor_hp" value="{{ old('nomor_hp', $pegawai->nomor_hp) }}" required>
                                </div>
                                @error('nomor_hp') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- === BAGIAN 3: PENEMPATAN === --}}
                    <div class="mb-5">
                        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                            <i class="bi bi-building-gear text-primary me-2"></i>3. Lokasi Penempatan
                        </h5>

                        <div class="row">
                            <div class="col-12">
                                <label class="form-label fw-bold mb-2">Satuan Kerja Saat Ini</label>
                                
                                @if(auth()->user()->role === 'admin')
                                    <select class="form-select @error('satuan_kerja_id') is-invalid @enderror" 
                                            name="satuan_kerja_id" required>
                                        <option value="" disabled>-- Pilih Satuan Kerja --</option>
                                        @foreach($satuanKerjas as $satker)
                                            <option value="{{ $satker->id }}" 
                                                {{ old('satuan_kerja_id', $pegawai->satuan_kerja_id) == $satker->id ? 'selected' : '' }}>
                                                {{ $satker->satuan_kerja }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('satuan_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror

                                @else
                                    <div class="d-flex align-items-center p-3 border rounded bg-light">
                                        <div class="me-3 text-secondary">
                                            <i class="bi bi-shield-lock-fill fs-4"></i>
                                        </div>
                                        <div>
                                            <div class="small text-muted fw-bold text-uppercase">Lokasi Terkunci</div>
                                            <div class="fw-bold text-dark fs-6">{{ $pegawai->satuanKerja->satuan_kerja ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="form-text mt-1">
                                        <i class="bi bi-info-circle me-1"></i> Anda tidak memiliki akses untuk memindahkan pegawai ini.
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
                        <button type="submit" class="btn btn-warning px-4 text-dark">
                            <i class="bi bi-check2-circle me-1"></i> Update Data
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</main>
@endsection