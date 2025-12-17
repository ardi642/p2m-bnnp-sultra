@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1 fw-bold text-dark">Kegiatan P2M</h1>
                    <p class="text-muted mb-0">Master Data Sosialisasi Tatap Muka/Konvensional</p>
                </div>
            </div>
            
            @include('p2m.partials.select-p2m-index')

            {{-- LOGIKA PHP --}}
            @php
                $allFilters = request()->only(['satuan_kerja_id', 'bulan', 'tahun', 'anggaran_pelaksanaan', 'sasaran_kegiatan', 'search', 'pegawai_nips']);
                if (empty($allFilters['tahun'])) { $allFilters['tahun'] = [date('Y')]; }
                $activeFilters = collect($allFilters)->filter(function($value) { return !empty($value); })->count(); 
            @endphp
            
            <div class="row justify-content-center mb-5" x-data="{ showFilter: true }">
                <div class="col-12">
                    
                    {{-- CARD UTAMA --}}
                    <div class="card border-0 shadow-sm"> 
                        <div class="card-header bg-white py-3 border-bottom">
                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
                                <h5 class="card-title mb-0 fw-bold text-secondary"><i class="bi bi-table me-2"></i>Data Sosialisasi Tatap Muka/Konvensional</h5>
                                
                                {{-- Tombol Filter --}}
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
                            <form action="{{ route('p2m.sosialisasi.index') }}" method="GET">
                                <button type="submit" style="display: none;" aria-hidden="true"></button>
                                <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                                <input type="hidden" name="sort_order" value="{{ request('sort_order') }}">

                                {{-- PANEL FILTER --}}
                                <div x-show="showFilter" x-transition class="mb-4 px-3 px-lg-0 pt-3 pt-lg-0">
                                    <div class="bg-body-tertiary p-4 rounded-3 border">
                                        <div class="row g-3">
                                            
                                            {{-- Baris 1: Pencarian & Satuan Kerja (Jika Admin) --}}
                                            <div class="{{ $user->isAdmin() ? 'col-lg-8' : 'col-12' }}">
                                                <label class="form-label fw-bold small text-secondary text-uppercase">Kata Kunci</label>
                                                <div class="input-group shadow-sm">
                                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari Kegiatan, Tempat, dll..." value="{{ request('search') }}">
                                                </div>
                                            </div>

                                            @if ($user->isAdmin())
                                                <div class="col-lg-4">
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

                                            {{-- Baris 2: Anggaran, Sasaran, Bulan, Tahun (Satu Baris) --}}
                                            <div class="col-12 col-lg-3">
                                                <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Anggaran</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-anggaran" name="anggaran_pelaksanaan[]" multiple placeholder="Pilih Anggaran..." autocomplete="off">
                                                        <option value="DIPA" {{ in_array('DIPA', request('anggaran_pelaksanaan', [])) ? 'selected' : '' }}>DIPA</option>
                                                        <option value="NON DIPA" {{ in_array('NON DIPA', request('anggaran_pelaksanaan', [])) ? 'selected' : '' }}>NON DIPA</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-12 col-lg-3">
                                                <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Sasaran</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-sasaran" name="sasaran_kegiatan[]" multiple placeholder="Pilih Sasaran..." autocomplete="off">
                                                        <option value="lingkungan pendidikan" {{ in_array('lingkungan pendidikan', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Lingkungan Pendidikan</option>
                                                        <option value="lingkungan kerja" {{ in_array('lingkungan kerja', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Lingkungan Kerja</option>
                                                        <option value="lingkungan masyarakat" {{ in_array('lingkungan masyarakat', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Lingkungan Masyarakat</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-6 col-lg-3">
                                                <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Bulan</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-bulan" name="bulan[]" multiple placeholder="Bulan..." autocomplete="off">
                                                        @foreach(range(1, 12) as $m)
                                                            <option value="{{ $m }}" {{ in_array($m, request('bulan', [])) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-6 col-lg-3">
                                                <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Tahun</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-tahun" name="tahun[]" multiple placeholder="Tahun..." autocomplete="off">
                                                        @foreach($years as $year)
                                                            <option value="{{ $year }}" {{ in_array($year, request('tahun', [date('Y')])) ? 'selected' : '' }}>{{ $year }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Baris 3: Pegawai (Col-6) --}}
                                            <div class="col-12 col-lg-6">
                                                <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Pegawai</label>
                                                <div class="d-flex align-items-stretch shadow-sm bg-white rounded border" x-data="{ logic: '{{ request('pegawai_logic', 'OR') }}' }">
                                                    
                                                    {{-- PERBAIKAN WARNA TOMBOL LOGIKA --}}
                                                    <button type="button" 
                                                            class="btn rounded-0 rounded-start border-end d-flex align-items-center justify-content-center fw-bold px-3" 
                                                            style="width: 70px; flex-shrink: 0;"
                                                            {{-- UBAH DISINI: Pakai btn-danger (solid) biar tidak pudar --}}
                                                            :class="logic === 'AND' ? 'btn-danger text-white' : 'btn-light text-secondary'"
                                                            @click="logic = logic === 'OR' ? 'AND' : 'OR'"
                                                            title="Klik untuk ubah logika">
                                                        <span x-text="logic"></span>
                                                    </button>
                                                    <input type="hidden" name="pegawai_logic" :value="logic">
                                                    
                                                    <div class="flex-grow-1" style="min-width: 0;">
                                                        <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Cari Pegawai..." autocomplete="off" class="border-0">
                                                            @foreach($pegawais as $pgw)
                                                                {{-- PERBAIKAN OPSI: Tambah NIP --}}
                                                                <option value="{{ $pgw->nip }}" {{ in_array($pgw->nip, request('pegawai_nips', [])) ? 'selected' : '' }}>
                                                                    {{ $pgw->nama }} ({{ $pgw->nip }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 text-end pt-3 border-top mt-4">
                                                <a href="{{ route('p2m.sosialisasi.index') }}" class="btn btn-link text-decoration-none text-muted btn-sm me-2">Reset</a>
                                                <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-funnel-fill me-1"></i> Terapkan</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- TOOLBAR --}}
                                <div class="d-flex justify-content-between align-items-center mb-3 px-3 px-lg-0">
                                    <button type="submit" formaction="{{ route('p2m.sosialisasi.export') }}" class="btn btn-success btn-sm text-white d-flex align-items-center gap-2 shadow-sm">
                                        <i class="bi bi-file-earmark-excel"></i> <span class="d-none d-lg-inline">Export Excel</span>
                                    </button>
                                    <div class="text-muted small fst-italic">
                                        Total Data: <strong>{{ $sosialisasis->total() }}</strong>
                                    </div>
                                </div>
                            </form>
                            
                            {{-- TABEL DATA --}}
                            <div class="custom-table-scroll mb-3" id="data-table">
                                <table class="table table-hover align-middle mb-0" x-data="{ expanded: [] }">
                                    <thead class="bg-light">
                                        <tr class="text-center align-middle small text-uppercase fw-bold text-secondary text-nowrap">
                                            <th class="py-3 bg-light ps-3">No</th>
                                            <th class="py-3 bg-light text-start">
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'satuan_kerja', 'sort_order' => request('sort_by') == 'satuan_kerja' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-secondary d-flex align-items-center gap-1">
                                                    Satuan Kerja
                                                    @if(request('sort_by') == 'satuan_kerja') <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'alpha-down' : 'alpha-down-alt' }}"></i>
                                                    @else <i class="bi bi-arrow-down-up opacity-25"></i> @endif
                                                </a>
                                            </th>
                                            <th class="py-3 bg-light">
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'anggaran_pelaksanaan', 'sort_order' => request('sort_by') == 'anggaran_pelaksanaan' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-secondary d-flex justify-content-center align-items-center gap-1">
                                                    Anggaran
                                                    @if(request('sort_by') == 'anggaran_pelaksanaan') <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'down' : 'up' }}"></i>
                                                    @else <i class="bi bi-arrow-down-up opacity-25"></i> @endif
                                                </a>
                                            </th>
                                            <th class="py-3 bg-light text-start">
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'nama_kegiatan', 'sort_order' => request('sort_by') == 'nama_kegiatan' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-secondary d-flex align-items-center gap-1">
                                                    Nama Kegiatan
                                                    @if(request('sort_by') == 'nama_kegiatan') <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'alpha-down' : 'alpha-down-alt' }}"></i>
                                                    @else <i class="bi bi-arrow-down-up opacity-25"></i> @endif
                                                </a>
                                            </th>
                                            <th class="py-3 bg-light">
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'sasaran_kegiatan', 'sort_order' => request('sort_by') == 'sasaran_kegiatan' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-secondary d-flex justify-content-center align-items-center gap-1">
                                                    Sasaran
                                                    @if(request('sort_by') == 'sasaran_kegiatan') <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'alpha-down' : 'alpha-down-alt' }}"></i>
                                                    @else <i class="bi bi-arrow-down-up opacity-25"></i> @endif
                                                </a>
                                            </th>
                                            <th class="py-3 bg-light">
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'tanggal_pelaksanaan', 'sort_order' => request('sort_by') == 'tanggal_pelaksanaan' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-secondary d-flex justify-content-center align-items-center gap-1">
                                                    Tanggal
                                                    @if(request('sort_by') == 'tanggal_pelaksanaan') <i class="bi bi-sort-numeric-{{ request('sort_order') == 'asc' ? 'down' : 'up-alt' }}"></i>
                                                    @else <i class="bi bi-arrow-down-up opacity-25"></i> @endif
                                                </a>
                                            </th>
                                            <th class="py-3 bg-light">
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'tempat_kegiatan', 'sort_order' => request('sort_by') == 'tempat_kegiatan' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-secondary d-flex justify-content-center align-items-center gap-1">
                                                    Tempat
                                                    @if(request('sort_by') == 'tempat_kegiatan') <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'alpha-down' : 'alpha-down-alt' }}"></i>
                                                    @else <i class="bi bi-arrow-down-up opacity-25"></i> @endif
                                                </a>
                                            </th>
                                            <th class="py-3 bg-light text-start" style="min-width: 250px;">Pegawai</th>
                                            <th class="py-3 bg-light">
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'jumlah_peserta', 'sort_order' => request('sort_by') == 'jumlah_peserta' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-secondary d-flex justify-content-center align-items-center gap-1">
                                                    Peserta
                                                    @if(request('sort_by') == 'jumlah_peserta') <i class="bi bi-sort-numeric-{{ request('sort_order') == 'asc' ? 'down' : 'up-alt' }}"></i>
                                                    @else <i class="bi bi-arrow-down-up opacity-25"></i> @endif
                                                </a>
                                            </th>
                                            <th class="py-3 bg-light">
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => (request('sort_by') == 'created_at' && request('sort_order') == 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-secondary d-flex justify-content-center align-items-center gap-1">
                                                    Dibuat
                                                    @if(!request('sort_by') || request('sort_by') == 'created_at') <i class="bi bi-caret-{{ request('sort_order', 'desc') == 'asc' ? 'up' : 'down' }}-fill small"></i>
                                                    @else <i class="bi bi-arrow-down-up opacity-25"></i> @endif
                                                </a>
                                            </th>
                                            <th class="py-3 bg-light pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="border-top-0">
                                        @forelse ($sosialisasis as $data)
                                            <tr class="text-center align-middle" :class="expanded.includes({{ $data->id }}) ? 'bg-light' : ''">
                                                <td class="fw-bold text-secondary ps-3">{{ $sosialisasis->firstItem() + $loop->index }}</td>
                                                <td class="text-start"><span class="fw-semibold text-dark">{{ $data->satuanKerja->satuan_kerja ?? '-' }}</span></td>
                                                
                                                <td>
                                                    <span class="badge rounded-pill {{ $data->anggaran_pelaksanaan == 'DIPA' ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-info bg-opacity-10 text-info border border-info border-opacity-25' }}">
                                                        {{ $data->anggaran_pelaksanaan }}
                                                    </span>
                                                </td>

                                                <td class="text-start">
                                                    <a href="#" class="text-decoration-none fw-bold text-dark" 
                                                       @click.prevent="expanded.includes({{ $data->id }}) ? expanded = expanded.filter(id => id !== {{ $data->id }}) : expanded.push({{ $data->id }})">
                                                        {{ $data->nama_kegiatan }}
                                                    </a>
                                                </td>

                                                <td>
                                                    @php
                                                        $sasaranClass = match($data->sasaran_kegiatan) {
                                                            'lingkungan pendidikan' => 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25',
                                                            'lingkungan kerja' => 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25',
                                                            default => 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25',
                                                        };
                                                    @endphp
                                                    <span class="badge rounded-pill {{ $sasaranClass }} text-capitalize">
                                                        {{ $data->sasaran_kegiatan }}
                                                    </span>
                                                </td>

                                                <td class="small text-muted text-nowrap">{{ $data->tanggal_pelaksanaan->locale('id')->translatedFormat('d M Y') }}</td>
                                                <td class="small">{{ Str::limit($data->tempat_kegiatan, 20) }}</td>
                                                
                                                <td class="text-start">
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach($data->pegawai->sortBy('nama') as $pegawai)
                                                            <span class="badge bg-white text-secondary border fw-normal shadow-sm" style="font-size: 0.75rem;">
                                                                {{ $pegawai->nama }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </td>

                                                <td><span class="fw-bold">{{ $data->jumlah_peserta }}</span></td>
                                                
                                                <td class="small text-muted">
                                                    {{ $data->created_at->locale('id')->translatedFormat('d M Y') }}<br>
                                                    {{ $data->created_at->format('H:i') }}
                                                </td>

                                                <td class="pe-3">
                                                    <div class="btn-group btn-group-sm shadow-sm">
                                                        <button type="button" class="btn btn-light border text-secondary" title="Lihat Detail"
                                                                @click="expanded.includes({{ $data->id }}) ? expanded = expanded.filter(id => id !== {{ $data->id }}) : expanded.push({{ $data->id }})">
                                                            <i class="bi" :class="expanded.includes({{ $data->id }}) ? 'bi-chevron-up' : 'bi-eye'"></i>
                                                        </button>
                                                        @if (auth()->user()->hasRole('operator'))
                                                            <a href="{{ route('p2m.sosialisasi.edit', $data->id) }}" class="btn btn-light border text-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                            <button type="button" class="btn btn-light border text-danger" title="Hapus" onclick="confirmDelete({{ $data->id }})"><i class="bi bi-trash"></i></button>
                                                            <form id="delete-form-{{ $data->id }}" action="{{ route('p2m.sosialisasi.destroy', $data->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>

                                            {{-- EXPANDED ROW (DETAIL LENGKAP) --}}
                                            <tr x-show="expanded.includes({{ $data->id }})" x-transition>
                                                <td colspan="11" class="p-0 border-0">
                                                    <div class="bg-body-tertiary p-4 border-bottom shadow-inner">
                                                        <div class="card border-0 shadow-sm">
                                                            <div class="card-body">
                                                                <h6 class="card-title fw-bold text-primary border-bottom pb-2 mb-3">
                                                                    <i class="bi bi-info-circle me-2"></i>Detail Lengkap
                                                                </h6>
                                                                
                                                                <div class="row g-4">
                                                                    {{-- Kolom Kiri: Data Utama --}}
                                                                    <div class="col-lg-6 border-end-lg">
                                                                        <dl class="row mb-0 small">
                                                                            <dt class="col-sm-4 text-secondary">Kegiatan</dt>
                                                                            <dd class="col-sm-8 fw-bold text-dark">{{ $data->nama_kegiatan }}</dd>

                                                                            <dt class="col-sm-4 text-secondary">Satuan Kerja</dt>
                                                                            <dd class="col-sm-8">{{ $data->satuanKerja->satuan_kerja ?? '-' }}</dd>

                                                                            <dt class="col-sm-4 text-secondary">Anggaran</dt>
                                                                            <dd class="col-sm-8"><span class="badge bg-light text-dark border">{{ $data->anggaran_pelaksanaan }}</span></dd>

                                                                            <dt class="col-sm-4 text-secondary">Sasaran</dt>
                                                                            <dd class="col-sm-8 text-capitalize">{{ $data->sasaran_kegiatan }}</dd>

                                                                            <dt class="col-sm-4 text-secondary">Tanggal</dt>
                                                                            <dd class="col-sm-8">{{ $data->tanggal_pelaksanaan->locale('id')->translatedFormat('l, d F Y') }}</dd>

                                                                            <dt class="col-sm-4 text-secondary">Tempat</dt>
                                                                            <dd class="col-sm-8">{{ $data->tempat_kegiatan }}</dd>

                                                                            <dt class="col-sm-4 text-secondary">Peserta</dt>
                                                                            <dd class="col-sm-8 fw-bold">{{ $data->jumlah_peserta }} Orang</dd>
                                                                        </dl>
                                                                    </div>

                                                                    {{-- Kolom Kanan: Meta Data & Pegawai --}}
                                                                    <div class="col-lg-6">
                                                                        <dl class="row mb-3 small">
                                                                            <dt class="col-sm-4 text-secondary">Dibuat</dt>
                                                                            <dd class="col-sm-8">
                                                                                {{ $data->created_at->locale('id')->translatedFormat('l, d F Y H:i') }}
                                                                            </dd>

                                                                            <dt class="col-sm-4 text-secondary">Terakhir Diubah</dt>
                                                                            <dd class="col-sm-8">{{ $data->updated_at->locale('id')->translatedFormat('l, d F Y H:i') }}</dd>

                                                                            <dt class="col-sm-4 text-secondary">List Pegawai</dt>
                                                                            <dd class="col-sm-8">
                                                                                <ul class="list-unstyled mb-0" style="max-height: 150px; overflow-y: auto;">
                                                                                    @foreach($data->pegawai->sortBy('nama') as $pegawai)
                                                                                        <li class="mb-1">
                                                                                            <i class="bi bi-person-check me-2 text-success"></i>
                                                                                            {{ $pegawai->nama }} 
                                                                                            <span class="text-muted small ms-1">({{ $pegawai->nip }})</span>
                                                                                        </li>
                                                                                    @endforeach
                                                                                </ul>
                                                                            </dd>
                                                                        </dl>
                                                                    </div>
                                                                    
                                                                    {{-- DOKUMENTASI --}}
                                                                    <div class="col-12">
                                                                        <div class="border-top pt-3">
                                                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                                                <span class="fw-bold text-secondary small">Dokumentasi & Lampiran</span>
                                                                                <span class="badge bg-secondary rounded-pill">{{ $data->dokumentasi->count() }}</span>
                                                                            </div>

                                                                            @if($data->dokumentasi->count() > 0)
                                                                                <div class="row g-2">
                                                                                    @foreach($data->dokumentasi as $doc)
                                                                                        <div class="col-12 col-md-6">
                                                                                            <div class="card border p-2 bg-light shadow-none h-100">
                                                                                                <div class="d-flex align-items-center gap-2">
                                                                                                    <div class="flex-shrink-0 text-secondary">
                                                                                                        @if(Str::contains($doc->tipe_file, 'image')) <i class="bi bi-file-image fs-3 text-primary"></i>
                                                                                                        @elseif(Str::contains($doc->tipe_file, 'pdf')) <i class="bi bi-file-pdf fs-3 text-danger"></i>
                                                                                                        @elseif(Str::contains($doc->tipe_file, ['word', 'officedocument', 'msword'])) <i class="bi bi-file-word fs-3 text-primary"></i>
                                                                                                        @elseif(Str::contains($doc->tipe_file, ['excel', 'spreadsheet'])) <i class="bi bi-file-excel fs-3 text-success"></i>
                                                                                                        @else <i class="bi bi-file-earmark fs-3"></i> @endif
                                                                                                    </div>
                                                                                                    
                                                                                                    <div class="flex-grow-1" style="min-width: 0;">
                                                                                                        <div class="small fw-bold lh-sm text-break">{{ $doc->nama_file_asli }}</div>
                                                                                                        <div class="text-muted" style="font-size: 0.7rem;">{{ number_format($doc->ukuran_file / 1024, 0) }} KB</div>
                                                                                                    </div>
                                                                                                    
                                                                                                    <div class="d-flex gap-1">
                                                                                                        @if(Str::contains($doc->tipe_file, ['image', 'pdf']))
                                                                                                            <a href="{{ Storage::url($doc->path_file) }}" target="_blank" class="btn btn-sm btn-outline-info py-0 px-2" title="Lihat"><i class="bi bi-eye"></i></a>
                                                                                                        @endif
                                                                                                        <a href="{{ route('dokumentasi.download', $doc->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2" title="Unduh"><i class="bi bi-download"></i></a>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    @endforeach
                                                                                </div>
                                                                            @else
                                                                                <div class="text-muted small fst-italic border rounded p-2 text-center bg-light">Tidak ada file.</div>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="11" class="text-center py-5 text-muted">Belum ada data kegiatan.</td></tr>
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
                                    <div>{{ $sosialisasis->withQueryString()->links() }}</div>
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
@vite('resources/css/tom-select.css')
<style>
    .ts-control { 
        border: none !important; 
        box-shadow: none !important;
        padding-top: 0.5rem; 
        padding-bottom: 0.5rem; 
        background-color: transparent !important;
        min-height: 40px; 
    }
    .ts-wrapper.focus .ts-control { 
        box-shadow: none !important; 
    }
    
    .custom-table-scroll { max-height: 70vh; overflow-y: auto; position: relative; border: 1px solid #dee2e6; border-radius: 6px; }
    .custom-table-scroll thead th { position: sticky !important; top: 0 !important; z-index: 2; background-color: #f8f9fa !important; box-shadow: inset 0 -1px 0 #dee2e6; }
    
    .page-link { border: none; color: #6c757d; border-radius: 50% !important; margin: 0 2px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
    .page-item.active .page-link { background-color: #0d6efd; color: white; box-shadow: 0 2px 4px rgba(13,110,253,0.3); }
</style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const configTomSelect = { plugins: ['remove_button', 'clear_button'], persist: false, create: false, maxOptions: null };
        const ids = ['select-satker', 'select-bulan', 'select-anggaran', 'select-sasaran', 'select-tahun', 'select-pegawai'];
        ids.forEach(id => { if(document.getElementById(id)) new TomSelect('#' + id, configTomSelect); });
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
    
    @if(session('success'))
        Swal.fire({ icon: 'success', title: 'Berhasil', text: "{{ session('message') }}", timer: 3000, showConfirmButton: false, toast: true, position: 'top-end' });
    @endif
</script>
@endpush