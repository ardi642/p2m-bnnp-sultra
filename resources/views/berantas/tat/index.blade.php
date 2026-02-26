@extends('admin')

@section('content')
<main class="admin-main" x-data="{ showFilter: true, expanded: [] }">
    <div class="container-fluid p-4 p-lg-5">
        
        {{-- HEADER TITLE --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Data TAT</h1>
                <p class="text-muted mb-0">Kelola Data Tim Asesmen Terpadu</p>
            </div>
            @if (auth()->user()->hasRole(['operator_satker', 'operator_berantas']))
            <a href="{{ route('berantas.tat.create') }}" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-plus-lg"></i> Tambah Data
            </a>
            @endif
        </div>

        {{-- ALERT NOTIFIKASI --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><strong>Berhasil!</strong> {{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><strong>Gagal!</strong> {{ session('error') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- INFO LINTAS SATKER JIKA FILTER PENCARIAN GLOBAL AKTIF --}}
        @if(request()->filled('filter_nik') || request()->filled('filter_no_telepon'))
            <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm mb-4 d-flex align-items-center" role="alert">
                <i class="bi bi-info-circle-fill fs-3 me-3 text-info"></i>
                <div>
                    <h6 class="alert-heading fw-bold mb-1">Pencarian Seluruh Satuan kerja Aktif</h6>
                    <span class="small">Karena Anda menggunakan filter NIK atau Nomor Telepon, sistem otomatis menampilkan hasil pencarian riwayat tersangka di <strong>Seluruh Satuan Kerja</strong></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- FILTER & SORTING --}}
        @php
            $allFilters = request()->only([
                'search', 'bulan', 'tahun', 'satuan_kerja_id', 
                'kategori_bb', 'narkotika_ids', 'search_non_narkotika',
                'filter_nik', 'filter_no_telepon'
            ]);
            if (empty($allFilters['tahun'])) { $allFilters['tahun'] = [date('Y')]; }
            
            $activeFilters = collect($allFilters)->filter(function($value) { 
                return !empty($value) && ($value !== ['']); 
            })->count();
            
            $sortLink = function($col, $label) {
                $currentCol = request('sort_by', 'created_at'); 
                $currentOrder = request('sort_order', 'desc');
                $newOrder = ($currentCol === $col && $currentOrder === 'desc') ? 'asc' : 'desc';
                
                $icon = 'bi-arrow-down-up text-muted opacity-25';
                if ($currentCol === $col) {
                    $icon = $currentOrder === 'desc' ? 'bi-sort-down text-primary' : 'bi-sort-up text-primary';
                }
                
                $url = request()->fullUrlWithQuery(['sort_by' => $col, 'sort_order' => $newOrder]);
                return '<a href="'.$url.'" class="text-decoration-none text-secondary fw-bold d-flex align-items-center justify-content-between gap-2">'.$label.' <i class="bi '.$icon.'"></i></a>';
            };
            $formatAngka = function($nilai) { return str_replace('.', ',', (string)(float)$nilai); };
        @endphp

        <div class="row justify-content-center mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm"> 
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
                            <h5 class="card-title mb-0 fw-bold text-secondary"><i class="bi bi-table me-2"></i>Data Kasus TAT</h5>
                            <button type="button" @click="showFilter = !showFilter" 
                                class="btn btn-sm transition-all d-flex align-items-center gap-2"
                                :class="showFilter ? 'btn-light text-secondary border' : 'btn-primary shadow-sm'">
                                <i class="bi" :class="showFilter ? 'bi-chevron-up' : 'bi-funnel'"></i> 
                                <span x-text="showFilter ? 'Sembunyikan Filter' : 'Filter Pencarian'"></span>
                                @if($activeFilters > 0)
                                    <span class="badge bg-danger rounded-pill">{{ $activeFilters }}</span>
                                @endif
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0 p-lg-4">
                        
                        {{-- FORM FILTER --}}
                        <form action="{{ route('berantas.tat.index') }}" method="GET">
                            <input type="hidden" name="sort_by" value="{{ request('sort_by', 'created_at') }}">
                            <input type="hidden" name="sort_order" value="{{ request('sort_order', 'desc') }}">
                            
                            <div x-show="showFilter" x-transition class="mb-4 px-3 px-lg-0 pt-3 pt-lg-0" x-data="fileDownloader">
                                <div class="bg-body-tertiary p-4 rounded-3 border">
                                    <div class="row g-3 text-start">
                                        
                                        <div class="col-12">
                                            <label class="form-label fw-bold small text-secondary text-uppercase">Kata Kunci Umum</label>
                                            <div class="input-group shadow-sm">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                                <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari No Register, Nama Tersangka..." value="{{ request('search') }}">
                                            </div>
                                        </div>


                                        @if(Auth::user()->hasRole('admin'))
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

                                        <div class="col-md-6 {{ Auth::user()->hasRole('admin') ? 'col-lg-6' : 'col-lg-4' }}">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Kategori Barang Bukti</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-kategori-bb" name="kategori_bb[]" multiple placeholder="Kategori..." autocomplete="off">
                                                    <option value="Narkotika" {{ in_array('Narkotika', request('kategori_bb', [])) ? 'selected' : '' }}>Narkotika</option>
                                                    <option value="Non-Narkotika" {{ in_array('Non-Narkotika', request('kategori_bb', [])) ? 'selected' : '' }}>Non-Narkotika</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-6 {{ Auth::user()->hasRole('admin') ? 'col-lg-6' : 'col-lg-4' }}">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Bulan</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-bulan" name="bulan[]" multiple placeholder="Pilih Bulan..." autocomplete="off">
                                                    @foreach(range(1, 12) as $m) 
                                                        <option value="{{ $m }}" {{ in_array($m, request('bulan', [])) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option> 
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-6 {{ Auth::user()->hasRole('admin') ? 'col-lg-6' : 'col-lg-4' }}">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Tahun</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-tahun" name="tahun[]" multiple placeholder="Pilih Tahun..." autocomplete="off">
                                                    @foreach($years as $year) 
                                                        <option value="{{ $year }}" {{ in_array($year, request('tahun', [date('Y')])) ? 'selected' : '' }}>{{ $year }}</option> 
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

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

                                        {{-- PENCARIAN TERSANGKA SPESIFIK (LINTAS SATKER) --}}
                                        <div class="col-lg-4">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">NIK (Tersangka)</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-nik" name="filter_nik[]" multiple placeholder="NIK" autocomplete="off">
                                                    @if(request('filter_nik'))
                                                        @foreach(request('filter_nik') as $nik) 
                                                            <option value="{{ $nik }}" selected>{{ $nik }}</option> 
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-4">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">No. Telepon (Tersangka)</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-nohp" name="filter_no_telepon[]" multiple placeholder="No HP" autocomplete="off">
                                                    @if(request('filter_no_telepon'))
                                                        @foreach(request('filter_no_telepon') as $hp) 
                                                            <option value="{{ $hp }}" selected>{{ $hp }}</option> 
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-12 text-end pt-3 border-top mt-4 text-start">
                                            <a href="{{ route('berantas.tat.index') }}" class="btn btn-link text-decoration-none text-muted btn-sm me-2">Reset Filter</a>
                                            <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-funnel-fill me-1"></i> Terapkan</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- STATS BAR --}}
                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-end align-items-lg-center mb-3 px-3 px-lg-0">
                                <div class="mb-2 mb-lg-0">
                                    <button type="submit" formaction="{{ route('berantas.tat.export') }}" class="btn btn-success btn-sm text-white d-flex align-items-center gap-2 px-3 shadow-none">
                                        <i class="bi bi-file-earmark-excel"></i> <span>Export Excel</span>
                                    </button>
                                </div>
                                <div class="d-flex flex-wrap justify-content-end gap-2">
                                    <div class="d-flex align-items-center border border-secondary-subtle rounded-3 px-3 py-1 bg-light">
                                        <i class="bi bi-briefcase text-muted me-2" style="font-size: 0.8rem;"></i>
                                        <span class="text-muted" style="font-size: 0.85rem;">Total kasus : </span>
                                        <span class="text-dark ms-1" style="font-size: 0.85rem;">{{ number_format($totalKasus, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex align-items-center border border-secondary-subtle rounded-3 px-3 py-1 bg-light">
                                        <i class="bi bi-people text-muted me-2" style="font-size: 0.8rem;"></i>
                                        <span class="text-muted" style="font-size: 0.85rem;">Total tersangka : </span>
                                        <span class="text-dark ms-1" style="font-size: 0.85rem;">{{ number_format($totalTersangka, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex align-items-center border border-secondary-subtle rounded-3 px-3 py-1 bg-light">
                                        <i class="bi bi-boxes text-muted me-2" style="font-size: 0.8rem;"></i>
                                        <span class="text-muted" style="font-size: 0.85rem;">Total barang bukti narkotika : </span>
                                        <span class="text-dark ms-1" style="font-size: 0.85rem;">{{ number_format($totalBBNarkotika, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="d-flex align-items-center border border-secondary-subtle rounded-3 px-3 py-1 bg-light">
                                        <i class="bi bi-speedometer2 text-muted me-2" style="font-size: 0.8rem;"></i>
                                        <span class="text-muted" style="font-size: 0.85rem;">Total berat narkotika : </span>
                                        <span class="text-dark ms-1" style="font-size: 0.85rem;">{{ number_format($totalBeratGram, 2, ',', '.') }} Gram</span>
                                    </div>
                                </div>
                            </div>
                        </form>
                        
                        {{-- TABEL DATA --}}
                        <div class="custom-table-scroll mb-3" id="data-table">
                            <table class="table table-hover align-middle mb-0" x-data="{ expanded: [] }" style="min-width: 1000px;">
                                <thead class="bg-light sticky-top">
                                    <tr class="text-center align-middle small text-uppercase text-secondary text-nowrap">
                                        <th class="py-3 bg-light ps-3">No</th>
                                        <th class="py-3 bg-light text-start">{!! $sortLink('satuan_kerja_id', 'Satuan Kerja') !!}</th>
                                        <th class="py-3 bg-light text-start">{!! $sortLink('no_register', 'No. Register') !!}</th>
                                        <th class="py-3 bg-light text-start">{!! $sortLink('tanggal_pelaksanaan', 'Tanggal Pelaksanaan') !!}</th>
                                        <th class="py-3 bg-light text-start">Tersangka</th>
                                        <th class="py-3 bg-light text-start">Barang Bukti</th>
                                        <th class="py-3 bg-light">{!! $sortLink('created_at', 'Dibuat') !!}</th>
                                        <th class="py-3 bg-light pe-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                    @forelse ($data as $row)
                                        <tr class="align-top" :class="expanded.includes({{ $row->id }}) ? 'bg-light' : ''">
                                            <td class="text-center fw-bold text-secondary ps-3 py-3">{{ $data->firstItem() + $loop->index }}</td>
                                            <td class="text-start py-3"><span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-normal shadow-sm text-wrap text-start">{{ $row->satuanKerja->satuan_kerja ?? '-' }}</span></td>
                                            <td class="text-start py-3"><a href="#" class="text-decoration-none fw-bold text-dark" @click.prevent="expanded.includes({{ $row->id }}) ? expanded = expanded.filter(id => id !== {{ $row->id }}) : expanded.push({{ $row->id }})">{{ $row->no_register }}</a></td>
                                            <td class="text-start py-3"><span class="small text-muted text-nowrap">{{ $row->tanggal_pelaksanaan->locale('id')->translatedFormat('d M Y') }}</span></td>
                                            
                                            <td class="text-start py-3">
                                                <div class="d-flex flex-column gap-2">
                                                    @forelse($row->tersangka as $t)
                                                        <div class="d-flex align-items-center bg-white p-1 rounded border shadow-sm" style="width: fit-content;">
                                                            <i class="bi bi-person-fill text-secondary mx-1 small"></i>
                                                            <div>
                                                                <span class="small fw-bold text-dark">{{ $t->nama_tersangka }}</span>
                                                                <span class="text-muted ms-1 small" style="font-size: 0.7rem;">({{ $t->jenis_kelamin }})</span>
                                                            </div>
                                                        </div>
                                                    @empty 
                                                        <span class="text-muted small fst-italic">-</span> 
                                                    @endforelse
                                                </div>
                                            </td>

                                            <td class="text-start py-3">
                                                <div class="d-flex flex-column gap-1">
                                                    @forelse($row->barangBukti as $bb)
                                                        <div class="small d-flex align-items-center">
                                                            @if($bb->kategori === 'Narkotika') 
                                                                <i class="bi bi-capsule text-danger me-2" title="Narkotika"></i> 
                                                            @else 
                                                                <i class="bi bi-box-seam text-success me-2" title="Non-Narkotika"></i> 
                                                            @endif
                                                            <span class="text-dark me-1 fw-semibold">{{ $bb->nama_barang }}</span>
                                                            <span class="text-muted">({{ $formatAngka($bb->kuantitas) }} {{ $bb->satuan }})</span>
                                                        </div>
                                                    @empty 
                                                        <span class="text-muted small fst-italic">-</span> 
                                                    @endforelse
                                                </div>
                                            </td>

                                            <td class="text-center small py-3">
                                                <div class="text-dark">{{ $row->created_at->format('d/m/Y') }}</div>
                                                <div class="text-muted">{{ $row->created_at->format('H:i') }} WIB</div>
                                            </td>
                                            
                                            <td class="text-center pe-3 py-3">
                                                <div class="btn-group btn-group-sm shadow-sm">
                                                    <button type="button" class="btn btn-light border border-secondary-subtle" @click="expanded.includes({{ $row->id }}) ? expanded = expanded.filter(id => id !== {{ $row->id }}) : expanded.push({{ $row->id }})" title="Lihat Detail">
                                                        <i class="bi transition-all" :class="expanded.includes({{ $row->id }}) ? 'bi-chevron-up text-primary' : 'bi-eye text-secondary'"></i>
                                                    </button>
                                                    @if (auth()->user()->hasRole(['operator_satker', 'operator_berantas']))
                                                        <a href="{{ route('berantas.tat.edit', $row->id) }}" class="btn btn-light border border-secondary-subtle text-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                        <button type="button" class="btn btn-light border border-secondary-subtle text-danger" onclick="confirmDelete({{ $row->id }})" title="Hapus"><i class="bi bi-trash"></i></button>
                                                        <form id="delete-form-{{ $row->id }}" action="{{ route('berantas.tat.destroy', $row->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>

                                        {{-- DETAIL EXPANDABLE --}}
                                        <tr x-show="expanded.includes({{ $row->id }})" x-transition x-data="fileDownloader">
                                            <td colspan="8" class="p-0 border-0">
                                                <div class="bg-body-tertiary p-4 border-bottom shadow-inner text-start">
                                                    <div class="card border-0 shadow-sm">
                                                        <div class="card-body">
                                                            <h6 class="card-title fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-info-circle me-2"></i>Detail TAT Lengkap</h6>
                                                            
                                                            {{-- BAGIAN 1: DATA UTAMA --}}
                                                            <div class="row g-4 text-start mb-4">
                                                                <div class="col-md-3">
                                                                    <div class="small text-secondary text-uppercase fw-bold mb-1">Satuan Kerja</div>
                                                                    <div class="fw-semibold text-dark">{{ $row->satuanKerja->satuan_kerja ?? '-' }}</div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="small text-secondary text-uppercase fw-bold mb-1">No. Register</div>
                                                                    <div class="fw-semibold text-dark font-monospace">{{ $row->no_register }}</div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="small text-secondary text-uppercase fw-bold mb-1">Tgl Pelaksanaan</div>
                                                                    <div class="fw-semibold text-dark">{{ $row->tanggal_pelaksanaan->locale('id')->translatedFormat('l, d F Y') }}</div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="small text-secondary text-uppercase fw-bold mb-1">Instansi Pengirim</div>
                                                                    <div class="fw-semibold text-dark">{{ $row->instansi_pengirim ?? '-' }}</div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="small text-secondary text-uppercase fw-bold mb-1">Tgl Penangkapan</div>
                                                                    <div class="text-dark">{{ $row->tanggal_penangkapan ? $row->tanggal_penangkapan->locale('id')->translatedFormat('d F Y') : '-' }}</div>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <div class="small text-secondary text-uppercase fw-bold mb-1">Tgl Permohonan</div>
                                                                    <div class="text-dark">{{ $row->tanggal_permohonan ? $row->tanggal_permohonan->locale('id')->translatedFormat('d F Y') : '-' }}</div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="small text-secondary text-uppercase fw-bold mb-1">Lembaga Rehab</div>
                                                                    <div class="text-dark fw-bold">{{ $row->lembaga_rehab ?? '-' }}</div>
                                                                </div>
                                                                <div class="col-12">
                                                                    <div class="small text-secondary text-uppercase fw-bold mb-1">Pasal Disangkakan</div>
                                                                    <div class="text-dark" style="white-space: pre-wrap; line-height: 1.6;">{{ $row->pasal_disangkakan ?? '-' }}</div>
                                                                </div>
                                                            </div>

                                                            {{-- BAGIAN 2: DATA TERSANGKA --}}
                                                            <div class="col-12 mt-4">
                                                                <div class="small text-secondary text-uppercase fw-bold mb-2 border-bottom pb-2">Data Tersangka</div>
                                                                <div class="table-responsive">
                                                                    <table class="table table-bordered align-middle mb-0">
                                                                        <thead class="bg-light">
                                                                            <tr>
                                                                                <th class="p-3" style="width: 25%;">Nama Tersangka</th>
                                                                                <th class="p-3">NIK</th>
                                                                                <th class="p-3">TTL / Usia</th>
                                                                                <th class="p-3">Pekerjaan / Pendidikan</th>
                                                                                <th class="p-3">Kontak</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @forelse($row->tersangka as $t)
                                                                                <tr>
                                                                                    <td class="p-3">
                                                                                        <div class="fw-bold text-dark">{{ $t->nama_tersangka }}</div>
                                                                                        <div class="small text-muted mt-1"><i class="bi bi-gender-ambiguous me-1"></i>{{ $t->jenis_kelamin }}</div>
                                                                                    </td>
                                                                                    <td class="p-3 font-monospace">{{ $t->nik ?? '-' }}</td>
                                                                                    <td class="p-3">{{ $t->usia ? $t->usia . ' Tahun' : '-' }}</td>
                                                                                    <td class="p-3">
                                                                                        <div class="fw-bold text-dark small">{{ $t->pekerjaan ?? '-' }}</div>
                                                                                        <div class="small text-muted">{{ $t->pendidikan ?? '-' }}</div>
                                                                                    </td>
                                                                                    <td class="p-3 font-monospace">{{ $t->no_telepon ?? '-' }}</td>
                                                                                </tr>
                                                                            @empty
                                                                                <tr><td colspan="5" class="text-center p-3 text-muted fst-italic">Tidak ada data tersangka</td></tr>
                                                                            @endforelse
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>

                                                            {{-- BAGIAN 3: BARANG BUKTI --}}
                                                            <div class="col-12 mt-4">
                                                                <div class="small text-secondary text-uppercase fw-bold mb-2 border-bottom pb-2">Barang Bukti</div>
                                                                <div class="table-responsive">
                                                                    <table class="table table-bordered align-middle mb-0">
                                                                        <thead class="bg-light">
                                                                            <tr>
                                                                                <th class="p-3">Nama Barang</th>
                                                                                <th class="p-3" style="width: 20%;">Kategori</th>
                                                                                <th class="p-3" style="width: 20%;">Jumlah</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @forelse($row->barangBukti as $bb)
                                                                                <tr>
                                                                                    <td class="p-3 fw-bold">{{ $bb->nama_barang }}</td>
                                                                                    <td class="p-3">
                                                                                        @if($bb->kategori == 'Narkotika') 
                                                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Narkotika</span>
                                                                                        @else 
                                                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Non-Narkotika</span> 
                                                                                        @endif
                                                                                    </td>
                                                                                    <td class="p-3">{{ $formatAngka($bb->kuantitas) }} {{ $bb->satuan }}</td>
                                                                                </tr>
                                                                            @empty
                                                                                <tr><td colspan="3" class="text-center p-3 text-muted fst-italic">Tidak ada barang bukti</td></tr>
                                                                            @endforelse
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>

                                                            {{-- BAGIAN 4: TIM HUKUM, MEDIS & HASIL --}}
                                                            <div class="row g-4 mt-2">
                                                                <div class="col-md-6 border-end">
                                                                    <div class="small text-secondary text-uppercase fw-bold mb-3">Tim Terpadu</div>
                                                                    <div class="mb-3">
                                                                        <label class="small text-muted fw-bold d-block mb-1">Tim Hukum:</label>
                                                                        @if(is_array($row->tim_hukum) && count($row->tim_hukum) > 0)
                                                                            <ul class="mb-0 ps-3">
                                                                                @foreach($row->tim_hukum as $th)
                                                                                    <li class="small mb-1">{{ $th['nama'] ?? '-' }} <span class="text-muted fst-italic">({{ $th['instansi'] ?? '-' }})</span></li>
                                                                                @endforeach
                                                                            </ul>
                                                                        @else
                                                                            <div class="text-muted small fst-italic">-</div>
                                                                        @endif
                                                                    </div>
                                                                    <div>
                                                                        <label class="small text-muted fw-bold d-block mb-1">Tim Medis:</label>
                                                                        @if(is_array($row->tim_medis) && count($row->tim_medis) > 0)
                                                                            <ul class="mb-0 ps-3">
                                                                                @foreach($row->tim_medis as $tm)
                                                                                    <li class="small mb-1">{{ $tm['nama'] ?? '-' }}</li>
                                                                                @endforeach
                                                                            </ul>
                                                                        @else
                                                                            <div class="text-muted small fst-italic">-</div>
                                                                        @endif
                                                                    </div>
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <div class="small text-secondary text-uppercase fw-bold mb-3">Hasil Asesmen</div>
                                                                    <div class="mb-3">
                                                                        <label class="small text-muted fw-bold d-block mb-1">Rekomendasi:</label>
                                                                        @if($row->tindak_lanjut_rekomendasi == 'dilaksanakan')
                                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i> Dilaksanakan</span>
                                                                        @else
                                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-2"><i class="bi bi-x-circle-fill me-1"></i> Tidak Dilaksanakan</span>
                                                                        @endif
                                                                    </div>
                                                                    <div>
                                                                        <label class="small text-muted fw-bold d-block mb-1">Proses Hukum Lanjut:</label>
                                                                        <div class="text-dark small" style="white-space: pre-wrap;">{{ $row->proses_hukum_lanjut ?? '-' }}</div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            {{-- METADATA --}}
                                                            <div class="row mt-4 pt-3 border-top">
                                                                <div class="col-md-6">
                                                                    <div class="small text-secondary fw-bold text-uppercase mb-1">Dibuat Pada</div>
                                                                    <div class="text-dark small"><i class="bi bi-clock me-1"></i>{{ $row->created_at->locale('id')->translatedFormat('d F Y H:i') }} WIB</div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="small text-secondary fw-bold text-uppercase mb-1">Terakhir Diubah</div>
                                                                    <div class="text-dark small"><i class="bi bi-pencil-square me-1"></i>{{ $row->updated_at->locale('id')->translatedFormat('d F Y H:i') }} WIB</div>
                                                                </div>
                                                            </div>

                                                            {{-- DOWNLOADER FILE --}}
                                                            <form action="{{ route('dokumen.zip.selected') }}" method="POST" x-ref="formZip">
                                                                @csrf
                                                                <div class="col-12 text-start border-top pt-3 mt-3">
                                                                    <div class="row g-4">
                                                                        @php
                                                                            $fotos = $row->dokumen->where('kategori', 'dokumentasi');
                                                                            $lampirans = $row->dokumen->where('kategori', 'lampiran');
                                                                            $fotoIds = $fotos->where('is_link', false)->pluck('id')->values()->toArray();
                                                                            $lampiranIds = $lampirans->where('is_link', false)->pluck('id')->values()->toArray();
                                                                        @endphp
                                                                        
                                                                        <div class="col-lg-6">
                                                                            <div class="card h-100 border bg-light shadow-none">
                                                                                <div class="card-header bg-transparent border-bottom fw-bold text-primary d-flex justify-content-between align-items-center">
                                                                                    <div class="form-check">
                                                                                        @if(count($fotoIds) > 0)
                                                                                            <input class="form-check-input cursor-pointer" type="checkbox" @change="toggleAll({{ json_encode($fotoIds) }})" :checked="isAllSelected({{ json_encode($fotoIds) }})">
                                                                                        @endif
                                                                                        <label class="form-check-label cursor-pointer select-none"><i class="bi bi-images me-2"></i>Dokumentasi</label>
                                                                                    </div>
                                                                                    <span class="badge bg-primary rounded-pill">{{ $fotos->count() }}</span>
                                                                                </div>
                                                                                <div class="card-body p-2" style="max-height: 250px; overflow-y: auto;">
                                                                                    @forelse($fotos as $doc)
                                                                                        <div class="d-flex align-items-center bg-white border rounded p-2 mb-2 shadow-sm hover-shadow transition-all" :class="isSelected({{ $doc->id }}) ? 'border-primary bg-primary bg-opacity-10' : ''">
                                                                                            @if(!$doc->is_link)
                                                                                                <div class="form-check me-2 d-flex align-items-center">
                                                                                                    <input class="form-check-input shadow-none cursor-pointer" type="checkbox" id="chk-doc-{{ $doc->id }}" name="ids[]" value="{{ $doc->id }}" x-model="selected">
                                                                                                </div>
                                                                                            @endif
                                                                                            <label for="chk-doc-{{ $doc->id }}" class="flex-grow-1 text-truncate small cursor-pointer d-flex align-items-center m-0">
                                                                                                <div class="flex-shrink-0 me-2 text-primary bg-primary bg-opacity-10 p-1 rounded">
                                                                                                    @if($doc->is_link) <i class="bi bi-link-45deg"></i> @else <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover rounded" width="24" height="24"> @endif
                                                                                                </div>
                                                                                                <span class="text-truncate" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</span>
                                                                                            </label>
                                                                                            <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                                                                                @if(!$doc->is_link) 
                                                                                                    <a href="{{ Storage::disk($doc->disk ?? 'public')->url($doc->path_file) }}" target="_blank" class="btn btn-xs btn-outline-secondary"><i class="bi bi-eye"></i></a> 
                                                                                                    <a href="{{ route('dokumen.download', $doc->id) }}" class="btn btn-xs btn-outline-primary"><i class="bi bi-download"></i></a> 
                                                                                                @else 
                                                                                                    <a href="{{ $doc->path_url }}" target="_blank" class="btn btn-xs btn-outline-info w-100"><i class="bi bi-box-arrow-up-right me-1"></i>Buka</a> 
                                                                                                @endif
                                                                                            </div>
                                                                                        </div>
                                                                                    @empty 
                                                                                        <div class="text-center py-3 text-muted small fst-italic">Tidak ada dokumentasi.</div> 
                                                                                    @endforelse
                                                                                </div>
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-lg-6">
                                                                            <div class="card h-100 border bg-light shadow-none">
                                                                                <div class="card-header bg-transparent border-bottom fw-bold text-danger d-flex justify-content-between align-items-center">
                                                                                    <div class="form-check">
                                                                                        @if(count($lampiranIds) > 0)
                                                                                            <input class="form-check-input cursor-pointer" type="checkbox" @change="toggleAll({{ json_encode($lampiranIds) }})" :checked="isAllSelected({{ json_encode($lampiranIds) }})">
                                                                                        @endif
                                                                                        <label class="form-check-label cursor-pointer select-none"><i class="bi bi-paperclip me-2"></i>Lampiran</label>
                                                                                    </div>
                                                                                    <span class="badge bg-danger rounded-pill">{{ $lampirans->count() }}</span>
                                                                                </div>
                                                                                <div class="card-body p-2" style="max-height: 250px; overflow-y: auto;">
                                                                                    @forelse($lampirans as $doc)
                                                                                        <div class="d-flex align-items-center bg-white border rounded p-2 mb-2 shadow-sm hover-shadow transition-all" :class="isSelected({{ $doc->id }}) ? 'border-danger bg-danger bg-opacity-10' : ''">
                                                                                            @if(!$doc->is_link)
                                                                                                <div class="form-check me-2 d-flex align-items-center">
                                                                                                    <input class="form-check-input shadow-none cursor-pointer" type="checkbox" id="chk-lamp-{{ $doc->id }}" name="ids[]" value="{{ $doc->id }}" x-model="selected">
                                                                                                </div>
                                                                                            @endif
                                                                                            <label for="chk-lamp-{{ $doc->id }}" class="flex-grow-1 text-truncate small cursor-pointer d-flex align-items-center m-0">
                                                                                                <div class="flex-shrink-0 me-2 text-danger bg-danger bg-opacity-10 p-1 rounded">
                                                                                                    @if($doc->is_link) <i class="bi bi-link-45deg"></i> @elseif(Str::contains($doc->tipe_file, 'pdf')) <i class="bi bi-file-pdf"></i> @else <i class="bi bi-file-earmark-text"></i> @endif
                                                                                                </div>
                                                                                                <span class="text-truncate" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</span>
                                                                                            </label>
                                                                                            <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                                                                                @if(!$doc->is_link) 
                                                                                                    <a href="{{ Storage::disk($doc->disk ?? 'public')->url($doc->path_file) }}" target="_blank" class="btn btn-xs btn-outline-secondary"><i class="bi bi-eye"></i></a> 
                                                                                                    <a href="{{ route('dokumen.download', $doc->id) }}" class="btn btn-xs btn-outline-danger"><i class="bi bi-download"></i></a> 
                                                                                                @else 
                                                                                                    <a href="{{ $doc->path_url }}" target="_blank" class="btn btn-xs btn-outline-info w-100"><i class="bi bi-box-arrow-up-right me-1"></i>Buka</a> 
                                                                                                @endif
                                                                                            </div>
                                                                                        </div>
                                                                                    @empty 
                                                                                        <div class="text-center py-3 text-muted small fst-italic">Tidak ada lampiran.</div> 
                                                                                    @endforelse
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    @php $hasPhysicalFiles = $row->dokumen->where('is_link', false)->count() > 0; @endphp
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
                                        <tr><td colspan="8" class="text-center py-5 text-muted fst-italic border-bottom">Tidak ada data TAT.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- FOOTER (PAGINATION) --}}
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
    /* Dropdown TomSelect di atas Sticky Header */
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
            isSelected(id) {
                return this.selected.includes(id.toString()) || this.selected.includes(id);
            },
            toggleAll(ids) {
                const stringIds = ids.map(String);
                const allSelected = stringIds.every(id => this.selected.includes(id));

                if (allSelected) {
                    this.selected = this.selected.filter(id => !stringIds.includes(id));
                } else {
                    this.selected = [...new Set([...this.selected, ...stringIds])];
                }
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
        
        // Inisialisasi Filter Standar
        ['select-satker', 'select-bulan', 'select-tahun', 'select-narkotika'].forEach(id => { 
            if(document.getElementById(id)) new TomSelect('#' + id, configTomSelect); 
        });

        // Inisialisasi NIK dan No HP (Multiple text input / tags)
        ['select-nik', 'select-nohp'].forEach(id => {
            if(document.getElementById(id)) {
                 new TomSelect('#' + id, {
                    plugins: ['remove_button', 'clear_button'],
                    create: true, 
                    persist: false,
                    createOnBlur: true,
                    maxOptions: null
                 });
            }
        });

        // Listener untuk Kategori Barang Bukti (Dinamis)
        if(document.getElementById('select-kategori-bb')) {
            const tsKategori = new TomSelect('#select-kategori-bb', {
                ...configTomSelect,
                onChange: function() { updateBBVisibility(); }
            });

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
             new TomSelect('#select-non-narkotika', {
                plugins: ['remove_button', 'clear_button'],
                create: true, 
                persist: false,
                createOnBlur: true,
                maxOptions: null
             });
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