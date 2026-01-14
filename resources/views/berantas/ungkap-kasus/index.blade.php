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
                return !empty($value) && ($value !== ['']); 
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
                                        <div class="col-12">
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
                                        <div class="col-md-6 {{ Auth::user()->hasRole('admin') ? 'col-lg-6' : 'col-lg-4' }}">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Kategori Barang Bukti</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-kategori-bb" name="kategori_bb[]" multiple placeholder="Kategori (Kosongkan untuk Semua)..." autocomplete="off">
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

                                        {{-- Row 3: Dinamis Narkotika & Non-Narkotika --}}
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

                                        {{-- Dinamis: Nama Barang Non-Narkotika (MODIFIED: CREATE MODE & NO ICON) --}}
                                        <div class="col-lg-6" id="wrapper-non-narkotika" style="display: none;">
                                            <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Nama Barang (Non-Narkotika)</label>
                                            <div class="shadow-sm bg-white rounded">
                                                <select id="select-non-narkotika" name="search_non_narkotika[]" multiple placeholder="Ketik nama barang lalu Enter..." autocomplete="off">
                                                    @if(request('search_non_narkotika'))
                                                        @foreach(request('search_non_narkotika') as $val)
                                                            <option value="{{ $val }}" selected>{{ $val }}</option>
                                                        @endforeach
                                                    @endif
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
                                        <tr class="align-top" :class="expanded.includes({{ $item->id }}) ? 'bg-light' : ''">
                                            <td class="text-center fw-bold text-secondary ps-3 py-3">{{ $kasus->firstItem() + $loop->index }}</td>
                                            
                                            <td class="text-start py-3">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-normal shadow-sm text-wrap text-start">
                                                    {{ $item->satuanKerja->satuan_kerja ?? '-' }}
                                                </span>
                                            </td>

                                            <td class="text-start py-3">
                                                <a href="#" class="text-decoration-none fw-bold text-dark" 
                                                   @click.prevent="expanded.includes({{ $item->id }}) ? expanded = expanded.filter(id => id !== {{ $item->id }}) : expanded.push({{ $item->id }})">
                                                    {{ $item->nomor_lkn }}
                                                </a>
                                            </td>

                                            <td class="text-start py-3">
                                                <span class="small text-muted text-nowrap">{{ $item->tanggal_kejadian->locale('id')->translatedFormat('d M Y') }}</span>
                                            </td>

                                            <td class="text-start py-3">
                                                @if($item->tersangka->count() > 0)
                                                    <div class="d-flex flex-column gap-2">
                                                        @foreach($item->tersangka as $t)
                                                            <div class="d-flex align-items-center bg-white p-1 rounded border shadow-sm" style="width: fit-content;">
                                                                <i class="bi bi-person-fill text-secondary mx-1 small"></i>
                                                                <div>
                                                                    <span class="small fw-bold text-dark">{{ $t->nama_tersangka }}</span>
                                                                    <span class="text-muted ms-1 small" style="font-size: 0.7rem;">({{ $t->jenis_kelamin }})</span>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-muted small fst-italic">-</span>
                                                @endif
                                            </td>

                                            <td class="text-start py-3">
                                                @if($item->barangBukti->count() > 0)
                                                    <div class="d-flex flex-column gap-1">
                                                        @foreach($item->barangBukti as $bb)
                                                            <div class="small d-flex align-items-center">
                                                                @if($bb->kategori === 'Narkotika') 
                                                                    <i class="bi bi-capsule text-danger me-2" title="Narkotika"></i> 
                                                                @else 
                                                                    <i class="bi bi-box-seam text-success me-2" title="Non-Narkotika"></i> 
                                                                @endif
                                                                <span class="text-dark me-1 fw-semibold">{{ $bb->nama_barang }}</span>
                                                                <span class="text-muted">({{ $formatAngka($bb->kuantitas) }} {{ $bb->satuan }})</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-muted small fst-italic">-</span>
                                                @endif
                                            </td>

                                            <td class="text-center small py-3">
                                                <div class="text-dark">{{ $item->created_at->format('d/m/Y') }}</div>
                                                <div class="text-muted">{{ $item->created_at->format('H:i') }} WIB</div>
                                            </td>

                                            <td class="text-center pe-3 py-3">
                                                <div class="btn-group btn-group-sm shadow-sm">
                                                    <button type="button" class="btn btn-light border text-secondary" 
                                                            :class="expanded.includes({{ $item->id }}) ? 'btn-primary text-white' : 'btn-light border text-secondary'"
                                                            @click="expanded.includes({{ $item->id }}) ? expanded = expanded.filter(id => id !== {{ $item->id }}) : expanded.push({{ $item->id }})"
                                                            title="Lihat Detail">
                                                        <i class="bi" :class="expanded.includes({{ $item->id }}) ? 'bi-chevron-up' : 'bi-eye'"></i>
                                                    </button>

                                                    <a href="{{ route('berantas.ungkap-kasus.edit', $item->id) }}" class="btn btn-light border text-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                    
                                                    <button type="button" class="btn btn-light border text-danger" onclick="confirmDelete({{ $item->id }})" title="Hapus"><i class="bi bi-trash"></i></button>
                                                    <form id="delete-form-{{ $item->id }}" action="{{ route('berantas.ungkap-kasus.destroy', $item->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                                                </div>
                                            </td>
                                        </tr>

                                        {{-- TR DETAIL --}}
                                        <tr x-show="expanded.includes({{ $item->id }})" x-transition>
                                            <td colspan="8" class="p-0 border-0">
                                                <div class="bg-body-tertiary p-4 border-bottom shadow-inner text-start">
                                                    <div class="card border-0 shadow-sm">
                                                        <div class="card-body">
                                                            <h6 class="card-title fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-info-circle me-2"></i>Detail Kasus Lengkap</h6>
                                                            
                                                            <div class="row g-3 text-start">
                                                                {{-- INFO UTAMA --}}
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
                                                                        <dt class="col-sm-4 text-secondary mb-2">Terakhir Diubah</dt>
                                                                        <dd class="col-sm-8 text-dark">{{ $item->updated_at->locale('id')->translatedFormat('l, d F Y H:i') }} WIB</dd>
                                                                    </dl>
                                                                </div>

                                                                <div class="col-12"><hr class="border-secondary-subtle"></div>

                                                                {{-- TERSANGKA DETAIL --}}
                                                                <div class="col-12">
                                                                    <label class="small text-secondary fw-bold text-uppercase mb-2">Detail Tersangka</label>
                                                                    <div class="row g-2">
                                                                        @foreach($item->tersangka as $tsk)
                                                                            <div class="col-md-6 col-lg-4">
                                                                                <div class="p-2 border rounded bg-light d-flex gap-3 align-items-start h-100">
                                                                                    <div style="width: 50px; height: 50px; flex-shrink: 0;">
                                                                                        <img src="{{ $tsk->foto_tersangka ? Storage::url($tsk->foto_tersangka) : asset('assets/images/user-placeholder.png') }}" class="w-100 h-100 rounded-circle object-fit-cover border shadow-sm">
                                                                                    </div>
                                                                                    <div class="small">
                                                                                        <div class="fw-bold text-dark">{{ $tsk->nama_tersangka }}</div>
                                                                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $tsk->jenis_kelamin }} - {{ $tsk->pekerjaan }}</div>
                                                                                        <div class="badge bg-white border text-secondary mt-1 fw-normal">{{ $tsk->tahap }}</div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>

                                                                <div class="col-12 mt-3 pt-3 border-top">
                                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                                        <label class="small text-secondary fw-bold text-uppercase mb-0"><i class="bi bi-paperclip me-1"></i> Lampiran Dokumentasi</label>
                                                                        <span class="badge bg-secondary rounded-pill">{{ $item->dokumentasi->count() }} File</span>
                                                                    </div>
                                                                    @if($item->dokumentasi->count() > 0)
                                                                        <div class="d-flex gap-2 flex-wrap">
                                                                            @foreach($item->dokumentasi as $doc)
                                                                                <div class="btn-group btn-group-sm shadow-sm">
                                                                                    <a href="{{ Storage::url($doc->path_file) }}" target="_blank" class="btn btn-white border d-flex align-items-center gap-2" title="{{ $doc->nama_file_asli }}">
                                                                                        @if(Str::contains($doc->tipe_file, 'image')) <i class="bi bi-file-earmark-image text-primary"></i>
                                                                                        @elseif(Str::contains($doc->tipe_file, 'pdf')) <i class="bi bi-file-earmark-pdf text-danger"></i>
                                                                                        @else <i class="bi bi-file-earmark-text text-secondary"></i> @endif
                                                                                        <span class="d-inline-block text-truncate" style="max-width: 150px;">{{ $doc->nama_file_asli }}</span>
                                                                                    </a>
                                                                                    <a href="{{ route('dokumentasi.download', $doc->id) }}" class="btn btn-light border text-primary" title="Download"><i class="bi bi-download"></i></a>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    @else
                                                                        <div class="text-muted small fst-italic border rounded p-2 text-center bg-light">Tidak ada lampiran dokumen.</div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="text-center py-5 text-muted fst-italic border-bottom">Belum ada data kasus yang diinput.</td></tr>
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
    /* FIX: Dropdown TomSelect di atas Sticky Header */
    .ts-dropdown, .ts-dropdown.single { z-index: 2000 !important; }
    .ts-control { border: none !important; box-shadow: none !important; padding-top: 0.5rem; padding-bottom: 0.5rem; background-color: transparent !important; min-height: 40px; }
    .ts-wrapper.focus .ts-control { box-shadow: none !important; }
    
    .custom-table-scroll { max-height: 70vh; overflow-y: auto; position: relative; border: 1px solid #dee2e6; border-radius: 6px; }
    /* Sticky Header dengan z-index cukup (10) agar di bawah TomSelect (2000) */
    .custom-table-scroll thead th { position: sticky !important; top: 0 !important; z-index: 10; background-color: #f8f9fa !important; box-shadow: inset 0 -1px 0 #dee2e6; }
    
    .btn-xs { padding: 1px 5px; font-size: 0.75rem; }
    .page-link { border: none; color: #6c757d; border-radius: 50% !important; margin: 0 2px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
    .page-item.active .page-link { background-color: #0d6efd; color: white; box-shadow: 0 2px 4px rgba(13,110,253,0.3); }
</style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const configTomSelect = { plugins: ['remove_button', 'clear_button'], persist: false, create: false, maxOptions: null };
        
        // 1. Inisialisasi Filter Standar
        ['select-satker', 'select-bulan', 'select-tahun', 'select-narkotika'].forEach(id => { 
            if(document.getElementById(id)) new TomSelect('#' + id, configTomSelect); 
        });

        // 2. Inisialisasi Filter Kategori BB dengan Listener (Dynamic)
        if(document.getElementById('select-kategori-bb')) {
            const tsKategori = new TomSelect('#select-kategori-bb', {
                ...configTomSelect,
                onChange: function() { updateBBVisibility(); }
            });

            function updateBBVisibility() {
                const selected = tsKategori.getValue(); 
                const wrapperNarkotika = document.getElementById('wrapper-narkotika');
                const wrapperNonNarkotika = document.getElementById('wrapper-non-narkotika');

                // Logic: Jika kosong, hide semua. Jika ada, show sesuai pilihan.
                if (selected.length === 0) {
                    if(wrapperNarkotika) wrapperNarkotika.style.display = 'none';
                    if(wrapperNonNarkotika) wrapperNonNarkotika.style.display = 'none';
                } else {
                    if(wrapperNarkotika) wrapperNarkotika.style.display = selected.includes('Narkotika') ? 'block' : 'none';
                    if(wrapperNonNarkotika) wrapperNonNarkotika.style.display = selected.includes('Non-Narkotika') ? 'block' : 'none';
                }
            }
            // Jalankan saat load
            updateBBVisibility();
        }
        
        // 3. Inisialisasi Pencarian Non-Narkotika (Create Mode & No Icon)
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