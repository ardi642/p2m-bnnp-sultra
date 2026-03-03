@extends('admin')

@section('content')
@php
    $allFilters = request()->only([
        'search', 'bulan', 'tahun', 'satuan_kerja_id', 
        'jenis_kelamin', 'pendidikan', 'pekerjaan', 
        'sumber_pasien', 'narkotika_ids'
    ]);
    if (empty($allFilters['tahun'])) { $allFilters['tahun'] = [date('Y')]; }
    $activeFilters = collect($allFilters)->filter(function($value) { 
        return !empty($value) && ($value !== ['']); 
    })->count();

    $sortLink = function($col, $label) {
        $currCol = request('sort_by', 'created_at');
        $currOrd = request('sort_order', 'desc');
        $newOrd = ($currCol === $col && $currOrd === 'desc') ? 'asc' : 'desc';
        $icon = 'bi-arrow-down-up text-muted opacity-25'; 
        if ($currCol === $col) {
            $icon = $currOrd === 'desc' ? 'bi-sort-down text-primary' : 'bi-sort-up text-primary';
        }
        $url = request()->fullUrlWithQuery(['sort_by' => $col, 'sort_order' => $newOrd]);
        return '<a href="'.$url.'" class="text-decoration-none text-secondary fw-bold d-flex align-items-center gap-2">'.$label.' <i class="bi '.$icon.'"></i></a>';
    };
@endphp

