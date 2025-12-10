@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">P2M Sosialisasi Tatap Muka/Konvensional</h1>
                    <p class="text-muted mb-0">Master Data Sosialisasi Tatap Muka/Konvensional</p>
                </div>
            </div>
            
            @include('p2m.partials.select-p2m-index')

            {{-- LOGIKA HITUNG JUMLAH FILTER AKTIF (Define variable di sini) --}}
            @php
                // 1. Ambil semua input dari request (Kecuali pegawai_logic)
                $allFilters = request()->only([
                    'satuan_kerja_id', 
                    'bulan', 
                    'tahun', 
                    'anggaran_pelaksanaan', 
                    'sasaran_kegiatan', 
                    'search',
                    'pegawai_nips'
                ]);

                // 2. LOGIC DEFAULT TAHUN:
                if (empty($allFilters['tahun'])) {
                    $allFilters['tahun'] = [date('Y')];
                }

                // 3. HITUNG JUMLAH KATEGORI YANG AKTIF
                // Hapus flatten() agar array (seperti pegawai/satker) dihitung 1 kategori
                $activeFilters = collect($allFilters)->filter(function($value) {
                    // Cek apakah value ada isinya (tidak null, tidak array kosong, tidak string kosong)
                    return !empty($value);
                })->count(); 
            @endphp
            
            {{-- ALPINE JS STATE: showFilter selalu true agar filter terbuka default --}}
            <div class="row justify-content-center mb-10" 
                 x-data="{ showFilter: true }">
                
                <div class="col-12 col-lg-12">
                    <div class="card shadow-lg p-5">
                        <div class="card-header bg-white border-0">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="card-title mb-0 text-center">Data sosialisasi Tatap Muka/Konvensional</h5>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            
                            {{-- FORM PEMBUNGKUS UTAMA --}}
                            <form action="{{ route('p2m.sosialisasi.index') }}" method="GET" class="mb-8">
                                
                                {{-- TAMBAHAN: HIDDEN INPUT UNTUK MENJAGA SORTING SAAT EXPORT/FILTER --}}
                                {{-- Ini mengambil nilai dari URL dan memasukkannya ke dalam form --}}
                                <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                                <input type="hidden" name="sort_order" value="{{ request('sort_order') }}">

                                {{-- TOOLBAR ATAS --}}
                                <div class="row mb-5 align-items-center">
                                    <div class="col-auto">
                                        <div class="d-flex gap-2">
                                            
                                            {{-- 1. TOMBOL TOGGLE --}}
                                            <button type="button" 
                                                    @click="showFilter = !showFilter" 
                                                    class="btn btn-sm transition-all d-flex align-items-center gap-2"
                                                    :class="showFilter ? 'btn-secondary' : 'btn-primary'">
                                                <i class="bi" :class="showFilter ? 'bi-x-lg' : 'bi-sliders'"></i> 
                                                <span x-text="showFilter ? 'Tutup Filter' : 'Filter Pencarian Lanjutan'"></span>
                                                
                                                {{-- BADGE AKTIF --}}
                                                @if($activeFilters > 0)
                                                    <span class="badge bg-warning text-dark border border-dark rounded-pill px-2 ms-1" 
                                                          title="{{ $activeFilters }} kriteria filter sedang aktif">
                                                        {{ $activeFilters }} Aktif
                                                    </span>
                                                @endif
                                            </button>

                                            {{-- 2. TOMBOL HAPUS FILTER (Hanya muncul jika ada filter/search aktif) --}}
                                            {{-- @if($activeFilters > 0)
                                                <a href="{{ route('p2m.sosialisasi.index') }}" 
                                                   class="btn btn-danger btn-sm text-white d-flex align-items-center gap-1">
                                                    <i class="bi bi-x-circle"></i> Hapus Filter
                                                </a>
                                            @endif --}}

                                            {{-- 3. TOMBOL EXPORT EXCEL --}}
                                            {{-- Tombol ini akan mengirim semua input filter meskipun panel filter sedang tertutup --}}
                                            <button type="submit" 
                                                    formaction="{{ route('p2m.sosialisasi.export') }}"
                                                    class="btn btn-success btn-sm text-white d-flex align-items-center gap-2"
                                                    title="Export data sesuai filter yang aktif">
                                                <i class="bi bi-file-earmark-excel"></i> 
                                                <span class="d-none d-md-inline">Export Excel</span> {{-- Text sembunyi di HP biar rapi --}}
                                            </button>

                                        </div>
                                    </div>

                                    <div class="col-auto ms-auto">
                                        {{-- 3. INPUT PENCARIAN UMUM --}}
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="search" class="form-control" placeholder="Pencarian..." value="{{ request('search') }}">
                                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
                                        </div>
                                    </div>
                                    
                                </div>

                                {{-- PANEL FILTER --}}
                                <div x-show="showFilter" 
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 transform scale-95"
                                    x-transition:enter-end="opacity-100 transform scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 transform scale-100"
                                    x-transition:leave-end="opacity-0 transform scale-95"
                                    class="mb-4">

                                    <div class="bg-light p-4 rounded-3 border">
                                        <div class="row g-3">
                                            
                                            {{-- 1. SATUAN KERJA --}}
                                            @if ($user->isAdmin())
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold small text-muted text-uppercase mb-1">Satuan Kerja</label>
                                                    <select id="select-satker" name="satuan_kerja_id[]" multiple placeholder="Pilih Satuan Kerja..." autocomplete="off">
                                                        @foreach($satuanKerjas as $satker)
                                                            <option value="{{ $satker->id }}" {{ in_array($satker->id, request('satuan_kerja_id', [])) ? 'selected' : '' }}>
                                                                {{ $satker->satuan_kerja }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif

                                            {{-- 2. ANGGARAN --}}
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Anggaran</label>
                                                <select id="select-anggaran" name="anggaran_pelaksanaan[]" multiple placeholder="Pilih Anggaran..." autocomplete="off">
                                                    <option value="DIPA" {{ in_array('DIPA', request('anggaran_pelaksanaan', [])) ? 'selected' : '' }}>DIPA</option>
                                                    <option value="NON DIPA" {{ in_array('NON DIPA', request('anggaran_pelaksanaan', [])) ? 'selected' : '' }}>NON DIPA</option>
                                                </select>
                                            </div>
                                            
                                            {{-- 3. BULAN --}}
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Bulan Pelaksanaan</label>
                                                <select id="select-bulan" name="bulan[]" multiple placeholder="Pilih Bulan..." autocomplete="off">
                                                    @php $selectedMonths = request('bulan', []); @endphp
                                                    @foreach(range(1, 12) as $m)
                                                        <option value="{{ $m }}" {{ in_array($m, $selectedMonths) ? 'selected' : '' }}>
                                                            {{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            {{-- BARIS TAHUN PELAKSANAAN (Di dalam <div class="bg-light p-4 rounded-3 border">) --}}
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Tahun Pelaksanaan</label>
                                                <select id="select-tahun" name="tahun[]" multiple placeholder="Pilih Tahun..." autocomplete="off">
                                                    @php
                                                        $currentYear = date('Y');
                                                        $selectedYears = request('tahun', []);
                                                        if (empty($selectedYears)) {
                                                            $selectedYears = [$currentYear];
                                                        }
                                                    @endphp
                                                    @foreach($years as $year)
                                                        <option value="{{ $year }}" {{ in_array($year, $selectedYears) ? 'selected' : '' }}>
                                                            {{ $year }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            {{-- 5. SASARAN --}}
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Sasaran Kegiatan</label>
                                                <select id="select-sasaran" name="sasaran_kegiatan[]" multiple placeholder="Pilih Sasaran..." autocomplete="off">
                                                    <option value="lingkungan pendidikan" {{ in_array('lingkungan pendidikan', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Lingkungan Pendidikan</option>
                                                    <option value="lingkungan kerja" {{ in_array('lingkungan kerja', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Lingkungan Kerja</option>
                                                    <option value="lingkungan masyarakat" {{ in_array('lingkungan masyarakat', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Lingkungan Masyarakat</option>
                                                </select>
                                            </div>

                                            {{-- 6. PEGAWAI --}}
                                            <div class="col-md-12">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Pegawai</label>
                                                
                                                <div class="input-group" x-data="{ logic: '{{ request('pegawai_logic', 'OR') }}' }">
                                                    
                                                    {{-- Tombol Toggle Logic --}}
                                                    <button type="button" 
                                                            class="btn d-flex align-items-center gap-2 fw-bold"
                                                            :class="logic === 'AND' ? 'btn-danger text-white' : 'btn-outline-secondary bg-white text-secondary'"
                                                            @click="logic = logic === 'OR' ? 'AND' : 'OR'"
                                                            title="Klik untuk ubah logika filter">
                                                        
                                                        <i class="bi" :class="logic === 'AND' ? 'bi-check-all' : 'bi-check'"></i>
                                                        <span x-text="logic === 'AND' ? 'SEMUA (AND)' : 'SALAH SATU (OR)'" style="font-size: 0.8rem;"></span>
                                                    </button>

                                                    <input type="hidden" name="pegawai_logic" :value="logic">

                                                    {{-- Select TomSelect Pegawai --}}
                                                    <div style="flex-grow: 1;">
                                                        <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Pilih Pegawai..." autocomplete="off">
                                                            @php $selectedNips = request('pegawai_nips', []); @endphp
                                                            @foreach($pegawais as $pgw)
                                                                <option value="{{ $pgw->nip }}" {{ in_array($pgw->nip, $selectedNips) ? 'selected' : '' }}>
                                                                    {{ $pgw->nama }} - NIP: {{ $pgw->nip }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- BUTTONS ACTION --}}
                                            <div class="col-12 text-end mt-4 pt-2 border-top border-secondary-subtle">
                                                <a href="{{ route('p2m.sosialisasi.index') }}" 
                                                    class="btn btn-outline-secondary btn-sm me-2 px-3">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                                                </a>
                                                <button type="submit" class="btn btn-primary btn-sm px-4">
                                                    <i class="bi bi-funnel-fill"></i> Terapkan Filter
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            
                            {{-- TABEL DATA --}}
                            <div class="custom-table-scroll mb-3" id="data-table">
                                <table class="table table-hover mb-0" x-data="{ expanded: [] }">
                                    <thead class="table-light">
                                        <tr class="text-center align-middle">
                                            {{-- 1. NO (Tidak di-sort) --}}
                                            <th>No</th>

                                            {{-- 2. SATUAN KERJA --}}
                                            <th>
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'satuan_kerja', 'sort_order' => request('sort_by') == 'satuan_kerja' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" 
                                                class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">
                                                    Satuan Kerja
                                                    @if(request('sort_by') == 'satuan_kerja')
                                                        <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'alpha-down' : 'alpha-down-alt' }}"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i>
                                                    @endif
                                                </a>
                                            </th>

                                            {{-- 3. ANGGARAN --}}
                                            <th>
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'anggaran_pelaksanaan', 'sort_order' => request('sort_by') == 'anggaran_pelaksanaan' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" 
                                                class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">
                                                    Anggaran
                                                    @if(request('sort_by') == 'anggaran_pelaksanaan')
                                                        <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'down' : 'up' }}"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i>
                                                    @endif
                                                </a>
                                            </th>

                                            {{-- 4. NAMA KEGIATAN --}}
                                            <th>
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'nama_kegiatan', 'sort_order' => request('sort_by') == 'nama_kegiatan' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" 
                                                class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">
                                                    Nama Kegiatan
                                                    @if(request('sort_by') == 'nama_kegiatan')
                                                        <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'alpha-down' : 'alpha-down-alt' }}"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i>
                                                    @endif
                                                </a>
                                            </th>

                                            {{-- 5. SASARAN --}}
                                            <th>
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'sasaran_kegiatan', 'sort_order' => request('sort_by') == 'sasaran_kegiatan' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" 
                                                class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">
                                                    Sasaran
                                                    @if(request('sort_by') == 'sasaran_kegiatan')
                                                        <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'alpha-down' : 'alpha-down-alt' }}"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i>
                                                    @endif
                                                </a>
                                            </th>

                                            {{-- 6. TANGGAL --}}
                                            <th>
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'tanggal_pelaksanaan', 'sort_order' => request('sort_by') == 'tanggal_pelaksanaan' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" 
                                                class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">
                                                    Tanggal
                                                    @if(request('sort_by') == 'tanggal_pelaksanaan')
                                                        <i class="bi bi-sort-numeric-{{ request('sort_order') == 'asc' ? 'down' : 'up-alt' }}"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i>
                                                    @endif
                                                </a>
                                            </th>

                                            {{-- 7. TEMPAT --}}
                                            <th>
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'tempat_kegiatan', 'sort_order' => request('sort_by') == 'tempat_kegiatan' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" 
                                                class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">
                                                    Tempat
                                                    @if(request('sort_by') == 'tempat_kegiatan')
                                                        <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'alpha-down' : 'alpha-down-alt' }}"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i>
                                                    @endif
                                                </a>
                                            </th>

                                            {{-- 8. PEGAWAI (Tidak di-sort sesuai permintaan) --}}
                                            <th style="min-width: 200px;">Nama Pegawai</th>

                                            {{-- 9. JUMLAH PESERTA --}}
                                            <th>
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'jumlah_peserta', 'sort_order' => request('sort_by') == 'jumlah_peserta' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" 
                                                class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">
                                                    Peserta
                                                    @if(request('sort_by') == 'jumlah_peserta')
                                                        <i class="bi bi-sort-numeric-{{ request('sort_order') == 'asc' ? 'down' : 'up-alt' }}"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            
                                            <th>
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => (request('sort_by') == 'created_at' && request('sort_order') == 'asc') ? 'desc' : 'asc']) }}" 
                                                class="text-decoration-none text-dark d-block text-nowrap">
                                                    Dibuat
                                                    @if(!request('sort_by') || request('sort_by') == 'created_at')
                                                        {{-- Icon Aktif (Default) --}}
                                                        <i class="bi bi-caret-{{ request('sort_order', 'desc') == 'asc' ? 'up' : 'down' }}-fill small ms-1"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up text-muted opacity-25 small ms-1"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            {{-- 10. AKSI (Tidak di-sort) --}}
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($sosialisasis as $data)
                                            <tr class="text-center align-middle">
                                                <td>{{ $sosialisasis->firstItem() + $loop->index }}</td>
                                                <td>{{ $data->satuanKerja->satuan_kerja ?? '-' }}</td>
                                                <td>{{ $data->anggaran_pelaksanaan }}</td>
                                                <td>{{ $data->nama_kegiatan }}</td>
                                                <td>{{ $data->sasaran_kegiatan }}</td>
                                                <td>{{ $data->tanggal_pelaksanaan->locale('id')->translatedFormat('l, d F Y') }}</td>
                                                <td>{{ $data->tempat_kegiatan }}</td>
                                                <td class="text-start">
                                                    @foreach($data->pegawai as $pegawai)
                                                        <span class="badge bg-primary mb-1">{{ $pegawai->nama }}</span>
                                                    @endforeach
                                                </td>
                                                <td>{{ $data->jumlah_peserta }}</td>
                                                <td class="small text-muted">
                                                    {{ $data->created_at->locale('id')->translatedFormat('d M Y') }}<br>
                                                    {{ $data->created_at->format('H:i') }}
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <button type="button" class="btn btn-info btn-sm text-white" 
                                                                @click="expanded.includes({{ $data->id }}) ? expanded = expanded.filter(id => id !== {{ $data->id }}) : expanded.push({{ $data->id }})">
                                                            <i class="me-0 bi" :class="expanded.includes({{ $data->id }}) ? 'bi-eye-slash' : 'bi-eye'"></i> 
                                                        </button>
                                                        <a href="{{ route('p2m.sosialisasi.edit', $data->id) }}" class="btn btn-success btn-sm"><i class="me-0 bi bi-pencil-square"></i></a>
                                                        <form id="delete-form-{{ $data->id }}" action="{{ route('p2m.sosialisasi.destroy', $data->id) }}" method="POST" class="d-inline">
                                                            @csrf @method('DELETE')
                                                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete({{ $data->id }})"><i class="me-0 bi bi-trash"></i></button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr x-show="expanded.includes({{ $data->id }})" x-transition.duration.200ms class="bg-light">
                                                <td colspan="11" class="text-start p-4">
                                                    <div class="card border-0">
                                                        <div class="card-body">
                                                            <h5 class="card-title fw-bold text-primary mb-3">Informasi Tambahan</h5>
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
                                                                        <label class="fw-bold text-muted small text-uppercase">Terakhir Diperbarui</label>
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
                                                    <div class="text-muted">Tidak ada data kegiatan sosialisasi</div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-4">
                                
                                {{-- BAGIAN KIRI: Dropdown Jumlah Data --}}
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted small text-nowrap">Tampilkan</span>
                                    
                                    <select class="form-select form-select-sm border-secondary-subtle" 
                                            style="width: auto;" 
                                            onchange="window.location.href = this.value">
                                        
                                        {{-- Loop untuk membuat opsi 10, 25, 50, 100 --}}
                                        @foreach([10, 25, 50, 100] as $num)
                                            <option value="{{ request()->fullUrlWithQuery(['per_page' => $num, 'page' => 1]) }}"
                                                    {{ request('per_page') == $num ? 'selected' : '' }}>
                                                {{ $num }}
                                            </option>
                                        @endforeach
                                        
                                    </select>
                                    
                                    <span class="text-muted small text-nowrap">data / halaman</span>
                                </div>

                                {{-- BAGIAN KANAN: Pagination Links (Halaman 1, 2, 3...) --}}
                                <div>
                                    {{ $sosialisasis->fragment('data-table')->links() }}
                                </div>
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

