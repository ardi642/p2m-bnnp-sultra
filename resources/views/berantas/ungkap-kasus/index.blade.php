@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4 p-lg-5">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Data Ungkap Kasus</h1>
                <p class="text-muted mb-0">Daftar kasus, barang bukti, dan tersangka</p>
            </div>
            @if (auth()->user()->hasRole(['operator_satker', 'operator_berantas']))
            <a href="{{ route('berantas.ungkap-kasus.create') }}" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-plus-lg"></i> Tambah Kasus
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
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- FILTER --}}
        @php
            $allFilters = request()->only(['satuan_kerja_id', 'bulan', 'tahun', 'search', 'kategori_bb', 'narkotika_ids', 'search_non_narkotika']);
            if (empty($allFilters['tahun'])) { $allFilters['tahun'] = [date('Y')]; }
            $activeFilters = collect($allFilters)->filter(function($value) { return !empty($value) && ($value !== ['']); })->count();
            
            $sortLink = function($col, $label) {
                $currentCol = request('sort_by', 'created_at'); 
                $currentOrder = request('sort_order', 'desc'); 
                $newOrder = ($currentCol === $col && $currentOrder === 'desc') ? 'asc' : 'desc';
                $icon = 'bi-arrow-down-up text-muted opacity-25';
                if ($currentCol === $col) { $icon = $currentOrder === 'desc' ? 'bi-sort-down text-primary' : 'bi-sort-up text-primary'; }
                $url = request()->fullUrlWithQuery(['sort_by' => $col, 'sort_order' => $newOrder]);
                return '<a href="'.$url.'" class="text-decoration-none text-secondary fw-bold d-flex align-items-center justify-content-between gap-2">'.$label.' <i class="bi '.$icon.'"></i></a>';
            };
            $formatAngka = function($nilai) { return str_replace('.', ',', (string)(float)$nilai); };
        @endphp

        <div class="row justify-content-center mb-5" x-data="{ showFilter: true }">
            <div class="col-12">
                <div class="card border-0 shadow-sm"> 
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
                            <h5 class="card-title mb-0 fw-bold text-secondary"><i class="bi bi-table me-2"></i>Data Kasus</h5>
                            <button type="button" @click="showFilter = !showFilter" class="btn btn-sm transition-all d-flex align-items-center gap-2" :class="showFilter ? 'btn-light text-secondary border' : 'btn-primary shadow-sm'">
                                <i class="bi" :class="showFilter ? 'bi-chevron-up' : 'bi-funnel'"></i> 
                                <span x-text="showFilter ? 'Sembunyikan Filter' : 'Filter Pencarian'"></span>
                                @if($activeFilters > 0) <span class="badge bg-danger rounded-pill">{{ $activeFilters }}</span> @endif
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0 p-lg-4">
                        
                        {{-- FORM FILTER --}}
                        <form action="{{ route('berantas.ungkap-kasus.index') }}" method="GET">
                            <input type="hidden" name="sort_by" value="{{ request('sort_by', 'created_at') }}">
                            <input type="hidden" name="sort_order" value="{{ request('sort_order', 'desc') }}">
                            
                            <div x-show="showFilter" x-transition class="mb-4 px-3 px-lg-0 pt-3 pt-lg-0" x-data="fileDownloader">
                                <div class="bg-body-tertiary p-4 rounded-3 border">
                                    <div class="row g-3 text-start">
                                        {{-- KEYWORD SEARCH --}}
                                        <div class="col-12">
                                            <label class="form-label fw-bold small text-secondary text-uppercase">Kata Kunci</label>
                                            <div class="input-group shadow-sm">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                                <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari No LKN, Nama Tersangka, TKP..." value="{{ request('search') }}">
                                            </div>
                                        </div>

                                        {{-- ADMIN ONLY: SATKER --}}
                                        @if (Auth::user()->hasRole('admin'))
                                            <div class="col-lg-6">
                                                <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Satuan Kerja</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-satker" name="satuan_kerja_id[]" multiple placeholder="Pilih Satuan Kerja..." autocomplete="off">
                                                        @foreach($satuanKerjas as $satker)
                                                            <option value="{{ $satker->id }}" {{ in_array($satker->id, request('satuan_kerja_id', [])) ? 'selected' : '' }}>{{ $satker->satuan_kerja }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- KATEGORI BB --}}
                                        <div class="col-md-6 {{ Auth::user()->hasRole('admin') ? 'col-lg-6' : 'col-lg-4' }}">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Kategori Barang Bukti</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-kategori-bb" name="kategori_bb[]" multiple placeholder="Kategori..." autocomplete="off">
                                                    <option value="Narkotika" {{ in_array('Narkotika', request('kategori_bb', [])) ? 'selected' : '' }}>Narkotika</option>
                                                    <option value="Non-Narkotika" {{ in_array('Non-Narkotika', request('kategori_bb', [])) ? 'selected' : '' }}>Non-Narkotika</option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- BULAN & TAHUN --}}
                                        <div class="col-6 {{ Auth::user()->hasRole('admin') ? 'col-lg-6' : 'col-lg-4' }}">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Bulan</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-bulan" name="bulan[]" multiple placeholder="Pilih Bulan..." autocomplete="off">
                                                    @foreach(range(1, 12) as $m) <option value="{{ $m }}" {{ in_array($m, request('bulan', [])) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option> @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-6 {{ Auth::user()->hasRole('admin') ? 'col-lg-6' : 'col-lg-4' }}">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Tahun</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-tahun" name="tahun[]" multiple placeholder="Pilih Tahun..." autocomplete="off">
                                                    @foreach($years as $year) <option value="{{ $year }}" {{ in_array($year, request('tahun', [date('Y')])) ? 'selected' : '' }}>{{ $year }}</option> @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- CONDITIONAL INPUTS --}}
                                        <div class="col-lg-6" id="wrapper-narkotika" style="display: none;">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Jenis Narkotika</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-narkotika" name="narkotika_ids[]" multiple placeholder="Pilih Narkotika..." autocomplete="off">
                                                    @foreach($masterNarkotika as $n)
                                                        <option value="{{ $n->id }}" {{ in_array($n->id, request('narkotika_ids', [])) ? 'selected' : '' }}>{{ $n->nama_narkotika }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-6" id="wrapper-non-narkotika" style="display: none;">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Nama Barang (Non-Narkotika)</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-non-narkotika" name="search_non_narkotika[]" multiple placeholder="Ketik nama barang..." autocomplete="off">
                                                    @if(request('search_non_narkotika'))
                                                        @foreach(request('search_non_narkotika') as $val) <option value="{{ $val }}" selected>{{ $val }}</option> @endforeach 
                                                    @endif
                                                </select>
                                            </div>
                                        </div>

                                        {{-- ACTIONS --}}
                                        <div class="col-12 text-end pt-3 border-top mt-4 text-start">
                                            <a href="{{ route('berantas.ungkap-kasus.index') }}" class="btn btn-link text-decoration-none text-muted btn-sm me-2">Reset Filter</a>
                                            <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-funnel-fill me-1"></i> Terapkan</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- STATS BAR --}}
                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-end align-items-lg-center mb-3 px-3 px-lg-0">
                                <div class="mb-2 mb-lg-0">
                                    <button type="submit" formaction="{{ route('berantas.ungkap-kasus.export') }}" class="btn btn-success btn-sm text-white d-flex align-items-center gap-2 px-3 shadow-none">
                                        <i class="bi bi-file-earmark-excel"></i> <span>Export Excel</span>
                                    </button>
                                </div>
                                <div class="d-flex flex-wrap justify-content-end gap-2">
                                    <div class="d-flex align-items-center border border-secondary-subtle rounded-3 px-3 py-1 bg-light">
                                        <i class="bi bi-briefcase text-muted me-2" style="font-size: 0.8rem;"></i>
                                        <span class="text-muted" style="font-size: 0.85rem;">Total kasus:</span>
                                        <span class="text-dark ms-1" style="font-size: 0.85rem;">{{ number_format($totalKasus, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex align-items-center border border-secondary-subtle rounded-3 px-3 py-1 bg-light">
                                        <i class="bi bi-people text-muted me-2" style="font-size: 0.8rem;"></i>
                                        <span class="text-muted" style="font-size: 0.85rem;">Total tersangka : </span>
                                        <span class="text-dark ms-1" style="font-size: 0.85rem;">{{ number_format($totalTersangka, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex align-items-center border border-secondary-subtle rounded-3 px-3 py-1 bg-light">
                                        <i class="bi bi-boxes text-muted me-2" style="font-size: 0.8rem;"></i>
                                        <span class="text-muted" style="font-size: 0.85rem;">Total BB Narkotika: </span>
                                        <span class="text-dark ms-1" style="font-size: 0.85rem;">{{ number_format($totalBBNarkotika, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex align-items-center border border-secondary-subtle rounded-3 px-3 py-1 bg-light">
                                        <i class="bi bi-speedometer2 text-muted me-2" style="font-size: 0.8rem;"></i>
                                        <span class="text-muted" style="font-size: 0.85rem;">Total Berat: </span>
                                        <span class="text-dark ms-1" style="font-size: 0.85rem;">{{ number_format($totalBeratGram, 2, ',', '.') }} Gram</span>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        {{-- TABLE DATA --}}
                        <div class="custom-table-scroll mb-3" id="data-table">
                            <table class="table table-hover align-middle mb-0" x-data="{ expanded: [] }">
                                <thead class="bg-light sticky-top">
                                    <tr class="text-center align-middle small text-uppercase text-secondary text-nowrap">
                                        <th class="py-3 bg-light ps-3">No</th>
                                        <th class="py-3 bg-light text-start">{!! $sortLink('satuan_kerja', 'Satuan Kerja') !!}</th>
                                        <th class="py-3 bg-light text-start">{!! $sortLink('nomor_lkn', 'No. LKN') !!}</th>
                                        <th class="py-3 bg-light text-start">{!! $sortLink('tanggal_kejadian', 'Tanggal Kejadian') !!}</th>
                                        <th class="py-3 bg-light text-start" style="min-width: 250px;">Tersangka (Pemilik)</th>
                                        <th class="py-3 bg-light text-start" style="min-width: 250px;">Barang Bukti</th>
                                        <th class="py-3 bg-light">{!! $sortLink('created_at', 'Dibuat') !!}</th>
                                        <th class="py-3 bg-light pe-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                    @forelse ($kasus as $item)
                                        @php
                                            // Kelompokkan barang bukti berdasarkan pemilik (ID)
                                            $evidenceGroups = $item->barangBukti->groupBy(function($bb) { 
                                                return $bb->tersangka->pluck('id')->sort()->values()->implode('-'); 
                                            });
                                            
                                            // HITUNG FREKUENSI TERSANGKA (Untuk Badge "Sama")
                                            $allVisualOwners = $evidenceGroups->map(function($group) {
                                                return $group->first()->tersangka->pluck('id');
                                            })->flatten();
                                            $ownerFrequency = $allVisualOwners->countBy();

                                            $suspectsWithEvidenceIds = $item->barangBukti->flatMap->tersangka->pluck('id')->unique()->toArray();
                                            $orphanSuspects = $item->tersangka->whereNotIn('id', $suspectsWithEvidenceIds);
                                            $showLabel = ($evidenceGroups->count() > 1);
                                        @endphp
                                        
                                        {{-- ROW UTAMA --}}
                                        <tr class="align-top" :class="expanded.includes({{ $item->id }}) ? 'bg-light' : ''">
                                            <td class="text-center fw-bold text-secondary ps-3 py-3">{{ $kasus->firstItem() + $loop->index }}</td>
                                            <td class="text-start py-3"><span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-normal shadow-sm text-wrap text-start">{{ $item->satuanKerja->satuan_kerja ?? '-' }}</span></td>
                                            <td class="text-start py-3"><a href="#" class="text-decoration-none fw-bold text-dark" @click.prevent="expanded.includes({{ $item->id }}) ? expanded = expanded.filter(id => id !== {{ $item->id }}) : expanded.push({{ $item->id }})">{{ $item->nomor_lkn }}</a></td>
                                            <td class="text-start py-3"><span class="small text-muted text-nowrap">{{ $item->tanggal_kejadian->locale('id')->translatedFormat('d M Y') }}</span></td>
                                            
                                            {{-- KOLOM TERSANGKA --}}
                                            <td class="text-start p-0 align-top"> {{-- HAPUS height:1px AGAR BISA MEMANJANG --}}
                                                <div class="d-flex flex-column h-100">
                                                    @foreach($evidenceGroups as $signature => $evidenceList)
                                                        @php $owners = $evidenceList->first()->tersangka; $hasBorder = !($loop->last && $orphanSuspects->isEmpty()); @endphp
                                                        <div class="p-2 {{ $hasBorder ? 'border-bottom' : '' }} d-flex flex-column gap-2 justify-content-center flex-grow-1" style="min-height: 60px;">
                                                            @if($showLabel)<div class="fw-bold text-secondary" style="font-size: 0.65rem; opacity: 0.7;">#{{ $loop->iteration }}</div>@endif
                                                            
                                                            <div class="d-flex flex-column gap-1">
                                                                @foreach($owners as $t)
                                                                    <div class="d-flex flex-column bg-white p-2 rounded border shadow-sm w-100">
                                                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                                                            <div class="d-flex align-items-center text-truncate">
                                                                                <i class="bi bi-person-fill text-secondary me-2"></i>
                                                                                <span class="small fw-bold text-dark text-truncate" title="{{ $t->nama_tersangka }}">{{ $t->nama_tersangka }}</span>
                                                                                
                                                                                {{-- BADGE PENANDA ORANG SAMA --}}
                                                                                @if(isset($ownerFrequency[$t->id]) && $ownerFrequency[$t->id] > 1)
                                                                                    <span class="badge bg-warning text-dark border border-warning ms-2 py-0 px-1 d-inline-flex align-items-center" style="font-size: 0.65rem;" title="Tersangka ini muncul di kelompok lain">
                                                                                        <i class="bi bi-link-45deg me-1"></i>(Tersangka Sama)
                                                                                    </span>
                                                                                @endif
                                                                            </div>
                                                                            <span class="badge bg-light text-secondary border ms-1" style="font-size: 0.65rem;">{{ $t->jenis_kelamin == 'Laki-Laki' ? 'L' : 'P' }}</span>
                                                                        </div>
                                                                        <div class="d-flex align-items-center gap-2 small text-muted border-top pt-1 mt-1" style="font-size: 0.7rem;">
                                                                            <div class="d-flex align-items-center text-nowrap"><i class="bi bi-briefcase me-1"></i> {{ $t->pekerjaan ?? '-' }}</div>
                                                                            <div class="vr"></div>
                                                                            <div class="d-flex align-items-center text-nowrap"><i class="bi bi-flag me-1"></i> {{ $t->tahap ?? '-' }}</div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                    
                                                    @if($orphanSuspects->count() > 0)
                                                        <div class="p-2 d-flex flex-column gap-2 justify-content-center flex-grow-1" style="min-height: 60px;">
                                                            @foreach($orphanSuspects as $t)
                                                                <div class="d-flex align-items-center bg-danger-subtle p-1 rounded border border-danger-subtle shadow-sm" style="width: fit-content;">
                                                                    <i class="bi bi-person-exclamation text-danger mx-1 small"></i>
                                                                    <div><span class="small fw-bold text-dark">{{ $t->nama_tersangka }}</span><span class="text-danger ms-1 small" style="font-size: 0.7rem;">(Tanpa BB)</span></div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>

                                            {{-- KOLOM BARANG BUKTI --}}
                                            <td class="text-start p-0 align-top"> {{-- HAPUS height:1px --}}
                                                <div class="d-flex flex-column h-100">
                                                    @foreach($evidenceGroups as $signature => $evidenceList)
                                                        @php $hasBorder = !($loop->last && $orphanSuspects->isEmpty()); @endphp
                                                        <div class="p-2 {{ $hasBorder ? 'border-bottom' : '' }} d-flex flex-column gap-1 justify-content-center flex-grow-1" style="min-height: 60px;">
                                                            @if($showLabel)<div class="fw-bold text-secondary" style="font-size: 0.65rem; opacity: 0.7;">#{{ $loop->iteration }}</div>@endif
                                                            @foreach($evidenceList as $bb)
                                                                <div class="small d-flex align-items-center">
                                                                    @if($bb->kategori === 'Narkotika') <i class="bi bi-capsule text-danger me-2" title="Narkotika"></i> @else <i class="bi bi-box-seam text-success me-2" title="Non-Narkotika"></i> @endif
                                                                    <span class="text-dark me-1 fw-semibold">{{ $bb->nama_barang_non_narkotika ?? $bb->narkotika->nama_narkotika }}</span>
                                                                    <span class="text-muted">({{ $formatAngka($bb->kuantitas) }} {{ $bb->satuan_narkotika ?? $bb->satuan_non_narkotika }})</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>

                                            <td class="text-center small py-3"><div class="text-dark">{{ $item->created_at->format('d/m/Y') }}</div><div class="text-muted">{{ $item->created_at->format('H:i') }} WIB</div></td>
                                            
                                            {{-- AKSI --}}
                                            <td class="text-center pe-3 py-3">
                                                <div class="btn-group btn-group-sm shadow-sm">
                                                    <button type="button" class="btn btn-light border border-secondary-subtle" @click="expanded.includes({{ $item->id }}) ? expanded = expanded.filter(id => id !== {{ $item->id }}) : expanded.push({{ $item->id }})" title="Lihat Detail">
                                                        <i class="bi transition-all" :class="expanded.includes({{ $item->id }}) ? 'bi-chevron-up text-primary' : 'bi-eye text-secondary'"></i>
                                                    </button>
                                                    @if (auth()->user()->hasRole(['operator_satker', 'operator_berantas']))
                                                        <a href="{{ route('berantas.ungkap-kasus.edit', $item->id) }}" class="btn btn-light border text-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                        <button type="button" class="btn btn-light border text-danger" onclick="confirmDelete({{ $item->id }})" title="Hapus"><i class="bi bi-trash"></i></button>
                                                        <form id="delete-form-{{ $item->id }}" action="{{ route('berantas.ungkap-kasus.destroy', $item->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>

                                        {{-- DETAIL EXPANDABLE --}}
                                        <tr x-show="expanded.includes({{ $item->id }})" x-transition x-data="fileDownloader">
                                            <td colspan="8" class="p-0 border-0">
                                                <div class="bg-body-tertiary p-4 border-bottom shadow-inner text-start">
                                                    <div class="card border-0 shadow-sm">
                                                        <div class="card-body">
                                                            <h6 class="card-title fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-info-circle me-2"></i>Detail Kasus Lengkap</h6>
                                                            
                                                            {{-- INFORMASI UTAMA TANPA KOTAK --}}
                                                            <div class="row g-4 text-start mb-4">
                                                                <div class="col-md-3">
                                                                    <div class="small text-secondary text-uppercase fw-bold mb-1">Satuan Kerja</div>
                                                                    <div class="fw-semibold text-dark">{{ $item->satuanKerja->satuan_kerja ?? '-' }}</div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="small text-secondary text-uppercase fw-bold mb-1">Nomor LKN</div>
                                                                    <div class="fw-semibold text-dark font-monospace">{{ $item->nomor_lkn }}</div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="small text-secondary text-uppercase fw-bold mb-1">Tanggal Kejadian</div>
                                                                    <div class="fw-semibold text-dark">{{ $item->tanggal_kejadian->locale('id')->translatedFormat('l, d F Y') }}</div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="small text-secondary text-uppercase fw-bold mb-1">Lokasi TKP</div>
                                                                    {{-- ALAMAT DI ATAS & TEBAL --}}
                                                                    <div class="fw-semibold text-dark mb-1" style="line-height: 1.4;">{{ $item->alamat_tkp }}</div>
                                                                    
                                                                    {{-- LAT/LONG (ABU-ABU) --}}
                                                                    <div class="text-secondary small font-monospace mb-2">
                                                                        <i class="bi bi-geo-alt me-1"></i>{{ $item->latitude ?? '-' }}, {{ $item->longitude ?? '-' }}
                                                                    </div>

                                                                    {{-- TOMBOL MAPS (BARIS BARU) --}}
                                                                    @if($item->latitude && $item->longitude)
                                                                        <div>
                                                                            <a href="https://www.google.com/maps/search/?api=1&query={{ $item->latitude }},{{ $item->longitude }}" target="_blank" class="btn btn-xs btn-outline-primary shadow-sm">
                                                                                <i class="bi bi-map-fill me-1"></i>Buka Google Maps
                                                                            </a>
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="col-12">
                                                                    <div class="small text-secondary text-uppercase fw-bold mb-1">Kronologis Kejadian</div>
                                                                    <div class="text-dark" style="white-space: pre-wrap; line-height: 1.6;">{{ $item->kronologis ?? '-' }}</div>
                                                                </div>

                                                                {{-- DAFTAR TERSANGKA & BB DI DETAIL --}}
                                                                <div class="col-12 mt-4">
                                                                    <div class="small text-secondary text-uppercase fw-bold mb-2 border-bottom pb-2">Rincian Tersangka & Barang Bukti</div>
                                                                    <div class="table-responsive">
                                                                        {{-- TABEL DENGAN PADDING & JENIS KELAMIN --}}
                                                                        <table class="table table-bordered align-middle mb-0">
                                                                            <thead class="bg-light">
                                                                                <tr>
                                                                                    <th class="px-3 py-2">Nama Tersangka</th>
                                                                                    <th class="px-3 py-2">Pekerjaan / Tahap</th>
                                                                                    <th class="px-3 py-2">Barang Bukti</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @foreach($evidenceGroups as $group)
                                                                                    <tr>
                                                                                        <td class="align-top px-3 py-2">
                                                                                            @foreach($group->first()->tersangka as $t)
                                                                                                <div class="mb-2">
                                                                                                    <span class="fw-bold text-dark">{{ $t->nama_tersangka }}</span>
                                                                                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1 fw-normal" style="font-size: 0.65rem;">{{ $t->jenis_kelamin }}</span>
                                                                                                </div>
                                                                                            @endforeach
                                                                                        </td>
                                                                                        <td class="align-top px-3 py-2">
                                                                                            @foreach($group->first()->tersangka as $t)
                                                                                                <div class="mb-2 small text-muted">{{ $t->pekerjaan ?? '-' }} / {{ $t->tahap }}</div>
                                                                                            @endforeach
                                                                                        </td>
                                                                                        <td class="align-top px-3 py-2">
                                                                                            @foreach($group as $bb)
                                                                                                <div class="d-flex align-items-center small mb-1">
                                                                                                    <i class="bi {{ $bb->kategori == 'Narkotika' ? 'bi-capsule text-danger' : 'bi-box-seam text-success' }} me-2"></i>
                                                                                                    <span>{{ $bb->nama_barang }} ({{ $formatAngka($bb->kuantitas) }} {{ $bb->satuan }})</span>
                                                                                                </div>
                                                                                            @endforeach
                                                                                        </td>
                                                                                    </tr>
                                                                                @endforeach
                                                                                {{-- Yg tidak punya BB --}}
                                                                                @foreach($orphanSuspects as $t)
                                                                                    <tr>
                                                                                        <td class="px-3 py-2">
                                                                                            <span class="fw-bold text-danger">{{ $t->nama_tersangka }}</span>
                                                                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1 fw-normal" style="font-size: 0.65rem;">{{ $t->jenis_kelamin }}</span>
                                                                                        </td>
                                                                                        <td class="px-3 py-2 text-muted small">{{ $t->pekerjaan ?? '-' }} / {{ $t->tahap }}</td>
                                                                                        <td class="px-3 py-2 text-muted fst-italic small">Tidak ada barang bukti</td>
                                                                                    </tr>
                                                                                @endforeach
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>

                                                                {{-- METADATA --}}
                                                                <div class="col-md-6 mt-4">
                                                                    <div class="small text-secondary fw-bold text-uppercase mb-1">Dibuat Pada</div>
                                                                    <div class="text-dark small"><i class="bi bi-clock me-1"></i>{{ $item->created_at->locale('id')->translatedFormat('d F Y H:i') }} WIB</div>
                                                                </div>
                                                                <div class="col-md-6 mt-4">
                                                                    <div class="small text-secondary fw-bold text-uppercase mb-1">Terakhir Diubah</div>
                                                                    <div class="text-dark small"><i class="bi bi-pencil-square me-1"></i>{{ $item->updated_at->locale('id')->translatedFormat('d F Y H:i') }} WIB</div>
                                                                </div>
                                                            </div>

                                                            {{-- DOWNLOADER FILE --}}
                                                            <form action="{{ route('dokumen.zip.selected') }}" method="POST" x-ref="formZip">
                                                                @csrf
                                                                <div class="col-12 text-start border-top pt-3">
                                                                    <div class="row g-4">
                                                                        @php
                                                                            $fotos = $item->dokumen->where('kategori', 'dokumentasi');
                                                                            $lampirans = $item->dokumen->where('kategori', 'lampiran');
                                                                            $fotoIds = $fotos->where('is_link', false)->pluck('id')->values()->toArray();
                                                                            $lampiranIds = $lampirans->where('is_link', false)->pluck('id')->values()->toArray();
                                                                        @endphp
                                                                        
                                                                        {{-- DOKUMENTASI --}}
                                                                        <div class="col-lg-6">
                                                                            <div class="card h-100 border bg-light shadow-none">
                                                                                <div class="card-header bg-transparent border-bottom fw-bold text-primary d-flex justify-content-between align-items-center">
                                                                                    <div class="form-check">
                                                                                        @if(count($fotoIds) > 0)<input class="form-check-input cursor-pointer" type="checkbox" @change="toggleAll({{ json_encode($fotoIds) }})" :checked="isAllSelected({{ json_encode($fotoIds) }})">@endif
                                                                                        <label class="form-check-label cursor-pointer select-none"><i class="bi bi-images me-2"></i>Dokumentasi</label>
                                                                                    </div>
                                                                                    <span class="badge bg-primary rounded-pill">{{ $fotos->count() }}</span>
                                                                                </div>
                                                                                <div class="card-body p-2" style="max-height: 250px; overflow-y: auto;">
                                                                                    @forelse($fotos as $doc)
                                                                                        <div class="d-flex align-items-center bg-white border rounded p-2 mb-2 shadow-sm hover-shadow transition-all" :class="isSelected({{ $doc->id }}) ? 'border-primary bg-primary bg-opacity-10' : ''">
                                                                                            @if(!$doc->is_link)<div class="form-check me-2 d-flex align-items-center"><input class="form-check-input shadow-none cursor-pointer" type="checkbox" id="chk-doc-{{ $doc->id }}" name="ids[]" value="{{ $doc->id }}" x-model="selected"></div>@endif
                                                                                            <label for="chk-doc-{{ $doc->id }}" class="flex-grow-1 text-truncate small cursor-pointer d-flex align-items-center m-0">
                                                                                                <div class="flex-shrink-0 me-2 text-primary bg-primary bg-opacity-10 p-1 rounded">@if($doc->is_link) <i class="bi bi-link-45deg"></i> @else <i class="bi bi-file-image"></i> @endif</div>
                                                                                                <span class="text-truncate" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</span>
                                                                                            </label>
                                                                                            <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                                                                                @if(!$doc->is_link) <a href="{{ Storage::disk($doc->disk ?? 'public')->url($doc->path_file) }}" target="_blank" class="btn btn-xs btn-outline-secondary"><i class="bi bi-eye"></i></a> <a href="{{ route('dokumen.download', $doc->id) }}" class="btn btn-xs btn-outline-primary"><i class="bi bi-download"></i></a> @else <a href="{{ $doc->path_url }}" target="_blank" class="btn btn-xs btn-outline-info w-100"><i class="bi bi-box-arrow-up-right me-1"></i>Buka</a> @endif
                                                                                            </div>
                                                                                        </div>
                                                                                    @empty <div class="text-center py-3 text-muted small fst-italic">Tidak ada dokumentasi.</div> @endforelse
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        {{-- LAMPIRAN --}}
                                                                        <div class="col-lg-6">
                                                                            <div class="card h-100 border bg-light shadow-none">
                                                                                <div class="card-header bg-transparent border-bottom fw-bold text-danger d-flex justify-content-between align-items-center">
                                                                                    <div class="form-check">
                                                                                        @if(count($lampiranIds) > 0)<input class="form-check-input cursor-pointer" type="checkbox" @change="toggleAll({{ json_encode($lampiranIds) }})" :checked="isAllSelected({{ json_encode($lampiranIds) }})">@endif
                                                                                        <label class="form-check-label cursor-pointer select-none"><i class="bi bi-paperclip me-2"></i>Lampiran</label>
                                                                                    </div>
                                                                                    <span class="badge bg-danger rounded-pill">{{ $lampirans->count() }}</span>
                                                                                </div>
                                                                                <div class="card-body p-2" style="max-height: 250px; overflow-y: auto;">
                                                                                    @forelse($lampirans as $doc)
                                                                                        <div class="d-flex align-items-center bg-white border rounded p-2 mb-2 shadow-sm hover-shadow transition-all" :class="isSelected({{ $doc->id }}) ? 'border-danger bg-danger bg-opacity-10' : ''">
                                                                                            @if(!$doc->is_link)<div class="form-check me-2 d-flex align-items-center"><input class="form-check-input shadow-none cursor-pointer" type="checkbox" id="chk-lamp-{{ $doc->id }}" name="ids[]" value="{{ $doc->id }}" x-model="selected"></div>@endif
                                                                                            <label for="chk-lamp-{{ $doc->id }}" class="flex-grow-1 text-truncate small cursor-pointer d-flex align-items-center m-0">
                                                                                                <div class="flex-shrink-0 me-2 text-danger bg-danger bg-opacity-10 p-1 rounded">@if($doc->is_link) <i class="bi bi-link-45deg"></i> @elseif(Str::contains($doc->tipe_file, 'pdf')) <i class="bi bi-file-pdf"></i> @else <i class="bi bi-file-earmark-text"></i> @endif</div>
                                                                                                <span class="text-truncate" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</span>
                                                                                            </label>
                                                                                            <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                                                                                @if(!$doc->is_link) <a href="{{ Storage::disk($doc->disk ?? 'public')->url($doc->path_file) }}" target="_blank" class="btn btn-xs btn-outline-secondary"><i class="bi bi-eye"></i></a> <a href="{{ route('dokumen.download', $doc->id) }}" class="btn btn-xs btn-outline-danger"><i class="bi bi-download"></i></a> @else <a href="{{ $doc->path_url }}" target="_blank" class="btn btn-xs btn-outline-info w-100"><i class="bi bi-box-arrow-up-right me-1"></i>Buka</a> @endif
                                                                                            </div>
                                                                                        </div>
                                                                                    @empty <div class="text-center py-3 text-muted small fst-italic">Tidak ada lampiran.</div> @endforelse
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    @php $hasPhysicalFiles = $item->dokumen->where('is_link', false)->count() > 0; @endphp
                                                                    @if($hasPhysicalFiles)
                                                                        <div class="col-12 text-end mt-3">
                                                                            <button type="button" @click="submitDownload" class="btn btn-dark btn-sm px-4 shadow-sm" :disabled="selected.length === 0">
                                                                                <i class="bi bi-file-earmark-zip-fill me-2"></i>Download File Terpilih (.ZIP)
                                                                            </button>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-center py-5 text-muted fst-italic border-bottom">Tidak ada data kasus.</td></tr>
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
                                <div>{{ $kasus->withQueryString()->links() }}</div>
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
    .custom-table-scroll thead th { position: sticky !important; top: 0 !important; z-index: 10; background-color: #f8f9fa !important; box-shadow: inset 0 -1px 0 #dee2e6; }
    .btn-xs { padding: 1px 5px; font-size: 0.75rem; }
    .page-link { border: none; color: #6c757d; border-radius: 50% !important; margin: 0 2px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
    .page-item.active .page-link { background-color: #0d6efd; color: white; box-shadow: 0 2px 4px rgba(13,110,253,0.3); }
</style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener('alpine:init', () => {
        Alpine.data('fileDownloader', () => ({
            selected: [],
            isSelected(id) { return this.selected.includes(id.toString()) || this.selected.includes(id); },
            toggleAll(ids) {
                const stringIds = ids.map(String);
                const allSelected = stringIds.every(id => this.selected.includes(id));
                if (allSelected) { this.selected = this.selected.filter(id => !stringIds.includes(id)); } 
                else { this.selected = [...new Set([...this.selected, ...stringIds])]; }
            },
            isAllSelected(ids) {
                if (ids.length === 0) return false;
                const stringIds = ids.map(String);
                return stringIds.every(id => this.selected.includes(id));
            },
            submitDownload() {
                if (this.selected.length === 0) {
                    Swal.fire({icon: 'warning', title: 'Pilih File', text: 'Silakan centang minimal satu file.', confirmButtonColor: '#0d6efd'});
                    return;
                }
                this.$refs.formZip.submit();
            }
        }));
    });

    document.addEventListener("DOMContentLoaded", function() {
        const configTomSelect = { plugins: ['remove_button', 'clear_button'], persist: false, create: false, maxOptions: null };
        ['select-satker', 'select-bulan', 'select-tahun', 'select-narkotika'].forEach(id => { if(document.getElementById(id)) new TomSelect('#' + id, configTomSelect); });
        
        if(document.getElementById('select-kategori-bb')) {
            const tsKategori = new TomSelect('#select-kategori-bb', { ...configTomSelect, onChange: function() { updateBBVisibility(); } });
            function updateBBVisibility() {
                const selected = tsKategori.getValue(); 
                const wrapperNarkotika = document.getElementById('wrapper-narkotika');
                const wrapperNonNarkotika = document.getElementById('wrapper-non-narkotika');
                if (selected.length === 0) {
                    if(wrapperNarkotika) wrapperNarkotika.style.display = 'none';
                    if(wrapperNonNarkotika) wrapperNonNarkotika.style.display = 'none';
                } else {
                    if(wrapperNarkotika) wrapperNarkotika.style.display = selected.includes('Narkotika') ? 'block' : 'none';
                    if(wrapperNonNarkotika) wrapperNonNarkotika.style.display = selected.includes('Non-Narkotika') ? 'block' : 'none';
                }
            }
            updateBBVisibility();
        }
        
        if(document.getElementById('select-non-narkotika')) {
             new TomSelect('#select-non-narkotika', { plugins: ['remove_button', 'clear_button'], create: true, persist: false, createOnBlur: true, maxOptions: null });
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