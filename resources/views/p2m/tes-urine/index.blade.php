@extends('admin')

@section('content')
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Kegiatan Tes Urine (Deteksi Dini)</h1>
                    <p class="text-muted mb-0">Master Data P2M</p>
                </div>
            </div>
            
            {{-- Tombol Create --}}
            @include('p2m.partials.select-p2m-index')

            @php
                $allFilters = request()->only(['satuan_kerja_id', 'bulan', 'tahun', 'anggaran_pelaksanaan', 'sasaran_kegiatan', 'search', 'pegawai_nips']);
                if (empty($allFilters['tahun'])) $allFilters['tahun'] = [date('Y')];
                $activeFilters = collect($allFilters)->filter(fn($v) => !empty($v))->count(); 
            @endphp
            
            <div class="row justify-content-center mb-10" x-data="{ showFilter: true }">
                <div class="col-12 col-lg-12">
                    <div class="card shadow-lg p-5">
                        <div class="card-header bg-white border-0">
                            <h5 class="card-title mb-0 text-center">Data Tes Urine / Deteksi Dini</h5>
                        </div>

                        <div class="card-body">
                            {{-- FORM FILTER --}}
                            <form action="{{ route('p2m.tes_urine.index') }}" method="GET" class="mb-8">
                                <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                                <input type="hidden" name="sort_order" value="{{ request('sort_order') }}">

                                <div class="row mb-5 align-items-center">
                                    <div class="col-auto">
                                        <div class="d-flex gap-2">
                                            <button type="button" @click="showFilter = !showFilter" 
                                                    class="btn btn-sm transition-all d-flex align-items-center gap-2"
                                                    :class="showFilter ? 'btn-secondary' : 'btn-primary'">
                                                <i class="bi" :class="showFilter ? 'bi-x-lg' : 'bi-sliders'"></i> 
                                                <span x-text="showFilter ? 'Tutup Filter' : 'Filter Pencarian Lanjutan'"></span>
                                                @if($activeFilters > 0)
                                                    <span class="badge bg-warning text-dark border border-dark rounded-pill px-2 ms-1">{{ $activeFilters }} Aktif</span>
                                                @endif
                                            </button>

                                            <button type="submit" formaction="{{ route('p2m.tes_urine.export') }}"
                                                    class="btn btn-success btn-sm text-white d-flex align-items-center gap-2">
                                                <i class="bi bi-file-earmark-excel"></i> <span class="d-none d-md-inline">Export Excel</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-auto ms-auto">
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="search" class="form-control" placeholder="Cari Instansi, Katim..." value="{{ request('search') }}">
                                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Cari</button>
                                        </div>
                                    </div>
                                </div>

                                {{-- PANEL FILTER ITEMS --}}
                                <div x-show="showFilter" x-transition.duration.300ms class="mb-4">
                                    <div class="bg-light p-4 rounded-3 border">
                                        <div class="row g-3">
                                            @if ($user->isAdmin())
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold small text-muted text-uppercase mb-1">Satuan Kerja</label>
                                                    <select id="select-satker" name="satuan_kerja_id[]" multiple placeholder="Pilih Satker...">
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
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Bulan</label>
                                                <select id="select-bulan" name="bulan[]" multiple placeholder="Pilih Bulan...">
                                                    @foreach(range(1, 12) as $m)
                                                        <option value="{{ $m }}" {{ in_array($m, request('bulan', [])) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F') }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Tahun</label>
                                                <select id="select-tahun" name="tahun[]" multiple placeholder="Pilih Tahun...">
                                                    @foreach($years as $year)
                                                        <option value="{{ $year }}" {{ in_array($year, request('tahun', []) ?: [date('Y')]) ? 'selected' : '' }}>{{ $year }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Sasaran Kegiatan</label>
                                                <select id="select-sasaran" name="sasaran_kegiatan[]" multiple placeholder="Pilih Sasaran...">
                                                    <option value="Instansi Pemerintah" {{ in_array('Instansi Pemerintah', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Instansi Pemerintah</option>
                                                    <option value="Lingkungan Pendidikan" {{ in_array('Lingkungan Pendidikan', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Lingkungan Pendidikan</option>
                                                    <option value="Pekerja Swasta" {{ in_array('Pekerja Swasta', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Pekerja Swasta</option>
                                                    <option value="Lingkungan Masyarakat" {{ in_array('Lingkungan Masyarakat', request('sasaran_kegiatan', [])) ? 'selected' : '' }}>Lingkungan Masyarakat</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small text-muted text-uppercase mb-1">Tim / Katim</label>
                                                <div class="input-group" x-data="{ logic: '{{ request('pegawai_logic', 'OR') }}' }">
                                                    <button type="button" class="btn fw-bold" :class="logic === 'AND' ? 'btn-danger text-white' : 'btn-outline-secondary bg-white text-secondary'" @click="logic = logic === 'OR' ? 'AND' : 'OR'">
                                                        <span x-text="logic === 'AND' ? 'AND' : 'OR'"></span>
                                                    </button>
                                                    <input type="hidden" name="pegawai_logic" :value="logic">
                                                    <div style="flex-grow: 1;">
                                                        <select id="select-pegawai" name="pegawai_nips[]" multiple placeholder="Pilih Pegawai...">
                                                            @foreach($pegawais as $pgw)
                                                                <option value="{{ $pgw->nip }}" {{ in_array($pgw->nip, request('pegawai_nips', [])) ? 'selected' : '' }}>{{ $pgw->nama }} - NIP {{ $pgw->nip }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 text-end mt-4 pt-2 border-top border-secondary-subtle">
                                                <a href="{{ route('p2m.tes_urine.index') }}" class="btn btn-outline-secondary btn-sm me-2 px-3"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
                                                <button type="submit" class="btn btn-primary btn-sm px-4"><i class="bi bi-funnel-fill"></i> Terapkan</button>
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
                                            
                                            {{-- LOOPING HEADERS --}}
                                            @foreach([
                                                'satuan_kerja' => 'Satker',
                                                'anggaran_pelaksanaan' => 'Anggaran',
                                                'nama_instansi_pelaksana' => 'Nama Instansi',
                                                'sasaran_kegiatan' => 'Sasaran',
                                                'tanggal_pelaksanaan' => 'Tanggal',
                                                'tempat_kegiatan' => 'Tempat'
                                            ] as $field => $label)
                                                <th>
                                                    <a href="{{ request()->fullUrlWithQuery(['sort_by' => $field, 'sort_order' => request('sort_by') == $field && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" 
                                                       class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">
                                                        {{ $label }}
                                                        
                                                        {{-- FIX: Menggunakan request() function, BUKAN $request --}}
                                                        @if(request('sort_by') == $field)
                                                            <i class="bi bi-sort-{{ request('sort_order') == 'asc' ? 'down' : 'up' }}"></i>
                                                        @else
                                                            <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i>
                                                        @endif
                                                    </a>
                                                </th>
                                            @endforeach

                                            <th>Tim / Katim</th>
                                            
                                            {{-- Peserta --}}
                                            <th>
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'jumlah_peserta', 'sort_order' => request('sort_by') == 'jumlah_peserta' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">
                                                    Peserta
                                                    @if(request('sort_by') == 'jumlah_peserta')
                                                        <i class="bi bi-sort-numeric-{{ request('sort_order') == 'asc' ? 'down' : 'up' }}"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i>
                                                    @endif
                                                </a>
                                            </th>

                                            {{-- Positif --}}
                                            <th>
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'jumlah_positif', 'sort_order' => request('sort_by') == 'jumlah_positif' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-danger fw-bold d-flex justify-content-center align-items-center gap-1">
                                                    Positif (+)
                                                    @if(request('sort_by') == 'jumlah_positif')
                                                        <i class="bi bi-sort-numeric-{{ request('sort_order') == 'asc' ? 'down' : 'up' }}"></i>
                                                    @else
                                                        <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i>
                                                    @endif
                                                </a>
                                            </th>
                                            
                                            {{-- Kolom DIBUAT (Default Sort) --}}
                                            <th>
                                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => request('sort_by') == 'created_at' && request('sort_order') == 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex justify-content-center align-items-center gap-1">
                                                    Dibuat
                                                    {{-- Logic Default: Jika sort_by kosong ATAU sort_by = created_at --}}
                                                    @if(request('sort_by') == 'created_at' || !request('sort_by')) 
                                                        <i class="bi bi-sort-numeric-{{ request('sort_order', 'desc') == 'asc' ? 'down' : 'up-alt' }}"></i> 
                                                    @else
                                                        <i class="bi bi-arrow-down-up text-muted opacity-25 small"></i>
                                                    @endif
                                                </a>
                                            </th>

                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($tes_urines as $data)
                                            <tr class="text-center align-middle">
                                                <td>{{ $tes_urines->firstItem() + $loop->index }}</td>
                                                <td>{{ $data->satuanKerja->satuan_kerja ?? '-' }}</td>
                                                <td>{{ $data->anggaran_pelaksanaan }}</td>
                                                <td class="fw-semibold">{{ $data->nama_instansi_pelaksana }}</td>
                                                <td>{{ $data->sasaran_kegiatan }}</td>
                                                <td>{{ $data->tanggal_pelaksanaan->translatedFormat('d M Y') }}</td>
                                                <td>{{ $data->tempat_kegiatan }}</td>
                                                <td class="text-start">
                                                    @foreach($data->pegawai as $pegawai)
                                                        <span class="badge bg-info text-dark mb-1">{{ $pegawai->nama }}</span>
                                                    @endforeach
                                                </td>
                                                <td>{{ $data->jumlah_peserta }}</td>
                                                <td>
                                                    @if($data->jumlah_positif > 0)
                                                        <span class="badge bg-danger rounded-pill">{{ $data->jumlah_positif }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                
                                                {{-- Isi Kolom Dibuat --}}
                                                <td>
                                                    <div class="small text-muted">
                                                        {{ $data->created_at->translatedFormat('d M Y') }} <br>
                                                        {{ $data->created_at->format('H:i') }}
                                                    </div>
                                                </td>

                                                <td>
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <button type="button" class="btn btn-info btn-sm text-white" @click="expanded.includes({{ $data->id }}) ? expanded = expanded.filter(id => id !== {{ $data->id }}) : expanded.push({{ $data->id }})">
                                                            <i class="me-0 bi" :class="expanded.includes({{ $data->id }}) ? 'bi-eye-slash' : 'bi-eye'"></i>
                                                        </button>
                                                        <a href="{{ route('p2m.tes_urine.edit', $data->id) }}" class="btn btn-success btn-sm"><i class="me-0 bi bi-pencil-square"></i></a>
                                                        <form id="delete-form-{{ $data->id }}" action="{{ route('p2m.tes_urine.destroy', $data->id) }}" method="POST" class="d-inline">
                                                            @csrf @method('DELETE')
                                                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete({{ $data->id }})"><i class="me-0 bi bi-trash"></i></button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            
                                            <tr x-show="expanded.includes({{ $data->id }})" x-transition.duration.200ms class="bg-light">
                                                <td colspan="12" class="text-start p-4">
                                                    <div class="card border-0">
                                                        <div class="card-body">
                                                            <h5 class="card-title fw-bold text-primary mb-3">Informasi Tambahan</h5>

                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="fw-bold text-muted small text-uppercase">Keterangan Positif</label>
                                                                    <p class="{{ $data->jumlah_positif > 0 ? 'text-danger fw-bold' : 'text-dark' }} mt-1">
                                                                        {{ $data->keterangan_positif ?: '-' }}
                                                                    </p>
                                                                </div>

                                                                <div class="col-md-6 mb-3">
                                                                    <label class="fw-bold text-muted small text-uppercase">Link Dokumentasi</label>
                                                                    <div class="mt-1">
                                                                        @if($data->link_kelengkapan_dokumentasi)
                                                                            <a href="{{ $data->link_kelengkapan_dokumentasi }}" target="_blank" class="text-primary text-break">
                                                                                <i class="bi bi-link-45deg"></i> {{ $data->link_kelengkapan_dokumentasi }}
                                                                            </a>
                                                                        @else
                                                                            <span class="text-muted fst-italic">Tidak ada link</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                
                                                                <div class="col-md-12 mt-2">
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <label class="fw-bold text-muted small text-uppercase">Dibuat Pada</label>
                                                                            <div class="d-flex align-items-center mt-1 text-secondary">
                                                                                <i class="bi bi-clock me-2"></i>
                                                                                {{ $data->created_at->locale('id')->translatedFormat('l, d F Y H:i') }}
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <label class="fw-bold text-muted small text-uppercase">Terakhir Diperbarui</label>
                                                                            <div class="d-flex align-items-center mt-1 text-secondary">
                                                                                <i class="bi bi-pencil-square me-2"></i>
                                                                                {{ $data->updated_at->locale('id')->translatedFormat('l, d F Y H:i') }}
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="12" class="text-center p-4 text-muted">Tidak ada data kegiatan Tes Urine</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted small">Tampilkan</span>
                                    <select class="form-select form-select-sm border-secondary-subtle" style="width: auto;" onchange="window.location.href = this.value">
                                        @foreach([10, 25, 50, 100] as $num)
                                            <option value="{{ request()->fullUrlWithQuery(['per_page' => $num, 'page' => 1]) }}" {{ request('per_page') == $num ? 'selected' : '' }}>{{ $num }}</option>
                                        @endforeach
                                    </select>
                                    <span class="text-muted small">data</span>
                                </div>
                                <div>{{ $tes_urines->fragment('data-table')->links() }}</div>
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
        const config = { plugins: ['remove_button', 'clear_button'], persist: false, create: false };
        if(document.getElementById('select-satker')) new TomSelect('#select-satker', config);
        if(document.getElementById('select-bulan')) new TomSelect('#select-bulan', config);
        if(document.getElementById('select-anggaran')) new TomSelect('#select-anggaran', config);
        if(document.getElementById('select-sasaran')) new TomSelect('#select-sasaran', config);
        if(document.getElementById('select-tahun')) new TomSelect('#select-tahun', config);
        if(document.getElementById('select-pegawai')) new TomSelect('#select-pegawai', config);
    });

    window.confirmDelete = function(id) {
        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Data yang dihapus tidak dapat dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
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