{{-- STYLE & SCRIPT TETAP SAMA SEPERTI SEBELUMNYA --}}
@push('styles')
@vite('resources/css/tom-select.css')
<style>
    .ts-control { border-radius: 0.375rem !important; border-color: #dee2e6 !important; box-shadow: none !important; }
    .ts-wrapper.focus .ts-control { box-shadow: none !important; border-color: #dee2e6 !important; }

    .input-group .ts-wrapper {
        height: 100%;
    }
    .input-group .ts-control {
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
        height: 100%;
        display: flex;
        align-items: center;
    }

    /* CSS KHUSUS UNTUK TABEL SCROLL & STICKY */
    .custom-table-scroll {
        max-height: 70vh;       /* Batasi tinggi tabel */
        overflow-y: auto;       /* Munculkan scrollbar vertikal */
        position: relative;     /* Agar posisi sticky relative terhadap kotak ini */
        border: 1px solid #dee2e6; /* Border tipis pembatas area scroll */
    }

    /* Memaksa Header Diam di Tempat */
    .custom-table-scroll thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 2;
        
        /* PENTING: Warna background header agar tidak tembus pandang */
        background-color: #f8f9fa !important; 
        
        /* Garis bawah header agar tegas */
        box-shadow: inset 0 -1px 0 #dee2e6;
    }

</style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const configTomSelect = { plugins: ['remove_button', 'clear_button'], persist: false, create: false, maxOptions: null };
        if(document.getElementById('select-satker')) new TomSelect('#select-satker', configTomSelect);
        if(document.getElementById('select-bulan')) new TomSelect('#select-bulan', configTomSelect);
        if(document.getElementById('select-anggaran')) new TomSelect('#select-anggaran', configTomSelect);
        if(document.getElementById('select-sasaran')) new TomSelect('#select-sasaran', configTomSelect);
        if(document.getElementById('select-tahun')) new TomSelect('#select-tahun', configTomSelect);
        if(document.getElementById('select-pegawai')) new TomSelect('#select-pegawai', configTomSelect);

    });

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