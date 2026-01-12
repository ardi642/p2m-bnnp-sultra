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
            <a href="{{ route('berantas.ungkap-kasus.create') }}" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                <i class="bi bi-plus-lg"></i> Tambah Kasus
            </a>
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

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><strong>Gagal!</strong> {{ session('error') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- PHP HELPER --}}
        @php
            // Ambil semua filter dari request
            $allFilters = request()->only(['satuan_kerja_id', 'bulan', 'tahun', 'search', 'kategori_bb', 'narkotika_ids', 'search_non_narkotika']);
            
            // Inisialisasi default tahun jika kosong
            if (empty($allFilters['tahun'])) { 
                $allFilters['tahun'] = [date('Y')]; 
            }
            
            // Hitung filter aktif untuk indikator badge pada tombol filter
            $activeFilters = collect($allFilters)->filter(function($value) { 
                return !empty($value); 
            })->count();
            
            // Helper untuk link pengurutan (sorting)
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

            // Helper format angka (koma sebagai desimal)
            $formatAngka = function($nilai) {
                return str_replace('.', ',', (string)(float)$nilai);
            };
        @endphp

        <div class="row justify-content-center mb-5" x-data="{ showFilter: true }">
            <div class="col-12">
                <div class="card border-0 shadow-sm"> 
                    
                    {{-- CARD HEADER --}}
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
                            <h5 class="card-title mb-0 fw-bold text-secondary"><i class="bi bi-table me-2"></i>Data Kasus</h5>
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
                        <form action="{{ route('berantas.ungkap-kasus.index') }}" method="GET">
                            <input type="hidden" name="sort_by" value="{{ request('sort_by', 'created_at') }}">
                            <input type="hidden" name="sort_order" value="{{ request('sort_order', 'desc') }}">

                            <div x-show="showFilter" x-transition class="mb-4 px-3 px-lg-0 pt-3 pt-lg-0">
                                <div class="bg-body-tertiary p-4 rounded-3 border">
                                    <div class="row g-3 text-start">
                                        {{-- Row 1: Search & Satker --}}
                                        <div class="{{ Auth::user()->hasRole('admin') ? 'col-lg-6' : 'col-12' }}">
                                            <label class="form-label fw-bold small text-secondary text-uppercase">Kata Kunci</label>
                                            <div class="input-group shadow-sm">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                                <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari No LKN, Nama Tersangka, TKP..." value="{{ request('search') }}">
                                            </div>
                                        </div>
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

                                        {{-- Row 2: Filter Kategori BB (Default Kosong) --}}
                                        <div class="col-lg-4">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Kategori Barang Bukti</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-kategori-bb" name="kategori_bb[]" multiple placeholder="Kategori (Kosongkan untuk Semua)..." autocomplete="off">
                                                    <option value="Narkotika" {{ in_array('Narkotika', request('kategori_bb', [])) ? 'selected' : '' }}>Narkotika</option>
                                                    <option value="Non-Narkotika" {{ in_array('Non-Narkotika', request('kategori_bb', [])) ? 'selected' : '' }}>Non-Narkotika</option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Dinamis: Jenis Narkotika --}}
                                        <div class="col-lg-4" id="wrapper-narkotika" style="display: none;">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Jenis Narkotika</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-narkotika" name="narkotika_ids[]" multiple placeholder="Pilih Narkotika..." autocomplete="off">
                                                    @foreach($masterNarkotika as $n)
                                                        <option value="{{ $n->id }}" {{ in_array($n->id, request('narkotika_ids', [])) ? 'selected' : '' }}>{{ $n->nama_narkotika }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Dinamis: Nama Barang Non-Narkotika --}}
                                        <div class="col-lg-4" id="wrapper-non-narkotika" style="display: none;">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Nama Barang (Non-Narkotika)</label>
                                            <div class="input-group shadow-sm">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-box-seam"></i></span>
                                                <input type="text" name="search_non_narkotika" class="form-control border-start-0 ps-0" placeholder="Cari nama barang..." value="{{ request('search_non_narkotika') }}">
                                            </div>
                                        </div>

                                        {{-- Row 3: Bulan & Tahun --}}
                                        <div class="col-lg-2">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Bulan</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-bulan" name="bulan[]" multiple placeholder="Bulan..." autocomplete="off">
                                                    @foreach(range(1, 12) as $m)
                                                        <option value="{{ $m }}" {{ in_array($m, request('bulan', [])) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 text-start">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Tahun</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-tahun" name="tahun[]" multiple placeholder="Tahun..." autocomplete="off">
                                                    @foreach($years as $year)
                                                        <option value="{{ $year }}" {{ in_array($year, request('tahun', [date('Y')])) ? 'selected' : '' }}>{{ $year }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Action Buttons --}}
                                        <div class="col-12 text-end pt-3 border-top mt-4 text-start">
                                            <a href="{{ route('berantas.ungkap-kasus.index') }}" class="btn btn-link text-decoration-none text-muted btn-sm me-2">Reset Filter</a>
                                            <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-funnel-fill me-1"></i> Terapkan</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {{-- EXPORT BUTTON --}}
                            <div class="d-flex justify-content-between align-items-center mb-3 px-3 px-lg-0">
                                <button type="submit" formaction="{{ route('berantas.ungkap-kasus.export') }}" class="btn btn-success btn-sm text-white d-flex align-items-center gap-2 shadow-sm">
                                    <i class="bi bi-file-earmark-excel"></i> <span class="d-none d-lg-inline">Export Excel</span>
                                </button>
                                <div class="text-muted small fst-italic">
                                    Total Data: <strong>{{ $kasus->total() }}</strong>
                                </div>
                            </div>
                        </form>
                        
                        {{-- TABEL DATA --}}
                        <div class="custom-table-scroll mb-3" id="data-table">
                            <table class="table table-hover align-middle mb-0" x-data="{ expanded: [] }">
                                <thead class="bg-light sticky-top">
                                    <tr class="text-center align-middle small text-uppercase text-secondary text-nowrap">
                                        <th class="py-3 bg-light ps-3">No</th>
                                        <th class="py-3 bg-light text-start" style="min-width: 200px;">{!! $sortLink('satuan_kerja', 'Satuan Kerja') !!}</th>
                                        <th class="py-3 bg-light text-start" style="min-width: 150px;">{!! $sortLink('nomor_lkn', 'No. LKN') !!}</th>
                                        <th class="py-3 bg-light text-start" style="min-width: 150px;">{!! $sortLink('tanggal_kejadian', 'Tanggal') !!}</th>
                                        <th class="py-3 bg-light text-start" style="min-width: 320px;">Tersangka</th>
                                        <th class="py-3 bg-light text-start" style="min-width: 200px;">Barang Bukti</th>
                                        <th class="py-3 bg-light" style="min-width: 150px;">{!! $sortLink('created_at', 'Dibuat') !!}</th>
                                        <th class="py-3 bg-light pe-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                    @forelse ($kasus as $item)
                                        @php
                                            $totalBB = $item->barangBukti->count();
                                            $rowspanCase = $totalBB > 0 ? $totalBB : 1; 

                                            $allSuspectsInCase = $item->barangBukti->flatMap->tersangka;
                                            $suspectCounts = $allSuspectsInCase->countBy('id'); 
                                            $uniqueSuspects = $allSuspectsInCase->unique('id')->values();

                                            $groupedBB = $item->barangBukti->groupBy(function($bb) {
                                                return $bb->tersangka->sortBy('id')->pluck('id')->join('-');
                                            });
                                        @endphp

                                        @if($totalBB > 0)
                                            @foreach($groupedBB as $signature => $group)
                                                @foreach($group as $index => $bb)
                                                    <tr class="align-middle" :class="expanded.includes({{ $item->id }}) ? 'bg-light' : ''">
                                                        
                                                        @if($loop->parent->first && $loop->first)
                                                            <td rowspan="{{ $rowspanCase }}" class="text-center fw-bold text-secondary ps-3 border-end">{{ $kasus->firstItem() + $loop->parent->parent->index }}</td>
                                                            <td rowspan="{{ $rowspanCase }}" class="text-start border-end">
                                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle text-wrap text-start">
                                                                    {{ $item->satuanKerja->satuan_kerja ?? 'Unknown' }}
                                                                </span>
                                                            </td>
                                                            <td rowspan="{{ $rowspanCase }}" class="text-start border-end">
                                                                <div class="fw-bold text-dark text-break">{{ $item->nomor_lkn }}</div>
                                                            </td>
                                                            <td rowspan="{{ $rowspanCase }}" class="text-start border-end">
                                                                <div class="small text-muted"><i class="bi bi-calendar-event me-1"></i>{{ \Carbon\Carbon::parse($item->tanggal_kejadian)->locale('id')->translatedFormat('d M Y') }}</div>
                                                            </td>
                                                        @endif

                                                        @if($loop->first)
                                                            <td rowspan="{{ $group->count() }}" class="text-start align-top bg-white border-end">
                                                                @if($bb->tersangka && $bb->tersangka->count() > 0)
                                                                    <div class="d-flex flex-column gap-3 py-2">
                                                                        @foreach($bb->tersangka as $tsk)
                                                                            @php
                                                                                $isRepeated = ($suspectCounts[$tsk->id] ?? 0) > 1;
                                                                                $tskIndex = $uniqueSuspects->where('id', $tsk->id)->keys()->first() + 1;
                                                                            @endphp
                                                                            <div class="pb-2 {{ !$loop->last ? 'border-bottom border-light' : '' }}">
                                                                                <div class="fw-bold text-dark mb-1 d-flex align-items-center justify-content-between">
                                                                                    <span><i class="bi bi-person-fill text-secondary me-1"></i>{{ $tsk->nama_tersangka }}</span>
                                                                                    @if($isRepeated)
                                                                                        <span class="badge bg-light text-secondary border rounded-1 fw-normal" style="font-size: 0.7rem;">T{{ $tskIndex }}</span>
                                                                                    @endif
                                                                                </div>
                                                                                <div class="ps-3 ms-1 small">
                                                                                    <div class="d-flex mb-1">
                                                                                        <span class="text-muted" style="width: 85px; flex-shrink: 0;">JK</span>
                                                                                        <span class="text-dark">: {{ $tsk->jenis_kelamin }}</span>
                                                                                    </div>
                                                                                    <div class="d-flex mb-1">
                                                                                        <span class="text-muted" style="width: 85px; flex-shrink: 0;">Pekerjaan</span>
                                                                                        <span class="text-dark">: {{ $tsk->pekerjaan ?? '-' }}</span>
                                                                                    </div>
                                                                                    <div class="d-flex">
                                                                                        <span class="text-muted" style="width: 85px; flex-shrink: 0;">Tahap</span>
                                                                                        <span class="text-dark fw-bold">: {{ $tsk->tahap ?? '-' }}</span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <span class="text-muted fst-italic small opacity-50 ps-2">- Tidak ada pemilik -</span>
                                                                @endif
                                                            </td>
                                                        @endif

                                                        <td class="text-start bg-white border-end">
                                                            <div class="d-flex align-items-center justify-content-between">
                                                                <div>
                                                                    @if($bb->kategori == 'Narkotika') <i class="bi bi-capsule text-danger me-1"></i>
                                                                    @else <i class="bi bi-box-seam text-success me-1"></i> @endif
                                                                    <span class="fw-bold text-dark">{{ $bb->nama_barang }}</span>
                                                                </div>
                                                                <span class="badge bg-light text-dark border ms-2">
                                                                    {{ $formatAngka($bb->kuantitas) }} {{ $bb->satuan }}
                                                                </span>
                                                            </div>
                                                        </td>

                                                        @if($loop->parent->first && $loop->first)
                                                            <td rowspan="{{ $rowspanCase }}" class="text-center border-end align-top pt-3">
                                                                <div class="small text-dark">{{ $item->created_at->format('d/m/Y') }}</div>
                                                                <div class="small text-muted" style="font-size: 0.8em;">{{ $item->created_at->format('H:i') }} WIB</div>
                                                            </td>
                                                            <td rowspan="{{ $rowspanCase }}" class="pe-3 text-center align-top pt-3">
                                                                <div class="btn-group btn-group-sm shadow-sm">
                                                                    <button type="button" class="btn btn-light border text-secondary" 
                                                                        @click="expanded.includes({{ $item->id }}) ? expanded = expanded.filter(id => id !== {{ $item->id }}) : expanded.push({{ $item->id }})">
                                                                        <i class="bi" :class="expanded.includes({{ $item->id }}) ? 'bi-chevron-up text-primary' : 'bi-eye text-secondary'"></i>
                                                                    </button>
                                                                    <a href="{{ route('berantas.ungkap-kasus.edit', $item->id) }}" class="btn btn-light border text-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                                    <button type="button" class="btn btn-light border text-danger" onclick="confirmDelete({{ $item->id }})" title="Hapus"><i class="bi bi-trash"></i></button>
                                                                    <form id="delete-form-{{ $item->id }}" action="{{ route('berantas.ungkap-kasus.destroy', $item->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                                                                </div>
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        @else
                                            {{-- Fallback jika data LKN ada tapi Barang Bukti kosong --}}
                                            <tr>
                                                <td class="text-center fw-bold text-secondary border-end">{{ $kasus->firstItem() + $loop->index }}</td>
                                                <td class="text-start border-end">---</td>
                                                <td class="text-start border-end"><div class="fw-bold">{{ $item->nomor_lkn }}</div></td>
                                                <td class="text-start border-end">---</td>
                                                <td colspan="2" class="text-center text-muted small fst-italic border-end">Data belum lengkap</td>
                                                <td class="text-center border-end">---</td>
                                                <td class="text-center pe-3">---</td>
                                            </tr>
                                        @endif

                                        {{-- DETAIL EXPAND ROW (DOKUMENTASI) --}}
                                        <tr x-show="expanded.includes({{ $item->id }})" x-transition>
                                            <td colspan="9" class="p-0 border-0">
                                                <div class="bg-body-tertiary p-4 border-bottom shadow-inner text-start">
                                                    <div class="card border-0 shadow-sm">
                                                        <div class="card-body">
                                                            <h6 class="card-title fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-info-circle me-2"></i>Detail Kasus Lengkap</h6>
                                                            
                                                            <div class="row g-4 text-start mb-4">
                                                                <div class="col-md-6">
                                                                    <dl class="row mb-0 small">
                                                                        <dt class="col-sm-4 text-secondary mb-2">No. LKN</dt>
                                                                        <dd class="col-sm-8 text-dark fw-bold">{{ $item->nomor_lkn }}</dd>
                                                                        <dt class="col-sm-4 text-secondary mb-2">Tanggal</dt>
                                                                        <dd class="col-sm-8 text-dark">{{ \Carbon\Carbon::parse($item->tanggal_kejadian)->locale('id')->translatedFormat('l, d F Y') }}</dd>
                                                                        <dt class="col-sm-4 text-secondary mb-2">TKP</dt>
                                                                        <dd class="col-sm-8 text-dark">{{ $item->alamat_tkp }}</dd>
                                                                    </dl>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <dl class="row mb-0 small">
                                                                        <dt class="col-sm-4 text-secondary mb-2">Satuan Kerja</dt>
                                                                        <dd class="col-sm-8 text-dark">{{ $item->satuanKerja->satuan_kerja ?? '-' }}</dd>
                                                                        <dt class="col-sm-4 text-secondary mb-2">Dibuat Pada</dt>
                                                                        <dd class="col-sm-8 text-dark">{{ $item->created_at->locale('id')->translatedFormat('l, d F Y H:i') }} WIB</dd>
                                                                    </dl>
                                                                </div>
                                                            </div>

                                                            <hr class="border-secondary-subtle">

                                                            <div class="mt-4">
                                                                <h6 class="fw-bold text-secondary small mb-3"><i class="bi bi-paperclip me-1"></i> Lampiran Dokumentasi</h6>
                                                                @if($item->dokumentasi->count() > 0)
                                                                    <div class="row g-2">
                                                                        @foreach($item->dokumentasi as $doc)
                                                                            <div class="col-lg-4 col-md-6 col-12">
                                                                                <div class="p-2 border rounded bg-light d-flex justify-content-between align-items-center shadow-sm">
                                                                                    <div class="small fw-bold text-dark text-truncate pe-2">{{ $doc->nama_file_asli }}</div>
                                                                                    <div class="d-flex gap-1 flex-shrink-0">
                                                                                        <a href="{{ Storage::url($doc->path_file) }}" target="_blank" class="btn btn-xs btn-outline-info"><i class="bi bi-eye"></i></a>
                                                                                        <a href="{{ Storage::url($doc->path_file) }}" download class="btn btn-xs btn-outline-primary"><i class="bi bi-download"></i></a>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <div class="text-muted small fst-italic text-center py-2">Tidak ada lampiran dokumen.</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-5 text-muted">
                                                Belum ada data kasus ditemukan.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- PAGINATION --}}
                        <div class="card-footer bg-white py-3 border-top-0">
                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-3">
                                <div class="d-flex align-items-center gap-2">
                                    <select class="form-select form-select-sm" style="width: 70px;" onchange="window.location.href = this.value">
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
    .ts-control { border: 1px solid #dee2e6; padding: 0.5rem 0.75rem; border-radius: 0.375rem; min-height: 40px; }
    .custom-table-scroll { max-height: 70vh; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 6px; }
    .custom-table-scroll thead th { position: sticky; top: 0; z-index: 10; background-color: #f8f9fa !important; box-shadow: inset 0 -1px 0 #dee2e6; }
    .btn-xs { padding: 1px 5px; font-size: 0.75rem; }
</style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const configTomSelect = { plugins: ['remove_button', 'clear_button'], persist: false, create: false, maxOptions: null };
        
        // 1. Inisialisasi Kategori BB (Default Kosong)
        const tsKategori = new TomSelect('#select-kategori-bb', {
            ...configTomSelect,
            onChange: function() { 
                updateBBVisibility(); 
            }
        });

        // 2. Inisialisasi Narkotika
        const tsNarkotika = new TomSelect('#select-narkotika', configTomSelect);

        // 3. Inisialisasi Satker, Bulan, Tahun
        ['select-satker', 'select-bulan', 'select-tahun'].forEach(id => { 
            if(document.getElementById(id)) new TomSelect('#' + id, configTomSelect); 
        });

        // Fungsi Toggle Input Dinamis
        function updateBBVisibility() {
            const selected = tsKategori.getValue();
            const wrapperNarkotika = document.getElementById('wrapper-narkotika');
            const wrapperNonNarkotika = document.getElementById('wrapper-non-narkotika');

            // Tampilkan wrapper jika value kategori terpilih ada di array
            wrapperNarkotika.style.display = selected.includes('Narkotika') ? 'block' : 'none';
            wrapperNonNarkotika.style.display = selected.includes('Non-Narkotika') ? 'block' : 'none';
        }

        // Jalankan saat load untuk menyesuaikan tampilan jika ada filter dari URL
        updateBBVisibility();
    });

    window.confirmDelete = function(id) {
        Swal.fire({
            title: 'Hapus Kasus?', 
            text: "Data kasus, tersangka, dan Barang Bukti akan dihapus permanen.", 
            icon: 'warning',
            showCancelButton: true, 
            confirmButtonColor: '#dc3545', 
            confirmButtonText: 'Ya, Hapus', 
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) { document.getElementById('delete-form-' + id).submit(); }
        });
    }
</script>
@endpush