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

                <div class="card shadow-lg p-4">
                    <div class="card-body">
                        <form action="{{ route('admin.users.store') }}" method="POST">
                            @csrf

                            {{-- ALERT KEMBALI KE STYLE BIRU (SESUAI GAMBAR) --}}
                            <div class="alert alert-info d-flex align-items-center mb-4" role="alert">
                                <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                                <div>
                                    Password default adalah: <strong>password</strong>.
                                    <br>Hanya pegawai yang <strong>belum memiliki akun</strong> yang muncul di daftar.
                                </div>
                            </div>

                            {{-- PILIH PEGAWAI --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold">Pilih Pegawai <span class="text-danger">*</span></label>
                                <select id="select-pegawai" name="pegawai_nip" class="form-control @error('pegawai_nip') is-invalid @enderror" placeholder="Cari Nama atau NIP..." autocomplete="off">
                                    <option value="">Pilih Pegawai...</option>
                                    @foreach($pegawais as $pgw)
                                        {{-- DATA ATTRIBUTES UNTUK JAVASCRIPT --}}
                                        <option value="{{ $pgw->nip }}" 
                                            data-nama="{{ $pgw->nama }}"
                                            data-nip="{{ $pgw->nip }}"
                                            data-email="{{ $pgw->email }}"
                                            data-satker="{{ $pgw->satuanKerja->satuan_kerja ?? '-' }}">
                                            {{ $pgw->nama }} ({{ $pgw->nip }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('pegawai_nip') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            {{-- INFO DINAMIS PEGAWAI (MUNCUL OTOMATIS) --}}
                            <div id="pegawai-detail-card" class="card bg-light border-0 mb-4 d-none">
                                <div class="card-body">
                                    <h6 class="card-title text-primary fw-bold mb-3">
                                        <i class="bi bi-person-badge me-2"></i>Detail Pegawai Terpilih
                                    </h6>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">Nama Lengkap</small>
                                            <span class="fw-bold text-dark" id="preview-nama">-</span>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted d-block">NIP</small>
                                            <span class="fw-bold text-dark" id="preview-nip">-</span>
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <small class="text-muted d-block">Email</small>
                                            <span class="fw-bold text-dark" id="preview-email">-</span>
                                        </div>
                                        <div class="col-md-6 mt-2">
                                            <small class="text-muted d-block">Satuan Kerja</small>
                                            <span class="badge bg-secondary" id="preview-satker">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- PILIH ROLE --}}
                            <div class="mb-4">
                                <label class="form-label fw-bold">Role Akses <span class="text-danger">*</span></label>
                                <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                                    <option value="" disabled selected>Pilih Role...</option>
                                    
                                    {{-- PILIHAN ROLE BERDASARKAN HAK AKSES LOGIN --}}
                                    @if(auth()->user()->hasRole('admin'))
                                        <option value="admin_satker">Admin Satuan Kerja</option>
                                        <option value="admin_p2m">Admin P2M</option>
                                        <option value="admin_berantas">Admin Berantas</option>
                                        <option value="admin_rehab">Admin Rehab</option>
                                        <option value="operator_satker">Operator Satuan Kerja</option>
                                        <option value="operator_p2m">Operator P2M</option>
                                        <option value="operator_berantas">Operator Berantas</option>
                                        <option value="operator_rehab">Operator Rehab</option>
                                    @endif

                                    @if(auth()->user()->hasRole('admin_satker'))
                                        <option value="admin_p2m">Admin P2M</option>
                                        <option value="admin_berantas">Admin Berantas</option>
                                        <option value="admin_rehab">Admin Rehab</option>
                                        <option value="operator_satker">Operator Satuan Kerja</option>
                                        <option value="operator_p2m">Operator P2M</option>
                                        <option value="operator_berantas">Operator Berantas</option>
                                        <option value="operator_rehab">Operator Rehab</option>
                                    @endif

                                    @if(auth()->user()->hasRole('admin_p2m'))
                                        <option value="operator_p2m">Operator P2M</option>
                                    @endif
                                    @if(auth()->user()->hasRole('admin_berantas'))
                                        <option value="operator_berantas">Operator Berantas</option>
                                    @endif
                                    @if(auth()->user()->hasRole('admin_rehab'))
                                        <option value="operator_rehab">Operator Rehab</option>
                                    @endif
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

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        // Inisialisasi TomSelect
        var selectElement = document.getElementById('select-pegawai');
        
        var tom = new TomSelect(selectElement, {
            create: false,
            sortField: { field: "text", direction: "asc" },
            placeholder: "Cari Pegawai...",
            onChange: function(value) {
                updatePegawaiInfo(value);
            }
        });

        // Fungsi Update Info Pegawai
        function updatePegawaiInfo(nip) {
            const detailCard = document.getElementById('pegawai-detail-card');
            
            if (!nip) {
                detailCard.classList.add('d-none');
                return;
            }

            // Cari element option asli di dalam DOM Select untuk ambil data-attribute
            const originalOption = selectElement.querySelector(`option[value="${nip}"]`);

            if (originalOption) {
                const nama = originalOption.getAttribute('data-nama');
                const email = originalOption.getAttribute('data-email');
                const satker = originalOption.getAttribute('data-satker');

                document.getElementById('preview-nama').textContent = nama;
                document.getElementById('preview-nip').textContent = nip;
                document.getElementById('preview-email').textContent = email;
                document.getElementById('preview-satker').textContent = satker;

                detailCard.classList.remove('d-none');
            }
        }
    });
</script>
@endpush