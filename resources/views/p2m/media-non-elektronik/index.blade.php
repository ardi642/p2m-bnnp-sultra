@extends('admin')
@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Media Non Elektronik</h1>
                    <p class="text-muted mb-0">Master Data P2M</p>
                </div>
            </div>
            @include('p2m.partials.select-p2m-index')

            @php
                $allFilters = request()->only(['satuan_kerja_id', 'bulan', 'tahun', 'anggaran_pelaksanaan', 'jenis_media', 'search']);
                if (empty($allFilters['tahun'])) { $allFilters['tahun'] = [date('Y')]; }
                $activeFilters = collect($allFilters)->filter(function($value) { return !empty($value); })->count();
            @endphp
            
            <div class="row justify-content-center mb-10" x-data="{ showFilter: true }">
                <div class="col-12 col-lg-12">
                    <div class="card shadow-lg p-5">
                        <div class="card-header bg-white border-0">
                            <h5 class="card-title mb-0 text-center">Data Media Non Elektronik</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('p2m.media_non_elektronik.index') }}" method="GET" class="mb-8">
                                
                                {{-- Saat Enter ditekan, tombol ini yang "diklik" oleh browser, menjalankan filter --}}
                                <button type="submit" style="display: none;" aria-hidden="true"></button>

                                <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                                <input type="hidden" name="sort_order" value="{{ request('sort_order') }}">

                                <div class="row mb-5 align-items-center">
                                    <div class="col-auto">
                                        <div class="d-flex gap-2">
                                            <button type="button" @click="showFilter = !showFilter" class="btn btn-sm transition-all d-flex align-items-center gap-2" :class="showFilter ? 'btn-secondary' : 'btn-primary'">
                                                <i class="bi" :class="showFilter ? 'bi-x-lg' : 'bi-sliders'"></i> <span x-text="showFilter ? 'Tutup Filter' : 'Filter Pencarian Lanjutan'"></span>
                                                @if($activeFilters > 0) <span class="badge bg-warning text-dark border border-dark rounded-pill px-2 ms-1">{{ $activeFilters }} Aktif</span> @endif
                                            </button>
                                            <button type="submit" formaction="{{ route('p2m.media_non_elektronik.export') }}" class="btn btn-success btn-sm text-white d-flex align-items-center gap-2">
                                                <i class="bi bi-file-earmark-excel"></i> <span class="d-none d-md-inline">Export Excel</span>
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

                                <div x-show="showFilter" x-transition.duration.300ms class="mb-4">
                                    <div class="bg-light p-4 rounded-3 border">
                                        <div class="row g-3">
                                            @if ($user->isAdmin())
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Satuan Kerja</label>
                                                <select id="select-satker" name="satuan_kerja_id[]" multiple placeholder="Pilih Satuan Kerja...">
                                                    @foreach($satuanKerjas as $satker)
                                                        <option value="{{ $satker->id }}" {{ in_array($satker->id, request('satuan_kerja_id', [])) ? 'selected' : '' }}>{{ $satker->satuan_kerja }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @endif

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Anggaran</label>
                                                <select id="select-anggaran" name="anggaran_pelaksanaan[]" multiple placeholder="Pilih Anggaran...">
                                                    <option value="DIPA" {{ in_array('DIPA', request('anggaran_pelaksanaan', [])) ? 'selected' : '' }}>DIPA</option>
                                                    <option value="NON DIPA" {{ in_array('NON DIPA', request('anggaran_pelaksanaan', [])) ? 'selected' : '' }}>NON DIPA</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Jenis Media</label>
                                                <select id="select-media" name="jenis_media[]" multiple placeholder="Pilih Media...">
                                                    <option value="Media Cetak" {{ in_array('Media Cetak', request('jenis_media', [])) ? 'selected' : '' }}>Media Cetak</option>
                                                    <option value="Media Luar Ruang" {{ in_array('Media Luar Ruang', request('jenis_media', [])) ? 'selected' : '' }}>Media Luar Ruang</option>
                                                    <option value="Branding Sarana Publik" {{ in_array('Branding Sarana Publik', request('jenis_media', [])) ? 'selected' : '' }}>Branding Sarana Publik</option>
                                                </select>
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Bulan</label>
                                                <select id="select-bulan" name="bulan[]" multiple placeholder="Pilih Bulan...">
                                                    @foreach(range(1, 12) as $m)
                                                        <option value="{{ $m }}" {{ in_array($m, request('bulan', [])) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-3">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Tahun</label>
                                                <select id="select-tahun" name="tahun[]" multiple placeholder="Pilih Tahun...">
                                                    @foreach($years as $year)
                                                        <option value="{{ $year }}" {{ in_array($year, request('tahun', []) ?: [date('Y')]) ? 'selected' : '' }}>{{ $year }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-12 text-end mt-4 pt-2 border-top border-secondary-subtle">
                                                <a href="{{ route('p2m.media_non_elektronik.index') }}" class="btn btn-outline-secondary btn-sm me-2 px-3"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                                                <button type="submit" class="btn btn-primary btn-sm px-4"><i class="bi bi-funnel-fill"></i> Terapkan</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            
                            <div class="custom-table-scroll mb-3" id="data-table">
                                <table class="table table-hover mb-0" x-data="{ expanded: [] }">
                                    <thead class="table-light">
                                        <tr class="text-center align-middle">
                                            <th>No</th>
                                            @foreach([
                                                'satuan_kerja' => 'Satker',
                                                'anggaran_pelaksanaan' => 'Anggaran',
                                                'jenis_media' => 'Media',
                                                'tanggal_pelaksanaan' => 'Mulai',
                                                'durasi_pelaksanaan' => 'Durasi'
                                            ] as $field => $label)
                                            <th>
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => $field, 'sort_order' => request('sort_by') == $field && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">
                                                    {{ $label }}
                                                    @if(request('sort_by') == $field) <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'down' : 'up' }}"></i> @else <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i> @endif
                                                </a>
                                            </th>
                                            @endforeach
                                            <th>Tempat</th>
                                            <th>
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => request('sort_by') == 'created_at' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark">
                                                    Dibuat @if(request('sort_by') == 'created_at' || !request('sort_by')) <i class="bi bi-caret-{{ request('sort_order', 'desc') == 'asc' ? 'up' : 'down' }}-fill small"></i> @endif
                                                </a>
                                            </th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($media_non_elektroniks as $data)
                                            <tr class="text-center align-middle">
                                                <td>{{ $media_non_elektroniks->firstItem() + $loop->index }}</td>
                                                <td>{{ $data->satuanKerja->satuan_kerja ?? '-' }}</td>
                                                <td>{{ $data->anggaran_pelaksanaan }}</td>
                                                <td><span class="badge bg-info text-dark">{{ $data->jenis_media }}</span></td>
                                                <td>{{ $data->tanggal_pelaksanaan->translatedFormat('d M Y') }}</td>
                                                <td>{{ $data->durasi_pelaksanaan }} Hari</td>
                                                <td>{{ $data->tempat_kegiatan }}</td>
                                                <td class="small text-muted">
                                                    {{ $data->created_at->translatedFormat('d M Y') }}<br>
                                                    {{ $data->created_at->format('H:i') }}
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        {{-- Tombol Detail (Mata) --}}
                                                        <button type="button" class="btn btn-info btn-sm text-white" 
                                                            @click="expanded.includes({{ $data->id }}) ? expanded = expanded.filter(id => id !== {{ $data->id }}) : expanded.push({{ $data->id }})">
                                                            <i class="me-0 bi" :class="expanded.includes({{ $data->id }}) ? 'bi-eye-slash' : 'bi-eye'"></i>
                                                        </button>
                                                        
                                                        <a href="{{ route('p2m.media_non_elektronik.edit', $data->id) }}" class="btn btn-success btn-sm">
                                                            <i class="me-0 bi bi-pencil-square"></i>
                                                        </a>
                                                        
                                                        <form id="delete-form-{{ $data->id }}" action="{{ route('p2m.media_non_elektronik.destroy', $data->id) }}" method="POST" class="d-inline">
                                                            @csrf @method('DELETE')
                                                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete({{ $data->id }})">
                                                                <i class="me-0 bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>

                                            {{-- BAGIAN DETAIL (EXPANDED ROW) YANG SUDAH DIPERBAIKI --}}
                                            <tr x-show="expanded.includes({{ $data->id }})" x-transition.duration.200ms class="bg-light">
                                                <td colspan="9" class="text-start p-4">
                                                    <div class="card border-0 shadow-sm">
                                                        <div class="card-body">
                                                            <h5 class="card-title fw-bold text-primary mb-3">Informasi Tambahan</h5>
                                                            
                                                            <div class="row">
                                                                {{-- Baris 1: Link Dokumentasi --}}
                                                                <div class="col-md-12 mb-3">
                                                                    <div class="mb-0">
                                                                        <label class="fw-bold text-muted small text-uppercase">Link Kelengkapan / Dokumentasi</label>
                                                                        <div class="d-flex align-items-center mt-1">
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

                                                                {{-- Baris 2: Dibuat Pada --}}
                                                                <div class="col-md-6">
                                                                    <div class="mb-0">
                                                                        <label class="fw-bold text-muted small text-uppercase">Dibuat Pada</label>
                                                                        <div class="d-flex align-items-center mt-1 text-dark">
                                                                            <i class="bi bi-clock fs-5 me-2 text-secondary"></i>
                                                                            {{ $data->created_at->locale('id')->translatedFormat('l, d F Y H:i') }}
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                {{-- Baris 2: Terakhir Diperbarui --}}
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
                                            <tr><td colspan="9" class="text-center p-4 text-muted">Tidak ada data Media Non Elektronik</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href = this.value">
                                    @foreach([10, 25, 50, 100] as $num)
                                        <option value="{{ request()->fullUrlWithQuery(['per_page' => $num, 'page' => 1]) }}" {{ request('per_page') == $num ? 'selected' : '' }}>{{ $num }}</option>
                                    @endforeach
                                </select>
                                <div>{{ $media_non_elektroniks->fragment('data-table')->links() }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('styles') @vite('resources/css/tom-select.css') 
<style> .custom-table-scroll { max-height: 70vh; overflow-y: auto; position: relative; border: 1px solid #dee2e6; } .custom-table-scroll thead th { position: sticky; top: 0; z-index: 2; background-color: #f8f9fa; box-shadow: inset 0 -1px 0 #dee2e6; } </style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const config = { plugins: ['remove_button', 'clear_button'], persist: false, create: false };
        if(document.getElementById('select-satker')) new TomSelect('#select-satker', config);
        if(document.getElementById('select-anggaran')) new TomSelect('#select-anggaran', config);
        if(document.getElementById('select-media')) new TomSelect('#select-media', config);
        if(document.getElementById('select-bulan')) new TomSelect('#select-bulan', config);
        if(document.getElementById('select-tahun')) new TomSelect('#select-tahun', config);
    });
    window.confirmDelete = function(id) {
        Swal.fire({ title: 'Yakin hapus?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya', cancelButtonText: 'Batal' }).then((result) => { if (result.isConfirmed) document.getElementById('delete-form-' + id).submit(); });
    }
    @if(session('success')) Swal.fire({ icon: 'success', title: "{{ session('message') }}", toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }); @endif
</script>
@endpush