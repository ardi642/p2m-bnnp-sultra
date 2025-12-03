

@extends('admin')

@section('content')
    <!-- Main Content -->
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Kegiatan P2M</h1>
                    <p class="text-muted mb-0">Master Data P2M</p>
                </div>
            </div>
            @include('p2m.partials.select-p2m-index')

            <div class="row justify-content-center">
                <div class="col-12 col-lg-12">
                    <div class="card shadow-lg p-5">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="card-title mb-0 text-center">Data sosialisasi Car Free Day</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-7 align-items-center">
                                <div class="col-auto">
                                    <a href="#" class="btn btn-primary btn-sm"><i class="bi bi-sliders"></i>Filter Pencarian Lanjutan</a>
                                </div>
                                <div class="col-auto ms-auto">
                                    <div class="d-flex align-items-center gap-4">
                                        <input type="text" class="form-control" placeholder="pencarian...">
                                        <a href="#" class="btn btn-primary btn text-nowrap"><i class="bi bi-search"></i>Cari</a>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                {{-- PERUBAHAN 1: State 'expanded' sekarang berupa Array kosong [] --}}
                                <table class="table table-hover mb-0" x-data="{ expanded: [] }">
                                    <thead class="table-light">
                                        <tr class="text-center">
                                            <th>No</th>
                                            <th>Satuan Kerja</th>
                                            <th>Tanggal Pelaksanaan</th>
                                            <th>Tempat Kegiatan</th>  
                                            <th style="min-width: 200px;">Nama Pegawai</th>
                                            <th>Jumlah Peserta</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($cfds as $data)
                                            {{-- Baris Utama --}}
                                            <tr class="text-center align-middle">
                                                <td>{{ $cfds->firstItem() + $loop->index }}</td>
                                                <td>{{ $data->satuanKerja->satuan_kerja ?? '-' }}</td>
                                                <td>
                                                    {{ $data->tanggal_pelaksanaan->locale('id')->translatedFormat('l, d F Y') }}
                                                </td>
                                                <td>{{ $data->tempat_kegiatan }}</td>
                                                <td class="text-start">
                                                    @foreach($data->pegawai as $pegawai)
                                                        <span class="badge bg-primary mb-1">{{ $pegawai->nama }}</span>
                                                    @endforeach
                                                </td>
                                                <td>{{ $data->jumlah_peserta }}</td>
                                                
                                                                                  
                                                
                                                <td>
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        {{-- PERUBAHAN 2: Logika Tombol Multi-Expand --}}
                                                        <button type="button" 
                                                                class="btn btn-info btn-sm text-white" 
                                                                @click="expanded.includes({{ $data->id }}) 
                                                                    ? expanded = expanded.filter(id => id !== {{ $data->id }}) 
                                                                    : expanded.push({{ $data->id }})">
                                                            <i class="bi" :class="expanded.includes({{ $data->id }}) ? 'bi-eye-slash' : 'bi-eye'"></i> Detail
                                                        </button>

                                                        <a href="#" class="btn btn-success btn-sm"><i class="bi bi-pencil-square"></i> Perbarui</a>
                                                        
                                                        <form id="delete-form-{{ $data->id }}" 
                                                            action="{{ route('p2m.cfd.destroy', $data->id) }}" 
                                                            method="POST" 
                                                            class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete({{ $data->id }})"><i class="bi bi-trash"></i> Hapus</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>

                                            {{-- PERUBAHAN 3: Baris Detail muncul jika ID ada di dalam array expanded --}}
                                            <tr x-show="expanded.includes({{ $data->id }})" x-transition.duration.300ms class="bg-light">
                                                <td colspan="10" class="text-start p-4">
                                                    <div class="card border-0">
                                                        <div class="card-body">
                                                            <h5 class="card-title fw-bold text-primary mb-3">
                                                                Informasi Tambahan
                                                            </h5>
                                                            <div class="row">
                                                                <div class="col-md-12 mb-3">
                                                                    <div class="mb-0">
                                                                        <label class="fw-bold text-muted">Link Kelengkapan / Dokumentasi</label>
                                                                        <div class="d-flex align-items-center mt-2">
                                                                            <i class="bi bi-link-45deg fs-4 me-2 text-primary"></i>
                                                                            @if($data->link_kelengkapan_dokumentasi)
                                                                                <a href="{{ $data->link_kelengkapan_dokumentasi }}" target="_blank" class="text-decoration-underline text-break text-primary fw-semibold">
                                                                                    {{ $data->link_kelengkapan_dokumentasi }}
                                                                                </a>
                                                                            @else
                                                                                <span class="text-muted fst-italic">Tidak ada link dokumentasi</span>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                {{-- Tambahan Waktu Buat dan Update --}}
                                                                <div class="col-md-6">
                                                                    <div class="mb-0">
                                                                        <label class="fw-bold text-muted small text-uppercase">Dibuat Pada</label>
                                                                        <div class="d-flex align-items-center mt-1 text-dark">
                                                                            <i class="bi bi-clock fs-5 me-2 text-secondary"></i>
                                                                            {{ $data->created_at->locale('id')->translatedFormat('l, d F Y H:i') }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="mb-0">
                                                                        <label class="fw-bold text-muted small text-uppercase">Terakhir Diupdate</label>
                                                                        <div class="d-flex align-items-center mt-1 text-dark">
                                                                            <i class="bi bi-pencil-square fs-5 me-2 text-secondary"></i>
                                                                            {{ $data->updated_at->locale('id')->translatedFormat('l, d F Y H:i') }}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>

                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center p-4">
                                                    <div class="text-muted">
                                                        Belum ada data kegiatan sosialisasi
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4">
                                {{ $cfds->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    {{-- Pastikan Alpine JS dimuat --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script type="module">
        window.confirmDelete = function(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33', // Merah untuk hapus
                cancelButtonColor: '#3085d6', // Biru untuk batal
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
        @if(session('success'))
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: 'success',
                title: "{{ session('message') }}"
            });
        @endif
    </script>
@endpush