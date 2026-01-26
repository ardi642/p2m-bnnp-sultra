@extends('admin')

@section('content')
<main class="admin-main" x-data="{ showFilter: true, expanded: [] }">
    <div class="container-fluid p-4 p-lg-5">
        
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Data Laporan Rehabilitasi</h1>
                <p class="text-muted mb-0">Rekapitulasi Target dan Realisasi Bulanan</p>
            </div>
            
            @if(auth()->user()->hasRole(['operator_satker', 'operator_rehab']))
                <a href="{{ route('rehab.laporan.create') }}" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                    <i class="bi bi-plus-lg"></i> Tambah Data
                </a>
            @endif
        </div>

        {{-- ALERT --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><strong>Berhasil!</strong> {{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- LOGIKA PHP --}}
        @php
            // Hitung Filter
            $filterCount = collect(request()->only(['bulan', 'tahun', 'satuan_kerja_id']))->filter()->count();
            if (!request()->has('tahun')) $filterCount += 1; // Default tahun ini
            
            // Helper Sorting
            $sortLink = function($col, $label) {
                $currentCol = request('sort_by', 'periode'); 
                $currentOrder = request('sort_order', 'desc');
                $newOrder = ($currentCol === $col && $currentOrder === 'desc') ? 'asc' : 'desc';
                $icon = 'bi-arrow-down-up text-muted opacity-25';
                if ($currentCol === $col) {
                    $icon = $currentOrder === 'desc' ? 'bi-sort-down text-white' : 'bi-sort-up text-white';
                }
                $url = request()->fullUrlWithQuery(['sort_by' => $col, 'sort_order' => $newOrder]);
                return '<a href="'.$url.'" class="text-decoration-none text-white fw-bold d-flex align-items-center justify-content-between gap-2">'.$label.' <i class="bi '.$icon.'"></i></a>';
            };

            // Hak Akses Export
            $user = Auth::user();
            $userSatker = ($user->pegawai && $user->pegawai->satuanKerja) ? $user->pegawai->satuanKerja : null;
            $isSuperAdmin = $user->hasRole('admin') && !$userSatker;
            $isBnnpSultra = false;
            if ($userSatker) {
                $namaSatker = strtoupper(trim($userSatker->satuan_kerja));
                $isBnnpSultra = ($namaSatker === 'BNNP SULTRA');
            }
            $canExport = $isSuperAdmin || $isBnnpSultra;
        @endphp

        <div class="row justify-content-center mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    
                    {{-- CARD HEADER FILTER --}}
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
                            <h5 class="card-title mb-0 fw-bold text-secondary">
                                <i class="bi bi-table me-2"></i>Data Laporan
                            </h5>
                            <button type="button" @click="showFilter = !showFilter" 
                                class="btn btn-sm transition-all d-flex align-items-center gap-2"
                                :class="showFilter ? 'btn-light text-secondary border' : 'btn-primary shadow-sm'">
                                <i class="bi" :class="showFilter ? 'bi-chevron-up' : 'bi-funnel'"></i> 
                                <span x-text="showFilter ? 'Sembunyikan Filter' : 'Filter Pencarian'"></span>
                                @if($filterCount > 0) <span class="badge bg-danger rounded-pill">{{ $filterCount }}</span> @endif
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0 p-lg-4">
                        
                        <form action="{{ route('rehab.laporan.index') }}" method="GET">
                            <input type="hidden" name="sort_by" value="{{ request('sort_by', 'periode') }}">
                            <input type="hidden" name="sort_order" value="{{ request('sort_order', 'desc') }}">

                            <div x-show="showFilter" x-transition class="mb-4 px-3 px-lg-0 pt-3 pt-lg-0">
                                <div class="bg-body-tertiary p-4 rounded-3 border">
                                    <div class="row g-3 text-start">
                                        @if(auth()->user()->hasRole('admin'))
                                            <div class="col-lg-6">
                                                <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Satuan Kerja</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-satker" name="satuan_kerja_id[]" multiple placeholder="Pilih Satuan Kerja..." autocomplete="off">
                                                        @foreach($satuanKerjas as $s)
                                                            <option value="{{ $s->id }}" {{ in_array($s->id, request('satuan_kerja_id', [])) ? 'selected' : '' }}>{{ $s->satuan_kerja }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="col-6 {{ auth()->user()->hasRole('admin') ? 'col-lg-3' : 'col-lg-6' }}">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Bulan</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-bulan" name="bulan[]" multiple placeholder="Pilih Bulan..." autocomplete="off">
                                                    @foreach(range(1,12) as $m)
                                                        <option value="{{ $m }}" {{ in_array($m, request('bulan', [])) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 {{ auth()->user()->hasRole('admin') ? 'col-lg-3' : 'col-lg-6' }}">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Tahun</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-tahun" name="tahun[]" multiple placeholder="Pilih Tahun..." autocomplete="off">
                                                    @php
                                                        $defaultYears = request()->has('tahun') ? [] : [date('Y')];
                                                    @endphp
                                                    @foreach($years as $y)
                                                        <option value="{{ $y }}" {{ in_array($y, request('tahun', $defaultYears)) ? 'selected' : '' }}>{{ $y }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-12 text-end pt-3 border-top mt-4 text-start">
                                            <a href="{{ route('rehab.laporan.index') }}" class="btn btn-link text-decoration-none text-muted btn-sm me-2">Reset</a>
                                            <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-funnel-fill me-1"></i> Terapkan</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- BARIS EXPORT & TOTAL DATA --}}
                            <div class="d-flex justify-content-between align-items-center mb-3 px-3 px-lg-0" style="position: relative; z-index: 1050;">
                                
                                {{-- KIRI: TOMBOL EXPORT (FIX LINK URL) --}}
                                <div>
                                    @if($canExport)
                                        <div class="dropdown">
                                            <button class="btn btn-success btn-sm dropdown-toggle shadow-sm d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-file-earmark-excel"></i> Export Excel
                                            </button>
                                            <ul class="dropdown-menu shadow-sm border-0">
                                                <li><h6 class="dropdown-header small text-uppercase fw-bold text-muted">Pilih Kategori</h6></li>
                                                {{-- KUNCI PERBAIKAN: Gunakan array_merge dengan request()->query() --}}
                                                {{-- Ini menjamin SEMUA filter (tahun[], bulan[], satker[]) terbawa otomatis dengan benar --}}
                                                <li>
                                                    <a class="dropdown-item py-2" href="{{ route('rehab.laporan.export', array_merge(request()->query(), ['kategori' => 'rawat_jalan'])) }}">
                                                        <i class="bi bi-bandaid me-2 text-warning"></i> Rawat Jalan
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2" href="{{ route('rehab.laporan.export', array_merge(request()->query(), ['kategori' => 'pasca_rehab'])) }}">
                                                        <i class="bi bi-heart-pulse me-2 text-success"></i> Pasca Rehabilitasi
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item py-2" href="{{ route('rehab.laporan.export', array_merge(request()->query(), ['kategori' => 'skhpn'])) }}">
                                                        <i class="bi bi-file-medical me-2 text-info"></i> SKHPN
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    @endif
                                </div>

                                {{-- KANAN: TOTAL DATA --}}
                                <div>
                                    <div class="text-muted small fst-italic">
                                        Total Data: <strong>{{ $data->total() }}</strong>
                                    </div>
                                </div>

                            </div>
                        </form>
                        
                        {{-- TABEL DATA --}}
                        <div class="custom-table-scroll mb-3" id="data-table" style="position: relative; z-index: 1;">
                            <table class="table table-bordered table-hover align-middle mb-0 text-center" style="min-width: 1200px;" x-data="{ expanded: [] }">
                                <thead class="bg-primary text-white sticky-top">
                                    <tr>
                                        <th rowspan="2" class="align-middle bg-primary border-white ps-3" width="5%">No</th>
                                        <th rowspan="2" class="align-middle bg-primary border-white" width="15%">{!! $sortLink('satuan_kerja_id', 'Satuan Kerja') !!}</th>
                                        <th rowspan="2" class="align-middle bg-primary border-white" width="12%">{!! $sortLink('periode', 'Periode') !!}</th>
                                        <th colspan="2" class="bg-warning text-dark border-dark">RAWAT JALAN (ORANG)</th>
                                        <th colspan="2" class="bg-success text-white border-white">PASCA REHAB (ORANG)</th>
                                        <th colspan="2" class="bg-info text-dark border-dark">SKHPN (ORANG)</th>
                                        <th rowspan="2" class="align-middle bg-primary border-white" width="10%">{!! $sortLink('created_at', 'Dibuat') !!}</th>
                                        <th rowspan="2" class="align-middle bg-secondary text-white border-white pe-3" width="8%">Aksi</th>
                                    </tr>
                                    <tr>
                                        <th class="bg-warning bg-opacity-25 text-dark">Target</th>
                                        <th class="bg-warning bg-opacity-50 text-dark">Realisasi</th>
                                        <th class="bg-success bg-opacity-25 text-white">Target</th>
                                        <th class="bg-success bg-opacity-50 text-white">Realisasi</th>
                                        <th class="bg-info bg-opacity-25 text-dark">Target</th>
                                        <th class="bg-info bg-opacity-50 text-dark">Realisasi</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                    @forelse($data as $key => $row)
                                    <tr :class="expanded.includes({{ $row->id }}) ? 'bg-light' : ''">
                                        <td class="text-secondary fw-bold ps-3">{{ $data->firstItem() + $key }}</td>
                                        <td class="text-start fw-bold text-dark">{{ $row->satuanKerja->satuan_kerja ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border border-secondary-subtle px-2 py-1 shadow-sm">{{ $row->periode_text }}</span>
                                        </td>
                                        <td class="text-secondary">{{ number_format($row->target_rawat_jalan) }}</td>
                                        <td class="fw-bold text-dark">{{ number_format($row->realisasi_rawat_jalan) }}</td>
                                        <td class="text-secondary">{{ number_format($row->target_pasca_rehab) }}</td>
                                        <td class="fw-bold text-dark">{{ number_format($row->realisasi_pasca_rehab) }}</td>
                                        <td class="text-secondary">{{ number_format($row->target_skhpn) }}</td>
                                        <td class="fw-bold text-dark">{{ number_format($row->realisasi_skhpn) }}</td>
                                        <td class="small text-muted text-nowrap">
                                            <div class="text-dark fw-bold">{{ $row->created_at->format('d/m/Y') }}</div>
                                            <div>{{ $row->created_at->format('H:i') }} WIB</div>
                                        </td>
                                        <td class="pe-3">
                                            <div class="btn-group btn-group-sm shadow-sm">
                                                <button type="button" class="btn btn-light border text-secondary" @click="expanded.includes({{ $row->id }}) ? expanded = expanded.filter(id => id !== {{ $row->id }}) : expanded.push({{ $row->id }})">
                                                    <i class="bi" :class="expanded.includes({{ $row->id }}) ? 'bi-chevron-up text-primary' : 'bi-eye text-secondary'"></i>
                                                </button>
                                                @if(auth()->user()->hasRole(['operator_satker', 'operator_rehab']))
                                                    <a href="{{ route('rehab.laporan.edit', $row->id) }}" class="btn btn-light border text-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                    <button type="button" class="btn btn-light border text-danger" onclick="confirmDelete({{ $row->id }})" title="Hapus"><i class="bi bi-trash"></i></button>
                                                    <form id="delete-form-{{ $row->id }}" action="{{ route('rehab.laporan.destroy', $row->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    {{-- BARIS DETAIL --}}
                                    <tr x-show="expanded.includes({{ $row->id }})" x-transition>
                                        <td colspan="12" class="p-0 border-0">
                                            <div class="bg-body-tertiary p-4 border-bottom shadow-inner text-start">
                                                <div class="card border-0 shadow-sm">
                                                    <div class="card-body">
                                                        <h6 class="card-title fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-info-circle me-2"></i>Detail Bukti Dukung & Informasi</h6>
                                                        <div class="row g-3">
                                                            <div class="col-md-4">
                                                                <ul class="list-group list-group-flush small">
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0"><span class="text-secondary">Satuan Kerja</span><span class="fw-bold text-dark">{{ $row->satuanKerja->satuan_kerja ?? '-' }}</span></li>
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0"><span class="text-secondary">Periode</span><span class="fw-bold text-dark">{{ $row->periode_text }}</span></li>
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0"><span class="text-secondary">Jumlah File</span><span class="badge bg-primary rounded-pill">{{ $row->dokumentasi->count() }}</span></li>
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0"><span class="text-secondary">Dibuat pada</span><span class="fw-bold text-dark">{{ $row->created_at->translatedFormat('d F Y H:i') }}</span></li>
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent px-0"><span class="text-secondary">Terakhir diubah</span><span class="fw-bold text-dark">{{ $row->updated_at->translatedFormat('d F Y H:i') }}</span></li>
                                                                </ul>
                                                            </div>
                                                            <div class="col-md-8">
                                                                <h6 class="small fw-bold text-secondary mb-2 text-uppercase">Lampiran File</h6>
                                                                @if($row->dokumentasi->count() > 0)
                                                                    <div class="row g-2">
                                                                        @foreach($row->dokumentasi as $doc)
                                                                            <div class="col-12 col-lg-6">
                                                                                <div class="p-2 border rounded bg-light d-flex justify-content-between align-items-center h-100 shadow-sm">
                                                                                    <div class="small fw-bold text-dark text-truncate pe-2" style="max-width: 80%;">
                                                                                        @if(Str::contains($doc->tipe_file, 'image')) <i class="bi bi-file-image text-primary me-1"></i>
                                                                                        @elseif(Str::contains($doc->tipe_file, 'pdf')) <i class="bi bi-file-pdf text-danger me-1"></i>
                                                                                        @elseif(Str::contains($doc->tipe_file, ['word', 'officedocument'])) <i class="bi bi-file-word text-primary me-1"></i>
                                                                                        @else <i class="bi bi-file-earmark-text text-secondary me-1"></i> @endif
                                                                                        {{ $doc->nama_file_asli }}
                                                                                    </div>
                                                                                    <div class="d-flex gap-1 flex-shrink-0">
                                                                                        @if(Str::contains($doc->tipe_file, ['image', 'pdf']))
                                                                                            <a href="{{ Storage::url($doc->path_file) }}" target="_blank" class="btn btn-xs btn-outline-info px-2 py-0" title="Preview"><i class="bi bi-eye"></i></a>
                                                                                        @endif
                                                                                        <a href="{{ route('dokumentasi.download', $doc->id) }}" class="btn btn-xs btn-outline-primary px-2 py-0" title="Download"><i class="bi bi-download"></i></a>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <div class="alert alert-light border border-secondary-subtle text-center text-muted fst-italic py-2 small mb-0">Tidak ada bukti dukung yang dilampirkan.</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="12" class="py-5 text-muted fst-italic">Tidak ada laporan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        {{-- FOOTER PAGINATION --}}
                        <div class="card-footer bg-white py-3 border-top-0">
                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <select class="form-select form-select-sm border-secondary-subtle" style="width: 70px;" onchange="window.location.href = this.value">
                                        @foreach([10, 25, 50, 100] as $num)
                                            <option value="{{ request()->fullUrlWithQuery(['per_page' => $num, 'page' => 1]) }}" {{ request('per_page') == $num ? 'selected' : '' }}>{{ $num }}</option>
                                        @endforeach
                                    </select>
                                    <span class="text-muted small">Data / halaman</span>
                                </div>
                                <div>{{ $data->withQueryString()->links() }}</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@push('styles')
<style>
    .ts-dropdown, .ts-dropdown.single { z-index: 2000 !important; }
    .ts-control { border: none !important; box-shadow: none !important; padding-top: 0.5rem; padding-bottom: 0.5rem; background-color: transparent !important; min-height: 40px; }
    .ts-wrapper.focus .ts-control { box-shadow: none !important; }
    .custom-table-scroll { max-height: 70vh; overflow-y: auto; position: relative; border: 1px solid #dee2e6; border-radius: 6px; }
    
    /* Z-Index Rendah untuk tabel agar kalah dari dropdown */
    .custom-table-scroll thead th { position: sticky !important; top: 0 !important; z-index: 10; box-shadow: inset 0 -1px 0 #dee2e6; }
    
    .btn-xs { padding: 1px 5px; font-size: 0.75rem; }
    .page-link { border: none; color: #6c757d; border-radius: 50% !important; margin: 0 2px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
    .page-item.active .page-link { background-color: #0d6efd; color: white; box-shadow: 0 2px 4px rgba(13,110,253,0.3); }
    .shadow-inner { box-shadow: inset 0 2px 4px 0 rgba(0,0,0,0.06); }
    [x-cloak] { display: none !important; }
</style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const configTomSelect = { 
            plugins: ['remove_button', 'clear_button'], 
            persist: false, 
            create: false, 
            maxOptions: null,
            placeholder: 'Pilih...'
        };
        ['select-bulan', 'select-tahun'].forEach(id => { 
            if(document.getElementById(id)) new TomSelect('#' + id, configTomSelect); 
        });
        if(document.getElementById('select-satker')) {
            new TomSelect('#select-satker', configTomSelect);
        }
    });

    window.confirmDelete = function(id) {
        Swal.fire({
            title: 'Hapus Data?', text: "Data ini akan dihapus permanen.", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal', reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) { document.getElementById('delete-form-' + id).submit(); }
        });
    }
</script>
@endpush