<main class="admin-main" x-data="{ showFilter: true }">
    <div class="container-fluid p-4 p-lg-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Data Pasien Rehabilitasi</h1>
                <p class="text-muted mb-0">Kelola Riwayat Rekam Medis Pasien Narkotika</p>
            </div>
            <a href="{{ route('rehab.pasien.create') }}" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-person-plus-fill"></i> Tambah Pasien Baru
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
                <i class="bi bi-check-circle-fill me-2"></i><strong>Berhasil!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Gagal!</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-secondary"><i class="bi bi-table me-2"></i>Tabel Riwayat Rehabilitasi</h5>
                <button type="button" @click="showFilter = !showFilter" class="btn btn-sm transition-all d-flex align-items-center gap-2" :class="showFilter ? 'btn-light text-secondary border' : 'btn-primary shadow-sm'">
                    <i class="bi" :class="showFilter ? 'bi-chevron-up' : 'bi-funnel'"></i> 
                    <span x-text="showFilter ? 'Sembunyikan Filter' : 'Filter Pencarian'"></span>
                    @if($activeFilters > 0) <span class="badge bg-danger rounded-pill">{{ $activeFilters }}</span> @endif
                </button>
            </div>

            <div class="card-body p-0 p-lg-4">
                <div x-show="showFilter" x-transition x-cloak class="mb-4 bg-body-tertiary p-4 rounded-3 border mx-3 mx-lg-0 mt-3 mt-lg-0">
                    <form action="{{ route('rehab.pasien.index') }}" method="GET" id="form-filter">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold small text-secondary">Kata Kunci</label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari ID Pasien / Nama Pasien..." value="{{ request('search') }}">
                                </div>
                            </div>
                            
                            @if(Auth::user()->isAdmin())
                            <div class="col-lg-4">
                                <label class="form-label fw-bold small text-secondary">Satuan Kerja</label>
                                <select id="sel-satker" name="satuan_kerja_id[]" multiple placeholder="Pilih Satuan Kerja...">
                                    @foreach($satuanKerjas as $sk) 
                                        <option value="{{ $sk->id }}" {{ in_array($sk->id, request('satuan_kerja_id', [])) ? 'selected' : '' }}>{{ $sk->satuan_kerja }}</option> 
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <div class="{{ Auth::user()->isAdmin() ? 'col-lg-4' : 'col-md-6' }}">
                                <label class="form-label fw-bold small text-secondary">Tahun</label>
                                <select id="sel-tahun" name="tahun[]" multiple placeholder="Pilih Tahun...">
                                    @foreach($years as $y) 
                                        <option value="{{ $y }}" {{ in_array($y, request('tahun', [date('Y')])) ? 'selected' : '' }}>{{ $y }}</option> 
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="{{ Auth::user()->isAdmin() ? 'col-lg-4' : 'col-md-6' }}">
                                <label class="form-label fw-bold small text-secondary">Bulan</label>
                                <select id="sel-bulan" name="bulan[]" multiple placeholder="Pilih Bulan...">
                                    @foreach(range(1,12) as $m) 
                                        <option value="{{ $m }}" {{ in_array($m, request('bulan', [])) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option> 
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-secondary">Jenis Kelamin</label>
                                <select id="sel-jk" name="jenis_kelamin[]" multiple placeholder="Pilih Jenis Kelamin...">
                                    <option value="Laki-laki" {{ in_array('Laki-laki', request('jenis_kelamin', [])) ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="Perempuan" {{ in_array('Perempuan', request('jenis_kelamin', [])) ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-secondary">Sumber Pasien</label>
                                <select id="sel-sumber" name="sumber_pasien[]" multiple placeholder="Pilih Sumber...">
                                    @foreach(\App\Constants\SumberPasien::ALL as $sp)
                                        <option value="{{ $sp }}" {{ in_array($sp, request('sumber_pasien', [])) ? 'selected' : '' }}>{{ $sp }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-secondary">Pekerjaan</label>
                                @php
                                    // Gabungkan konstanta dengan request agar tag ketikan manual tidak hilang saat refresh
                                    $reqPekerjaan = request('pekerjaan', []);
                                    $mergedPekerjaan = array_unique(array_merge($pekerjaans, $reqPekerjaan));
                                @endphp
                                <select id="sel-kerja" name="pekerjaan[]" multiple placeholder="Pilih/Ketik Pekerjaan...">
                                    @foreach($mergedPekerjaan as $p) 
                                        <option value="{{ $p }}" {{ in_array($p, $reqPekerjaan) ? 'selected' : '' }}>{{ $p }}</option> 
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-secondary">Pendidikan</label>
                                <select id="sel-didik" name="pendidikan[]" multiple placeholder="Pilih Pendidikan...">
                                    @foreach(\App\Constants\Pendidikan::ALL as $p) 
                                        <option value="{{ $p }}" {{ in_array($p, request('pendidikan', [])) ? 'selected' : '' }}>{{ $p }}</option> 
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 mt-3">
                                <label class="form-label fw-bold small text-secondary">Jenis Narkotika</label>
                                <select id="sel-narko" name="narkotika_ids[]" multiple placeholder="Cari Narkotika...">
                                    @foreach($masterNarkotika as $n) 
                                        <option value="{{ $n->id }}" {{ in_array($n->id, request('narkotika_ids', [])) ? 'selected' : '' }}>{{ $n->nama_narkotika }}</option> 
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 text-end pt-3 border-top mt-4">
                                <a href="{{ route('rehab.pasien.index') }}" class="btn btn-link text-decoration-none text-muted btn-sm me-2">Reset Filter</a>
                                <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-funnel-fill me-1"></i> Terapkan Filter</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="d-flex justify-content-between mb-3 px-3 px-lg-0">
                    <a href="{{ route('rehab.pasien.export', request()->query()) }}" class="btn btn-success btn-sm shadow-sm"><i class="bi bi-file-excel me-1"></i> Export Excel</a>
                    <div class="text-muted small border border-secondary-subtle rounded px-3 py-1 bg-light">
                        Total Riwayat: <strong>{{ $data->total() }}</strong>
                    </div>
                </div>

                <div class="custom-table-scroll mb-3 border border-secondary-subtle rounded mx-3 mx-lg-0">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="bg-light sticky-top">
                            <tr class="small text-uppercase text-secondary">
                                <th class="py-3 bg-light ps-3 text-center">No</th>
                                <th class="py-3 bg-light">{!! $sortLink('satuan_kerja_id', 'Satuan Kerja') !!}</th>
                                <th class="py-3 bg-light">{!! $sortLink('id_pasien', 'ID Pasien') !!}</th>
                                <th class="py-3 bg-light">{!! $sortLink('nama_pasien', 'Nama Pasien') !!}</th>
                                <th class="py-3 bg-light">{!! $sortLink('jenis_kelamin', 'Jenis Kelamin') !!}</th>
                                <th class="py-3 bg-light">{!! $sortLink('tanggal_rehab', 'Tgl Masuk Rehab') !!}</th>
                                <th class="py-3 bg-light text-center">{!! $sortLink('usia', 'Usia Masuk') !!}</th>
                                <th class="py-3 bg-light">{!! $sortLink('pekerjaan', 'Pekerjaan') !!}</th>
                                <th class="py-3 bg-light">{!! $sortLink('pendidikan', 'Pendidikan') !!}</th>
                                <th class="py-3 bg-light">{!! $sortLink('sumber_pasien', 'Sumber') !!}</th>
                                <th class="py-3 bg-light text-start">Narkotika</th>
                                <th class="py-3 bg-light text-center">{!! $sortLink('created_at', 'Dibuat') !!}</th>
                                <th class="py-3 bg-light text-center pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            @forelse($data as $key => $row)
                            <tr class="align-middle">
                                <td class="py-3 ps-3 text-center fw-bold text-muted">{{ $data->firstItem() + $key }}</td>
                                <td class="py-3">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        {{ $row->pasien->satuanKerja->satuan_kerja ?? '-' }}
                                    </span>
                                </td>
                                <td class="py-3 fw-bold font-monospace text-dark">{{ $row->pasien->id_pasien }}</td>
                                <td class="py-3 fw-semibold">{{ $row->pasien->nama_pasien }}</td>
                                <td class="py-3">{{ $row->pasien->jenis_kelamin }}</td>
                                <td class="py-3 text-primary fw-semibold">{{ $row->tanggal_rehab->format('d/m/Y') }}</td>
                                <td class="py-3 text-center">{{ $row->usia_saat_rehab }} Thn</td>
                                <td class="py-3">{{ $row->pekerjaan }}</td>
                                <td class="py-3">{{ $row->pendidikan }}</td>
                                <td class="py-3 text-center">
                                    <span class="badge border bg-light text-dark">
                                        {{ $row->sumber_pasien }}
                                    </span>
                                </td>
                                <td class="py-3 text-start">
                                    <div class="text-wrap" style="max-width: 150px;">
                                        @foreach($row->narkotika as $n) 
                                            <span class="badge bg-secondary mb-1">{{ $n->nama_narkotika }}</span> 
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3 text-center small text-muted">{{ $row->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-3 pe-3 text-center">
                                    <div class="btn-group btn-group-sm shadow-sm">
                                        <a href="{{ route('rehab.pasien.show', $row->pasien->id) }}" class="btn btn-light border border-secondary-subtle" title="Lihat Detail Pasien">
                                            <i class="bi bi-eye text-primary"></i>
                                        </a>
                                        @if (auth()->user()->hasRole(['operator_satker', 'operator_rehab', 'admin']))
                                            <a href="{{ route('rehab.pasien.riwayat.edit', ['id' => $row->id, 'ref' => 'index']) }}" class="btn btn-light border border-secondary-subtle" title="Edit Riwayat Ini">
                                                <i class="bi bi-pencil-square text-warning"></i>
                                            </a>
                                            <button type="button" class="btn btn-light border border-secondary-subtle text-danger" onclick="confirmDelete({{ $row->id }})" title="Hapus Riwayat">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <form id="delete-form-{{ $row->id }}" action="{{ route('rehab.pasien.destroy', $row->id) }}" method="POST" class="d-none">
                                                @csrf @method('DELETE')
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="13" class="text-center py-5 text-muted fst-italic">
                                    Tidak ada data pasien ditemukan.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end mx-3 mx-lg-0">{{ $data->withQueryString()->links() }}</div>
            </div>
        </div>
    </div>
</main>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .ts-dropdown { z-index: 2000 !important; }
    .custom-table-scroll { max-height: 70vh; overflow-y: auto; }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Konfigurasi bawaan (hanya bisa memilih dari list)
        const confDefault = { plugins: ['remove_button', 'clear_button'], create: false, maxOptions: null };
        // Konfigurasi khusus (bisa pilih list ATAU ketik teks manual sendiri)
        const confBebasKetik = { plugins: ['remove_button', 'clear_button'], create: true, maxOptions: null };

        // Aplikasikan confDefault ke semua dropdown kecuali pekerjaan
        ['sel-satker','sel-tahun','sel-bulan','sel-jk','sel-sumber','sel-narko','sel-didik'].forEach(id => { 
            if(document.getElementById(id)) new TomSelect('#'+id, confDefault); 
        });

        // Aplikasikan confBebasKetik KHUSUS untuk pekerjaan
        if(document.getElementById('sel-kerja')) {
            new TomSelect('#sel-kerja', confBebasKetik);
        }
    });

    window.confirmDelete = function(id) {
        Swal.fire({
            title: 'Hapus Riwayat?', 
            text: "Data kedatangan ini akan dihapus permanen.", 
            icon: 'warning',
            showCancelButton: true, 
            confirmButtonColor: '#dc3545', 
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus', 
            cancelButtonText: 'Batal', 
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) { 
                document.getElementById('delete-form-' + id).submit(); 
            }
        });
    }
</script>
@endpush