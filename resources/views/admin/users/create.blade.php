@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4 p-lg-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Buat Akun User Baru</h1>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="card shadow-lg p-5">
                    <div class="card-body">
                        <form action="{{ route('admin.users.store') }}" method="POST">
                            @csrf

                            <div class="alert alert-info d-flex align-items-center mb-4">
                                <i class="bi bi-info-circle-fill me-2 fs-4"></i>
                                <div>
                                    Password default adalah: <strong>12345678</strong>.
                                    <br><small>Hanya pegawai yang <b>belum memiliki akun</b> yang muncul di daftar.</small>
                                </div>
                            </div>

                            {{-- PILIH PEGAWAI --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold">Pilih Pegawai <span class="text-danger">*</span></label>
                                <select id="select-pegawai" name="pegawai_nip" class="form-control @error('pegawai_nip') is-invalid @enderror" placeholder="Ketik Nama atau NIP..." autocomplete="off">
                                    <option value="">Pilih Pegawai...</option>
                                    @foreach($pegawais as $pgw)
                                        <option value="{{ $pgw->nip }}">
                                            {{ $pgw->nama }} ({{ $pgw->nip }}) - {{ $pgw->satuanKerja->satuan_kerja ?? 'Tanpa Satker' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('pegawai_nip') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            {{-- PILIH ROLE --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold">Role Akses <span class="text-danger">*</span></label>
                                <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                                    <option value="" disabled selected>Pilih Role...</option>
                                    
                                    {{-- Admin Pusat Boleh Buat Admin Satker --}}
                                    @if(auth()->user()->role === 'admin')
                                        <option value="admin_satker">Admin Satker (Kepala/Admin Lokal)</option>
                                    @endif
                                    
                                    {{-- Semua Boleh Buat Operator --}}
                                    <option value="operator">Operator (Penginput Data)</option>
                                </select>
                                @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Simpan & Buat Akun</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@push('styles') @vite('resources/css/tom-select.css') @endpush
@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        new TomSelect('#select-pegawai', {
            create: false,
            sortField: { field: "text", direction: "asc" },
            placeholder: "Cari Pegawai...",
        });
    });
</script>
@endpush