@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4 p-lg-5">
        
        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Data Ungkap Kasus</h1>
                <p class="text-muted mb-0">Laporan Pemberantasan Narkotika</p>
            </div>
            
            @if(auth()->user()->hasRole('operator'))
                <a href="{{ route('berantas.ungkap-kasus.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-plus-lg me-2"></i>Tambah Kasus
                </a>
            @endif
        </div>

        {{-- Alert Success --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Filter Section --}}
        <div class="card border-0 shadow-sm mb-4" x-data="{ showFilter: false }">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-secondary"><i class="bi bi-funnel me-2"></i>Filter Data</h6>
                    <button class="btn btn-sm btn-light border" @click="showFilter = !showFilter">
                        <i class="bi" :class="showFilter ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
                    </button>
                </div>
            </div>
            
            <div class="card-body" x-show="showFilter" x-transition>
                <form action="{{ route('berantas.ungkap-kasus.index') }}" method="GET">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="small text-muted fw-bold text-uppercase">Pencarian</label>
                            <input type="text" name="search" class="form-control" placeholder="No LKN, TKP, Nama Tersangka..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold text-uppercase">Bulan</label>
                            <select name="bulan[]" class="form-select" id="select-bulan" multiple>
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ in_array($m, request('bulan', [])) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="small text-muted fw-bold text-uppercase">Tahun</label>
                            <select name="tahun[]" class="form-select" id="select-tahun" multiple>
                                @foreach($years as $y)
                                    <option value="{{ $y }}" {{ in_array($y, request('tahun', [])) ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i> Cari</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Table --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="text-uppercase small text-secondary">
                                <th class="ps-4 py-3">No</th>
                                <th class="py-3">Nomor LKN</th>
                                <th class="py-3">Tanggal & TKP</th>
                                <th class="py-3">Tersangka</th>
                                <th class="py-3">Barang Bukti</th>
                                <th class="py-3 text-center">Dokumen</th>
                                <th class="py-3 text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kasus as $item)
                                <tr>
                                    <td class="ps-4 text-muted fw-bold">{{ $kasus->firstItem() + $loop->index }}</td>
                                    <td>
                                        <span class="fw-bold text-primary">{{ $item->nomor_lkn }}</span><br>
                                        <span class="small text-muted">{{ $item->satuanKerja->satuan_kerja ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-semibold">{{ \Carbon\Carbon::parse($item->tanggal_kejadian)->locale('id')->translatedFormat('d F Y') }}</span>
                                            <span class="small text-muted text-truncate" style="max-width: 200px;" title="{{ $item->alamat_tkp }}">{{ $item->alamat_tkp }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark border border-warning">{{ $item->tersangka->count() }} Orang</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark border border-info">{{ $item->barangBukti->count() }} Item</span>
                                    </td>
                                    <td class="text-center">
                                        @if($item->dokumentasi->count() > 0)
                                            <span class="badge bg-secondary"><i class="bi bi-paperclip"></i> {{ $item->dokumentasi->count() }}</span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group btn-group-sm">
                                            @if(auth()->user()->hasRole('operator'))
                                                <a href="{{ route('berantas.ungkap-kasus.edit', $item->id) }}" class="btn btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                
                                                <button type="button" class="btn btn-outline-danger" onclick="confirmDelete({{ $item->id }})" title="Hapus"><i class="bi bi-trash"></i></button>
                                                <form id="delete-form-{{ $item->id }}" action="{{ route('berantas.ungkap-kasus.destroy', $item->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center py-5 text-muted fst-italic">Belum ada data kasus.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white py-3 border-top-0">
                {{ $kasus->links() }}
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
        if(document.getElementById('select-bulan')) new TomSelect('#select-bulan', {plugins: ['remove_button']});
        if(document.getElementById('select-tahun')) new TomSelect('#select-tahun', {plugins: ['remove_button']});
    });

    window.confirmDelete = function(id) {
        Swal.fire({
            title: 'Hapus Data?', text: "Data tidak dapat dikembalikan!", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d', confirmButtonText: 'Ya, Hapus'
        }).then((result) => {
            if (result.isConfirmed) document.getElementById('delete-form-' + id).submit();
        });
    }
</script>
@endpush