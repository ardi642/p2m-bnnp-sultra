@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Kegiatan P2M</h1>
                    <p class="text-muted mb-0">Master Data P2M</p>
                </div>
            </div>
            
            @include('p2m.partials.select-p2m-index')

            {{-- LOGIKA HITUNG JUMLAH FILTER AKTIF --}}
            @php
                $allFilters = request()->only([
                    'satuan_kerja_id', 'bulan', 'tahun', 'anggaran_pelaksanaan', 
                    'sasaran_kegiatan', 'search', 'pegawai_nips'
                ]);

                if (empty($allFilters['tahun'])) {
                    $allFilters['tahun'] = [date('Y')];
                }

                $activeFilters = collect($allFilters)->filter(function($value) {
                    return !empty($value);
                })->count(); 
            @endphp
            
            <div class="row justify-content-center mb-10" x-data="{ showFilter: true }">
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
                            
                            {{-- FORM FILTER --}}
                            <form action="{{ route('p2m.sosialisasi.index') }}" method="GET" class="mb-8">
                                <button type="submit" style="display: none;" aria-hidden="true"></button>
                                <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                                <input type="hidden" name="sort_order" value="{{ request('sort_order') }}">

                                {{-- TOOLBAR ATAS --}}
                                <div class="row mb-5 align-items-center">
                                    <div class="col-auto">
                                        <div class="d-flex gap-2">
                                            <button type="button" @click="showFilter = !showFilter" 
                                                    class="btn btn-sm transition-all d-flex align-items-center gap-2"
                                                    :class="showFilter ? 'btn-secondary' : 'btn-primary'">
                                                <i class="bi" :class="showFilter ? 'bi-x-lg' : 'bi-sliders'"></i> 
                                                <span x-text="showFilter ? 'Tutup Filter' : 'Filter Pencarian Lanjutan'"></span>
                                                @if($activeFilters > 0)
                                                    <span class="badge bg-warning text-dark border border-dark rounded-pill px-2 ms-1">
                                                        {{ $activeFilters }} Aktif
                                                    </span>
                                                @endif
                                            </button>

                                            <button type="submit" formaction="{{ route('p2m.sosialisasi.export') }}"
                                                    class="btn btn-success btn-sm text-white d-flex align-items-center gap-2"
                                                    title="Export data sesuai filter yang aktif">
                                                <i class="bi bi-file-earmark-excel"></i> 
                                                <span class="d-none d-md-inline">Export Excel</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-auto ms-auto">
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="search" class="form-control" placeholder="Pencarian..." value="{{ request('search') }}">
                                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
                                        </div>
                                    </div>
                                </div>

                                {{-- PANEL FILTER --}}
                                <div x-show="showFilter" x-transition.duration.300ms class="mb-4">
                                    <div class="bg-light p-4 rounded-3 border">
                                        <div class="row g-3">
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

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Anggaran</label>
                                                <select id="select-anggaran" name="anggaran_pelaksanaan[]" multiple placeholder="Pilih Anggaran..." autocomplete="off">
                                                    <option value="DIPA" {{ in_array('DIPA', request('anggaran_pelaksanaan', [])) ? 'selected' : '' }}>DIPA</option>
                                                    <option value="NON DIPA" {{ in_array('NON DIPA', request('anggaran_pelaksanaan', [])) ? 'selected' : '' }}>NON DIPA</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Bulan Pelaksanaan</label>
                                                <select id="select-bulan" name="bulan[]" multiple placeholder="Pilih Bulan..." autocomplete="off">
                                                    @foreach(range(1, 12) as $m)
                                                        <option value="{{ $m }}" {{ in_array($m, request('bulan', [])) ? 'selected' : '' }}>
                                                            {{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Tahun Pelaksanaan</label>
                                                <select id="select-tahun" name="tahun[]" multiple placeholder="Pilih Tahun..." autocomplete="off">
                                                    @foreach($years as $year)
                                                        <option value="{{ $year }}" {{ in_array($year, request('tahun', [date('Y')])) ? 'selected' : '' }}>
                                                            {{ $year }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Sasaran Kegiatan</label>
                                                <select id="select-sasaran" name="sasaran_kegiatan[]" multiple placeholder="Pilih Sasaran..." autocomplete="off">
                                                    <option value="lingkungan pendidikan" {{ in_array('lingkungan pendidikan', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Lingkungan Pendidikan</option>
                                                    <option value="lingkungan kerja" {{ in_array('lingkungan kerja', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Lingkungan Kerja</option>
                                                    <option value="lingkungan masyarakat" {{ in_array('lingkungan masyarakat', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Lingkungan Masyarakat</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Pegawai</label>
                                                <div class="input-group" x-data="{ logic: '{{ request('pegawai_logic', 'OR') }}' }">
                                                    <button type="button" class="btn d-flex align-items-center gap-2 fw-bold"
                                                            :class="logic === 'AND' ? 'btn-danger text-white' : 'btn-outline-secondary bg-white text-secondary'"
                                                            @click="logic = logic === 'OR' ? 'AND' : 'OR'">
                                                        <i class="bi" :class="logic === 'AND' ? 'bi-check-all' : 'bi-check'"></i>
                                                        <span x-text="logic === 'AND' ? 'SEMUA (AND)' : 'SALAH SATU (OR)'" style="font-size: 0.8rem;"></span>
                                                    </button>
                                                    <input type="hidden" name="pegawai_logic" :value="logic">
                                                    <div style="flex-grow: 1;">
                                                        <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Pilih Pegawai..." autocomplete="off">
                                                            @foreach($pegawais as $pgw)
                                                                <option value="{{ $pgw->nip }}" {{ in_array($pgw->nip, request('pegawai_nips', [])) ? 'selected' : '' }}>
                                                                    {{ $pgw->nama }} - NIP: {{ $pgw->nip }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 text-end mt-4 pt-2 border-top border-secondary-subtle">
                                                <a href="{{ route('p2m.sosialisasi.index') }}" class="btn btn-outline-secondary btn-sm me-2 px-3">
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
                                            <th>No</th>
                                            <th><a href="{{ request()->fullUrlWithQuery(['sort_by' => 'satuan_kerja', 'sort_order' => request('sort_by') == 'satuan_kerja' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">Satuan Kerja @if(request('sort_by') == 'satuan_kerja') <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'alpha-down' : 'alpha-down-alt' }}"></i> @else <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i> @endif</a></th>
                                            <th><a href="{{ request()->fullUrlWithQuery(['sort_by' => 'anggaran_pelaksanaan', 'sort_order' => request('sort_by') == 'anggaran_pelaksanaan' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">Anggaran @if(request('sort_by') == 'anggaran_pelaksanaan') <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'down' : 'up' }}"></i> @else <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i> @endif</a></th>
                                            <th><a href="{{ request()->fullUrlWithQuery(['sort_by' => 'nama_kegiatan', 'sort_order' => request('sort_by') == 'nama_kegiatan' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">Nama Kegiatan @if(request('sort_by') == 'nama_kegiatan') <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'alpha-down' : 'alpha-down-alt' }}"></i> @else <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i> @endif</a></th>
                                            <th><a href="{{ request()->fullUrlWithQuery(['sort_by' => 'sasaran_kegiatan', 'sort_order' => request('sort_by') == 'sasaran_kegiatan' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">Sasaran @if(request('sort_by') == 'sasaran_kegiatan') <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'alpha-down' : 'alpha-down-alt' }}"></i> @else <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i> @endif</a></th>
                                            <th><a href="{{ request()->fullUrlWithQuery(['sort_by' => 'tanggal_pelaksanaan', 'sort_order' => request('sort_by') == 'tanggal_pelaksanaan' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">Tanggal @if(request('sort_by') == 'tanggal_pelaksanaan') <i class="bi bi-sort-numeric-{{ request('sort_order') == 'asc' ? 'down' : 'up-alt' }}"></i> @else <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i> @endif</a></th>
                                            <th><a href="{{ request()->fullUrlWithQuery(['sort_by' => 'tempat_kegiatan', 'sort_order' => request('sort_by') == 'tempat_kegiatan' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">Tempat @if(request('sort_by') == 'tempat_kegiatan') <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'alpha-down' : 'alpha-down-alt' }}"></i> @else <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i> @endif</a></th>
                                            <th>Nama Pegawai</th>
                                            <th><a href="{{ request()->fullUrlWithQuery(['sort_by' => 'jumlah_peserta', 'sort_order' => request('sort_by') == 'jumlah_peserta' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">Peserta @if(request('sort_by') == 'jumlah_peserta') <i class="bi bi-sort-numeric-{{ request('sort_order') == 'asc' ? 'down' : 'up-alt' }}"></i> @else <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i> @endif</a></th>
                                            <th><a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => (request('sort_by') == 'created_at' && request('sort_order') == 'asc') ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-block text-nowrap">Dibuat @if(!request('sort_by') || request('sort_by') == 'created_at') <i class="bi bi-caret-{{ request('sort_order', 'desc') == 'asc' ? 'up' : 'down' }}-fill small ms-1"></i> @else <i class="bi bi-arrow-down-up text-muted opacity-25 small ms-1"></i> @endif</a></th>
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
                                                <td>{{ $data->tanggal_pelaksanaan->translatedFormat('d M Y') }}</td>
                                                <td>{{ $data->tempat_kegiatan }}</td>
                                                <td class="text-start">
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach($data->pegawai->sortBy('nama') as $pegawai)
                                                            <span class="badge bg-primary">{{ $pegawai->nama }}</span>
                                                        @endforeach
                                                    </div>
                                                </td>
                                                <td>{{ $data->jumlah_peserta }}</td>
                                                <td class="small text-muted">
                                                    {{ $data->created_at->format('d M Y') }}<br>
                                                    {{ $data->created_at->format('H:i') }}
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <button type="button" class="btn btn-info btn-sm text-white" 
                                                                @click="expanded.includes({{ $data->id }}) ? expanded = expanded.filter(id => id !== {{ $data->id }}) : expanded.push({{ $data->id }})">
                                                            <i class="me-0 bi" :class="expanded.includes({{ $data->id }}) ? 'bi-eye-slash' : 'bi-eye'"></i> 
                                                        </button>
                                                        @if (auth()->user()->hasRole('operator'))
                                                            <a href="{{ route('p2m.sosialisasi.edit', $data->id) }}" class="btn btn-success btn-sm"><i class="me-0 bi bi-pencil-square"></i></a>
                                                            <form id="delete-form-{{ $data->id }}" action="{{ route('p2m.sosialisasi.destroy', $data->id) }}" method="POST" class="d-inline">
                                                                @csrf @method('DELETE')
                                                                <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete({{ $data->id }})"><i class="me-0 bi bi-trash"></i></button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>

                                            {{-- EXPANDED ROW: DETAIL SIMPEL & FILE CARD HYBRID --}}
                                            <tr x-show="expanded.includes({{ $data->id }})" x-transition.duration.300ms>
                                                <td colspan="11" class="p-0 border-0">
                                                    <div class="bg-light p-3 border-bottom border-start border-end shadow-inner">
                                                        <div class="bg-white rounded-3 shadow-sm p-4 border-start border-3 border-primary">
                                                            
                                                            {{-- 1. INFORMASI SIMPEL (DIBUAT & DIUPDATE SAJA) --}}
                                                            <div class="row g-4 mb-4 pb-3 border-bottom border-secondary-subtle">
                                                                <div class="col-md-6 text-start">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="bi bi-clock-history fs-4 text-secondary me-3"></i>
                                                                        <div>
                                                                            <span class="text-uppercase text-muted x-small fw-bold d-block">Dibuat Pada</span>
                                                                            <span class="fs-6 text-dark fw-medium">{{ $data->created_at->translatedFormat('l, d F Y H:i') }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6 text-start">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="bi bi-pencil-square fs-4 text-secondary me-3"></i>
                                                                        <div>
                                                                            <span class="text-uppercase text-muted x-small fw-bold d-block">Terakhir Diupdate</span>
                                                                            <span class="fs-6 text-dark fw-medium">{{ $data->updated_at->translatedFormat('l, d F Y H:i') }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            {{-- 2. DOKUMENTASI & LAPORAN --}}
                                                            <div>
                                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                                    <h6 class="fw-bold text-uppercase text-secondary mb-0 small ls-1 d-flex align-items-center">
                                                                        <i class="bi bi-folder2-open text-warning me-2 fs-5"></i> 
                                                                        <span>Dokumentasi & Laporan</span>
                                                                    </h6>
                                                                    <span class="badge bg-secondary rounded-pill px-3">{{ $data->dokumentasi->count() }} File</span>
                                                                </div>

                                                                @if($data->dokumentasi->count() > 0)
                                                                    <div class="row g-3">
                                                                        @foreach($data->dokumentasi as $doc)
                                                                            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                                                                                <div class="card h-100 border border-secondary-subtle shadow-sm file-card position-relative overflow-hidden group hover-lift">
                                                                                    
                                                                                    {{-- Thumbnail Area --}}
                                                                                    <div class="ratio ratio-1x1 bg-light border-bottom d-flex align-items-center justify-content-center overflow-hidden position-relative">
                                                                                        @if(Str::contains($doc->tipe_file, 'image'))
                                                                                            <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100" loading="lazy">
                                                                                        @elseif(Str::contains($doc->tipe_file, 'pdf'))
                                                                                            <div class="text-danger"><i class="bi bi-file-earmark-pdf-fill display-3 filter-drop-shadow"></i></div>
                                                                                        @elseif(Str::contains($doc->tipe_file, ['word', 'officedocument']))
                                                                                            <div class="text-primary"><i class="bi bi-file-earmark-word-fill display-3 filter-drop-shadow"></i></div>
                                                                                        @elseif(Str::contains($doc->tipe_file, ['excel', 'spreadsheet']))
                                                                                            <div class="text-success"><i class="bi bi-file-earmark-excel-fill display-3 filter-drop-shadow"></i></div>
                                                                                        @else
                                                                                            <div class="text-secondary"><i class="bi bi-file-earmark-text-fill display-3"></i></div>
                                                                                        @endif
                                                                                    </div>

                                                                                    {{-- Card Footer (Info + Tombol Logic) --}}
                                                                                    <div class="card-body p-2 text-center bg-white d-flex flex-column justify-content-between">
                                                                                        <div class="mb-2">
                                                                                            <div class="fw-semibold text-dark text-truncate small mb-1" title="{{ $doc->nama_file_asli }}">
                                                                                                {{ $doc->nama_file_asli }}
                                                                                            </div>
                                                                                            <div class="text-muted x-small font-monospace" style="font-size: 0.7rem;">
                                                                                                {{-- Logic Ukuran File KB/MB --}}
                                                                                                {{ $doc->ukuran_file >= 1048576 
                                                                                                    ? number_format($doc->ukuran_file / 1048576, 2) . ' MB' 
                                                                                                    : number_format($doc->ukuran_file / 1024, 0) . ' KB' }}
                                                                                            </div>
                                                                                        </div>
                                                                                        
                                                                                        {{-- LOGIC TOMBOL BERDASARKAN TIPE FILE --}}
                                                                                        @if(Str::contains($doc->tipe_file, ['image', 'pdf', 'video']))
                                                                                            {{-- Hybrid: Lihat & Unduh --}}
                                                                                            <div class="d-flex gap-1">
                                                                                                <a href="{{ Storage::url($doc->path_file) }}" target="_blank" class="btn btn-outline-info btn-sm w-50 py-1 px-0" style="font-size: 0.75rem;" title="Lihat Preview">
                                                                                                    <i class="bi bi-eye"></i> Lihat
                                                                                                </a>
                                                                                                <a href="{{ route('dokumentasi.download', $doc->id) }}" class="btn btn-outline-primary btn-sm w-50 py-1 px-0" style="font-size: 0.75rem;" title="Unduh File">
                                                                                                    <i class="bi bi-download"></i> Unduh
                                                                                                </a>
                                                                                            </div>
                                                                                        @else
                                                                                            {{-- Single: Hanya Unduh --}}
                                                                                            <a href="{{ route('dokumentasi.download', $doc->id) }}" class="btn btn-primary btn-sm w-100 py-1" style="font-size: 0.75rem;" title="Unduh File">
                                                                                                <i class="bi bi-download me-1"></i> Unduh
                                                                                            </a>
                                                                                        @endif
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <div class="alert alert-light border border-secondary-subtle d-flex align-items-center justify-content-center p-4 rounded-3 text-center flex-column" role="alert">
                                                                        <div class="bg-secondary bg-opacity-10 p-3 rounded-circle mb-3">
                                                                            <i class="bi bi-folder-x fs-1 text-secondary opacity-50"></i>
                                                                        </div>
                                                                        <div class="fw-bold text-dark">Belum ada dokumentasi</div>
                                                                        <div class="small text-muted">Silakan edit data ini untuk mengupload foto atau laporan kegiatan.</div>
                                                                    </div>
                                                                @endif
                                                            </div>

                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="11" class="text-center p-4"><div class="text-muted">Tidak ada data</div></td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- PAGINATION --}}
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted small text-nowrap">Tampilkan</span>
                                    <select class="form-select form-select-sm border-secondary-subtle" style="width: auto;" onchange="window.location.href = this.value">
                                        @foreach([10, 25, 50, 100] as $num)
                                            <option value="{{ request()->fullUrlWithQuery(['per_page' => $num, 'page' => 1]) }}" {{ request('per_page') == $num ? 'selected' : '' }}>{{ $num }}</option>
                                        @endforeach
                                    </select>
                                    <span class="text-muted small text-nowrap">data / halaman</span>
                                </div>
                                <div>{{ $sosialisasis->fragment('data-table')->links() }}</div>
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
    .ts-control { border-radius: 0.375rem !important; border-color: #dee2e6 !important; box-shadow: none !important; }
    .ts-wrapper.focus .ts-control { box-shadow: none !important; border-color: #dee2e6 !important; }
    .input-group .ts-wrapper { height: 100%; }
    .input-group .ts-control { border-top-left-radius: 0 !important; border-bottom-left-radius: 0 !important; height: 100%; display: flex; align-items: center; }
    .custom-table-scroll { max-height: 70vh; overflow-y: auto; position: relative; border: 1px solid #dee2e6; }
    .custom-table-scroll thead th { position: sticky !important; top: 0 !important; z-index: 2; background-color: #f8f9fa !important; box-shadow: inset 0 -1px 0 #dee2e6; }
    
    /* CSS BARU UNTUK KARTU FILE & DETAIL */
    .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .hover-lift:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
    .filter-drop-shadow { filter: drop-shadow(0 2px 3px rgba(0,0,0,0.1)); }
    .x-small { font-size: 0.75rem; }
    .ls-1 { letter-spacing: 0.05em; }
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
            title: 'Apakah Anda yakin?', text: "Data yang dihapus tidak dapat dikembalikan!", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!', cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) { document.getElementById('delete-form-' + id).submit(); }
        });
    }
    @if(session('success'))
        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, didOpen: (toast) => { toast.addEventListener('mouseenter', Swal.stopTimer); toast.addEventListener('mouseleave', Swal.resumeTimer); } });
        Toast.fire({ icon: 'success', title: "{{ session('message') }}" });
    @endif
</script>
@endpush