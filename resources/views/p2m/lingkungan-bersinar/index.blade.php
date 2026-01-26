@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1 fw-bold text-dark">Lingkungan Bersinar</h1>
                    <p class="text-muted mb-0">Master Data Kawasan/Wilayah Bersih Narkoba</p>
                </div>
            </div>

            {{-- Alert --}}
            @if(session('success')) 
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div><strong>Berhasil!</strong> {{ session('message') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div> 
            @endif

            @if(session('error')) 
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><strong>Gagal!</strong> {{ session('message') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div> 
            @endif
            
            {{-- Navigasi Pilihan --}}
            @include('p2m.partials.select-p2m-index')

            @php
                // Logic Filter & Sorting
                // TAMBAHAN: 'anggaran_pelaksanaan' dimasukkan ke dalam filter
                $activeFilters = collect(request()->only(['satuan_kerja_id', 'bulan', 'tahun', 'sasaran_kegiatan', 'search', 'pegawai_nips', 'anggaran_pelaksanaan']))->filter()->count();
                if(empty(request('tahun'))) $activeFilters++; 
                
                $sortLink = function($col, $label) {
                    $currentCol = request('sort_by', 'created_at'); 
                    $currentOrder = request('sort_order', 'desc');
                    $newOrder = ($currentCol === $col && $currentOrder === 'desc') ? 'asc' : 'desc';
                    $icon = ($currentCol === $col) ? ($currentOrder === 'desc' ? 'bi-sort-down text-primary' : 'bi-sort-up text-primary') : 'bi-arrow-down-up text-muted opacity-25';
                    $url = request()->fullUrlWithQuery(['sort_by' => $col, 'sort_order' => $newOrder]);
                    return '<a href="'.$url.'" class="text-decoration-none text-secondary fw-bold d-flex align-items-center justify-content-between gap-2">'.$label.' <i class="bi '.$icon.'"></i></a>';
                };
            @endphp
            
            <div class="row justify-content-center mb-5" x-data="{ showFilter: true }">
                <div class="col-12">
                    <div class="card border-0 shadow-sm"> 
                        
                        {{-- Card Header --}}
                        <div class="card-header bg-white py-3 border-bottom">
                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
                                <h5 class="card-title mb-0 fw-bold text-secondary"><i class="bi bi-table me-2"></i>Data Lingkungan Bersinar</h5>
                                <button type="button" @click="showFilter = !showFilter" class="btn btn-sm transition-all d-flex align-items-center gap-2" :class="showFilter ? 'btn-light text-secondary border' : 'btn-primary shadow-sm'">
                                    <i class="bi" :class="showFilter ? 'bi-chevron-up' : 'bi-funnel'"></i> 
                                    <span x-text="showFilter ? 'Sembunyikan Filter' : 'Filter Pencarian'"></span>
                                    @if($activeFilters > 0) <span class="badge bg-danger rounded-pill">{{ $activeFilters }}</span> @endif
                                </button>
                            </div>
                        </div>

                        <div class="card-body p-0 p-lg-4">
                            
                            {{-- Form Filter --}}
                            <form action="{{ route('p2m.lingkungan-bersinar.index') }}" method="GET">
                                <button type="submit" style="display: none;" aria-hidden="true"></button>
                                <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                                <input type="hidden" name="sort_order" value="{{ request('sort_order') }}">

                                <div x-show="showFilter" x-transition class="mb-4 px-3 px-lg-0 pt-3 pt-lg-0">
                                    <div class="bg-body-tertiary p-4 rounded-3 border">
                                        <div class="row g-3 text-start">
                                            
                                            {{-- Kata Kunci --}}
                                            <div class="{{ $user->isAdmin() ? 'col-lg-8' : 'col-12' }}">
                                                <label class="form-label fw-bold small text-secondary text-uppercase">Kata Kunci</label>
                                                <div class="input-group shadow-sm">
                                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                                    <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari Tempat, PJ, dll..." value="{{ request('search') }}">
                                                </div>
                                            </div>

                                            {{-- Satuan Kerja (Admin Only) --}}
                                            @if ($user->isAdmin())
                                                <div class="col-lg-4">
                                                    <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Satuan Kerja</label>
                                                    <div class="shadow-sm bg-white rounded">
                                                        <select id="select-satker" name="satuan_kerja_id[]" multiple placeholder="Pilih Satuan Kerja...">
                                                            @foreach($satuanKerjas as $satker) 
                                                                <option value="{{ $satker->id }}" {{ in_array($satker->id, request('satuan_kerja_id', [])) ? 'selected' : '' }}>{{ $satker->satuan_kerja }}</option> 
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- TAMBAHAN: FILTER ANGGARAN --}}
                                            <div class="col-12 col-lg-3">
                                                <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Anggaran</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-anggaran" name="anggaran_pelaksanaan[]" multiple placeholder="Pilih Anggaran..." autocomplete="off">
                                                        <option value="DIPA" {{ in_array('DIPA', request('anggaran_pelaksanaan', [])) ? 'selected' : '' }}>DIPA</option>
                                                        <option value="NON DIPA" {{ in_array('NON DIPA', request('anggaran_pelaksanaan', [])) ? 'selected' : '' }}>NON DIPA</option>
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Sasaran --}}
                                            <div class="col-12 col-lg-3">
                                                <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Sasaran</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-sasaran" name="sasaran_kegiatan[]" multiple placeholder="Pilih Sasaran...">
                                                        <option value="lingkungan kerja" {{ in_array('lingkungan kerja', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Lingkungan Kerja</option>
                                                        <option value="lingkungan pendidikan" {{ in_array('lingkungan pendidikan', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Lingkungan Pendidikan</option>
                                                        <option value="lingkungan masyarakat" {{ in_array('lingkungan masyarakat', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Lingkungan Masyarakat</option>
                                                        <option value="lingkungan swasta" {{ in_array('lingkungan swasta', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Lingkungan Swasta</option>
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Bulan --}}
                                            <div class="col-6 col-lg-3">
                                                <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Bulan</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-bulan" name="bulan[]" multiple placeholder="Bulan...">
                                                        @foreach(range(1, 12) as $m) 
                                                            <option value="{{ $m }}" {{ in_array($m, request('bulan', [])) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option> 
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Tahun --}}
                                            <div class="col-6 col-lg-3 text-start">
                                                <label class="form-label fw-bold small text-secondary text-uppercase mb-1">Tahun</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-tahun" name="tahun[]" multiple placeholder="Tahun...">
                                                        @foreach($years as $year) 
                                                            <option value="{{ $year }}" {{ in_array($year, request('tahun', [date('Y')])) ? 'selected' : '' }}>{{ $year }}</option> 
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Pegawai --}}
                                            <div class="col-12 col-lg-6">
                                                <label class="form-label fw-bold small text-secondary text-uppercase mb-1 d-block">Penanggung Jawab</label>
                                                <div class="d-flex align-items-stretch shadow-sm bg-white rounded border" x-data="{ logic: '{{ request('pegawai_logic', 'OR') }}' }">
                                                    <button type="button" class="btn rounded-0 rounded-start border-end d-flex align-items-center justify-content-center fw-bold px-3" style="width: 70px;" :class="logic === 'AND' ? 'btn-danger text-white' : 'btn-light text-secondary'" @click="logic = logic === 'OR' ? 'AND' : 'OR'"><span x-text="logic"></span></button>
                                                    <input type="hidden" name="pegawai_logic" :value="logic">
                                                    <div class="flex-grow-1">
                                                        <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Cari Pegawai..." class="border-0">
                                                            @foreach($pegawais as $pgw) 
                                                                <option value="{{ $pgw->nip }}" {{ in_array($pgw->nip, request('pegawai_nips', [])) ? 'selected' : '' }}>{{ $pgw->nama }} ({{ $pgw->nip }})</option> 
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 text-end pt-3 border-top mt-4 text-start">
                                                <a href="{{ route('p2m.lingkungan-bersinar.index') }}" class="btn btn-link text-decoration-none text-muted btn-sm me-2">Reset</a>
                                                <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-funnel-fill me-1"></i> Terapkan</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-end align-items-lg-center mb-3 px-3 px-lg-0">
                                    
                                    <div class="mb-2 mb-lg-0">
                                        <button type="submit" formaction="{{ route('p2m.lingkungan-bersinar.export') }}" class="btn btn-success btn-sm text-white d-flex align-items-center gap-2 px-3 shadow-none">
                                            <i class="bi bi-file-earmark-excel"></i> <span>Export Excel</span>
                                        </button>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <div class="d-flex align-items-center border border-secondary-subtle rounded-3 px-3 py-1 bg-light">
                                            <i class="bi bi-info-circle text-muted me-2" style="font-size: 0.85rem;"></i>
                                            <span class="text-muted" style="font-size: 0.9rem;">Lingkungan bersinar yang terbentuk :</span>
                                            <span class="text-dark ms-1" style="font-size: 0.9rem;">
                                                {{ number_format($totalKegiatan, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            
                            {{-- Tabel Data --}}
                            <div class="custom-table-scroll mb-3" id="data-table">
                                <table class="table table-hover align-middle mb-0" x-data="{ expanded: [] }">
                                    <thead class="bg-light sticky-top">
                                        <tr class="text-center align-middle small text-uppercase text-secondary text-nowrap">
                                            <th class="py-3 bg-light ps-3">No</th>
                                            <th class="py-3 bg-light text-start">{!! $sortLink('satuan_kerja', 'Satuan Kerja') !!}</th>
                                            
                                            {{-- TAMBAHAN: KOLOM ANGGARAN --}}
                                            <th class="py-3 bg-light">{!! $sortLink('anggaran_pelaksanaan', 'Anggaran') !!}</th>
                                            
                                            <th class="py-3 bg-light text-start">{!! $sortLink('nama_tempat_wilayah', 'Nama Tempat/Wilayah') !!}</th>
                                            <th class="py-3 bg-light">{!! $sortLink('sasaran_kegiatan', 'Sasaran') !!}</th>
                                            <th class="py-3 bg-light">{!! $sortLink('tanggal_pencanangan', 'Tgl Pencanangan') !!}</th>
                                            <th class="py-3 bg-light text-start">Penanggung Jawab</th>
                                            <th class="py-3 bg-light">{!! $sortLink('jumlah_penggiat_p4gn', 'Jml Penggiat') !!}</th>
                                            <th class="py-3 bg-light">{!! $sortLink('created_at', 'Dibuat') !!}</th>
                                            <th class="py-3 bg-light pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="border-top-0">
                                        @forelse ($datas as $data)
                                            <tr class="text-center align-middle" :class="expanded.includes({{ $data->id }}) ? 'bg-light' : ''">
                                                <td class="fw-bold text-secondary ps-3">{{ $datas->firstItem() + $loop->index }}</td>
                                                <td class="text-start"><span class="fw-semibold text-dark">{{ $data->satuanKerja->satuan_kerja ?? '-' }}</span></td>
                                                
                                                {{-- TAMBAHAN: BADGE ANGGARAN --}}
                                                <td>
                                                    <span class="badge rounded-pill {{ $data->anggaran_pelaksanaan == 'DIPA' ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-info bg-opacity-10 text-info border border-info border-opacity-25' }}">
                                                        {{ $data->anggaran_pelaksanaan }}
                                                    </span>
                                                </td>

                                                <td class="text-start"><a href="#" class="text-decoration-none fw-bold text-dark" @click.prevent="expanded.includes({{ $data->id }}) ? expanded = expanded.filter(id => id !== {{ $data->id }}) : expanded.push({{ $data->id }})">{{ $data->nama_tempat_wilayah }}</a></td>
                                                <td>
                                                    @php 
                                                        $sasaranClass = match($data->sasaran_kegiatan) { 'sekolah/kampus bersinar' => 'bg-warning', 'pondok pesantren bersinar' => 'bg-primary', 'tempat hiburan bersinar' => 'bg-success', 'tempat wisata bersinar' => 'bg-info', 'industri bersinar' => 'bg-secondary' }; 
                                                    @endphp
                                                    <span class="badge rounded-pill {{ $sasaranClass }} bg-opacity-10 {{ str_replace('bg-', 'text-', $sasaranClass) }} border {{ str_replace('bg-', 'border-', $sasaranClass) }} border-opacity-25 text-capitalize">{{ $data->sasaran_kegiatan }}</span>
                                                </td>
                                                <td class="small text-muted text-nowrap">{{ $data->tanggal_pencanangan->locale('id')->translatedFormat('d M Y') }}</td>
                                                <td class="text-start">
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach($data->pegawai->sortBy('nama') as $pegawai) 
                                                            <span class="badge bg-white border text-secondary fw-normal shadow-sm">{{ $pegawai->nama }}</span> 
                                                        @endforeach 
                                                        @if($data->pegawai->isEmpty())
                                                            <span class="text-muted small">-</span>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td><span class="fw-bold">{{ $data->jumlah_penggiat_p4gn }}</span></td>
                                                <td class="small text-muted text-nowrap text-center">{{ $data->created_at->locale('id')->translatedFormat('d M Y') }}</td>
                                                <td class="pe-3">
                                                    <div class="btn-group btn-group-sm shadow-sm">
                                                        <button type="button" class="btn btn-light border text-secondary" @click="expanded.includes({{ $data->id }}) ? expanded = expanded.filter(id => id !== {{ $data->id }}) : expanded.push({{ $data->id }})"><i class="bi" :class="expanded.includes({{ $data->id }}) ? 'bi-chevron-up text-primary' : 'bi-eye text-secondary'"></i></button>
                                                        @if (auth()->user()->hasRole(['operator_satker', 'operator_p2m']))
                                                            <a href="{{ route('p2m.lingkungan-bersinar.edit', $data->id) }}" class="btn btn-light border text-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                            <button type="button" class="btn btn-light border text-danger" onclick="confirmDelete({{ $data->id }})" title="Hapus"><i class="bi bi-trash"></i></button>
                                                            <form id="delete-form-{{ $data->id }}" action="{{ route('p2m.lingkungan-bersinar.destroy', $data->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            {{-- Detail Row --}}
                                            <tr x-show="expanded.includes({{ $data->id }})" x-transition>
                                                <td colspan="10" class="p-0 border-0">
                                                    <div class="bg-body-tertiary p-4 border-bottom shadow-inner text-start">
                                                        <div class="card border-0 shadow-sm">
                                                            <div class="card-body">
                                                                <h6 class="card-title fw-bold text-primary border-bottom pb-2 mb-3"><i class="bi bi-info-circle me-2"></i>Detail Lengkap</h6>
                                                                <div class="row g-4 text-start">
                                                                    
                                                                    {{-- Kolom Kiri: Info Utama --}}
                                                                    <div class="col-lg-6">
                                                                        <dl class="row mb-0 small text-start">
                                                                            <dt class="col-sm-4 text-secondary mb-2">Nama Tempat</dt>
                                                                            <dd class="col-sm-8 text-dark">{{ $data->nama_tempat_wilayah }}</dd>

                                                                            {{-- TAMBAHAN: DATA ANGGARAN DI DETAIL --}}
                                                                            <dt class="col-sm-4 text-secondary mb-2">Anggaran</dt>
                                                                            <dd class="col-sm-8 text-dark">{{ $data->anggaran_pelaksanaan }}</dd>

                                                                            <dt class="col-sm-4 text-secondary mb-2">Tgl Pencanangan</dt>
                                                                            <dd class="col-sm-8 text-dark">{{ $data->tanggal_pencanangan->translatedFormat('l, d F Y') }}</dd>
                                                                            <dt class="col-sm-4 text-secondary mb-2">Jml Penggiat</dt>
                                                                            <dd class="col-sm-8 text-dark">{{ $data->jumlah_penggiat_p4gn }} Orang</dd>
                                                                            <dt class="col-sm-4 text-secondary mb-2">No HP PJ</dt>
                                                                            <dd class="col-sm-8 text-dark">{{ $data->no_hp_penanggung_jawab ?? '-' }}</dd>
                                                                        </dl>
                                                                    </div>

                                                                    {{-- Kolom Kanan: Timestamps & Pegawai --}}
                                                                    <div class="col-lg-6">
                                                                        <div class="row small mb-3 text-start">
                                                                            <div class="col-md-6 mb-2 text-start">
                                                                                <span class="text-secondary d-block">Dibuat Pada</span>
                                                                                <span class="text-dark">{{ $data->created_at->locale('id')->translatedFormat('l, d F Y H:i') }}</span>
                                                                            </div>
                                                                            <div class="col-md-6 mb-2 text-start">
                                                                                <span class="text-secondary d-block">Terakhir Diubah</span>
                                                                                <span class="text-dark">{{ $data->updated_at->locale('id')->translatedFormat('l, d F Y H:i') }}</span>
                                                                            </div>
                                                                        </div>

                                                                        <div class="mb-4 text-start">
                                                                            <span class="fw-bold text-secondary small d-block mb-2 text-start">Penanggung Jawab Wilayah (Detail):</span>
                                                                            <ul class="list-unstyled mb-0 small text-start">
                                                                                @foreach($data->pegawai->sortBy('nama') as $pegawai) 
                                                                                    @php $isPindah = $pegawai->satuan_kerja_id != $data->satuan_kerja_id; @endphp 
                                                                                    <li class="mb-2 text-start">
                                                                                        <i class="bi bi-person-check-fill me-2 text-success"></i><span class="text-dark">{{ $pegawai->nama }}</span> <span class="text-muted">({{ $pegawai->nip }})</span>
                                                                                        @if($isPindah) 
                                                                                            <br><small class="text-danger fw-bold fst-italic ms-4">Pindah ke: {{ $pegawai->satuanKerja->satuan_kerja ?? 'Luar Satker' }}</small> 
                                                                                        @endif 
                                                                                    </li> 
                                                                                @endforeach 
                                                                            </ul>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    {{-- DOKUMENTASI (Sama seperti modul lain) --}}
                                                                    <div class="col-12 mt-3 text-start">
                                                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                                                            <span class="fw-bold text-secondary small">Dokumentasi & Lampiran</span>
                                                                            <span class="badge bg-secondary rounded-pill">{{ $data->dokumentasi->count() }} File</span>
                                                                        </div>
                                                                        @if($data->dokumentasi->count() > 0) 
                                                                            <div class="row g-2 text-start"> 
                                                                                @foreach($data->dokumentasi as $doc) 
                                                                                    <div class="col-12 col-md-6 col-lg-4 text-start">
                                                                                        <div class="p-2 border rounded bg-light d-flex justify-content-between align-items-center h-100 shadow-sm">
                                                                                            <div class="small fw-bold text-dark text-wrap pe-2 d-flex align-items-center gap-2">
                                                                                                @if(Str::contains($doc->tipe_file, 'image')) <i class="bi bi-file-image text-primary fs-5"></i>
                                                                                                @elseif(Str::contains($doc->tipe_file, 'pdf')) <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
                                                                                                @elseif(Str::contains($doc->tipe_file, 'video')) <i class="bi bi-file-earmark-play text-dark fs-5"></i>
                                                                                                @elseif(Str::contains($doc->tipe_file, ['word', 'officedocument'])) <i class="bi bi-file-earmark-word text-primary fs-5"></i>
                                                                                                @else <i class="bi bi-file-earmark-text text-secondary fs-5"></i> @endif
                                                                                                <span class="text-break">{{ $doc->nama_file_asli }}</span>
                                                                                            </div>
                                                                                            <div class="d-flex gap-1 flex-shrink-0">
                                                                                                @if(Str::contains($doc->tipe_file, ['image', 'pdf', 'video']))
                                                                                                    <a href="{{ Storage::url($doc->path_file) }}" target="_blank" class="btn btn-xs btn-outline-info px-2 py-0" title="Lihat"><i class="bi bi-eye"></i></a>
                                                                                                @endif
                                                                                                <a href="{{ route('dokumentasi.download', $doc->id) }}" class="btn btn-xs btn-outline-primary px-2 py-0" title="Download"><i class="bi bi-download"></i></a>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div> 
                                                                                @endforeach 
                                                                            </div> 
                                                                        @else 
                                                                            <div class="text-muted small fst-italic border rounded p-3 text-center bg-light">Tidak ada dokumentasi.</div> 
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="10" class="text-center py-5 text-muted fst-italic border-bottom">Tidak ada data.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- Footer Table --}}
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
                                    <div>{{ $datas->withQueryString()->links() }}</div>
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
    document.addEventListener("DOMContentLoaded", function() {
        const configTomSelect = { plugins: ['remove_button', 'clear_button'], persist: false, create: false, maxOptions: null };
        
        // TAMBAHAN: 'select-anggaran' ke array ID
        const ids = ['select-satker', 'select-bulan', 'select-sasaran', 'select-tahun', 'select-pegawai', 'select-anggaran'];
        
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
</script>
@endpush