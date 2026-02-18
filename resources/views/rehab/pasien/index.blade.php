@extends('admin')

@section('content')
    <main class="admin-main" x-data="{
        showFilter: true,
        expanded: [],
        toggleExpand(id) {
            if (this.expanded.includes(id)) {
                this.expanded = this.expanded.filter(i => i !== id);
            } else {
                this.expanded.push(id);
            }
        },
        isExpanded(id) {
            return this.expanded.includes(id);
        }
    }">
        <div class="container-fluid p-4 p-lg-5">

            {{-- ==================================================================== --}}
            {{-- HEADER: JUDUL (KIRI) & TOMBOL TAMBAH (KANAN) --}}
            {{-- ==================================================================== --}}
            <div class="d-flex justify-content-between align-items-center mb-4">

                {{-- BAGIAN KIRI: Judul & Deskripsi --}}
                <div>
                    <h1 class="h3 mb-1 fw-bold text-dark">Data Pasien Rehabilitasi</h1>
                    <p class="text-muted mb-0">Master Data Pasien Rehabilitasi Narkotika</p>
                </div>

                {{-- BAGIAN KANAN: Tombol Tambah Data --}}
                @if (auth()->user()->hasRole(['operator_satker', 'operator_rehab']))
                    <a href="{{ route('rehab.pasien.create') }}"
                        class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                        <i class="bi bi-plus-lg"></i>
                        <span>Tambah Data</span>
                    </a>
                @endif
            </div>

            {{-- ALERT NOTIFIKASI --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div><strong>Berhasil!</strong> {{ session('message') ?? 'Data telah diproses.' }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div><strong>Gagal!</strong> {{ session('message') ?? session('error') }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- LOGIKA PHP FILTER & SORTING --}}
            @php
                $allFilters = request()->only([
                    'search',
                    'pekerjaan',
                    'jenis_kelamin',
                    'pendidikan',
                    'sumber_pasien',
                    'satuan_kerja_id',
                    'narkotika_id',
                    'tahun',
                ]);
                $activeFilters = collect($allFilters)
                    ->filter(function ($value) {
                        return !empty($value);
                    })
                    ->count();

                // Helper Sorting Link
                $sortLink = function ($col, $label) {
                    $currentCol = request('sort_by', 'created_at');
                    $currentOrder = request('sort_order', 'desc');
                    $newOrder = $currentCol === $col && $currentOrder === 'desc' ? 'asc' : 'desc';
                    $icon = 'bi-arrow-down-up text-muted opacity-25';
                    if ($currentCol === $col) {
                        $icon = $currentOrder === 'desc' ? 'bi-sort-down text-primary' : 'bi-sort-up text-primary';
                    }
                    $url = request()->fullUrlWithQuery(['sort_by' => $col, 'sort_order' => $newOrder]);
                    return '<a href="' .
                        $url .
                        '" class="text-decoration-none text-secondary fw-bold d-flex align-items-center justify-content-center gap-2">' .
                        $label .
                        ' <i class="bi ' .
                        $icon .
                        '"></i></a>';
                };
            @endphp

            <div class="row justify-content-center mb-5">
                <div class="col-12">

                    {{-- CARD UTAMA --}}
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 border-bottom">
                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
                                <h5 class="card-title mb-0 fw-bold text-secondary"><i class="bi bi-table me-2"></i>Data
                                    Pasien</h5>

                                {{-- Tombol Filter --}}
                                <button type="button" @click="showFilter = !showFilter"
                                    class="btn btn-sm transition-all d-flex align-items-center gap-2"
                                    :class="showFilter ? 'btn-light text-secondary border' : 'btn-primary shadow-sm'">
                                    <i class="bi" :class="showFilter ? 'bi-chevron-up' : 'bi-funnel'"></i>
                                    <span x-text="showFilter ? 'Sembunyikan Filter' : 'Filter Pencarian'"></span>
                                    @if ($activeFilters > 0)
                                        <span class="badge bg-danger rounded-pill">{{ $activeFilters }}</span>
                                    @endif
                                </button>
                            </div>
                        </div>

                        <div class="card-body p-0 p-lg-4">

                            {{-- FORM FILTER --}}
                            <form action="{{ route('rehab.pasien.index') }}" method="GET">
                                <button type="submit" style="display: none;" aria-hidden="true"></button>
                                <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                                <input type="hidden" name="sort_order" value="{{ request('sort_order') }}">

                                {{-- PANEL FILTER --}}
                                <div x-show="showFilter" x-transition.opacity class="mb-4 px-3 px-lg-0 pt-3 pt-lg-0"
                                    style="position: relative; z-index: 1040;">
                                    <div class="bg-body-tertiary p-4 rounded-3 border">
                                        <div class="row g-3 text-start">
                                            {{-- SEARCH --}}
                                            {{-- SATUAN KERJA (HANYA UNTUK ADMIN) --}}
                                            @if ($user->isAdmin())
                                                <div class="col-12 col-lg-12">
                                                    <label
                                                        class="form-label fw-bold small text-secondary text-uppercase mb-1">Satuan
                                                        Kerja</label>
                                                    <div class="shadow-sm bg-white rounded">
                                                        <select id="select-satker" name="satuan_kerja_id[]" multiple
                                                            placeholder="Pilih Satuan Kerja..." autocomplete="off">
                                                            @foreach ($satuanKerjas as $satker)
                                                                <option value="{{ $satker->id }}"
                                                                    {{ in_array($satker->id, request('satuan_kerja_id', [])) ? 'selected' : '' }}>
                                                                    {{ $satker->satuan_kerja }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="col-6 col-lg-4">
                                                <label
                                                    class="form-label fw-bold small text-secondary text-uppercase mb-1">Pekerjaan</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-pekerjaan" name="pekerjaan[]" multiple
                                                        placeholder="Pilih..." autocomplete="off">
                                                        @foreach (\App\Models\RehabPasien::Pekerjaan as $p)
                                                            <option value="{{ $p }}"
                                                                {{ in_array($p, request('pekerjaan', [])) ? 'selected' : '' }}>
                                                                {{ $p }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- JENIS KELAMIN --}}
                                            <div class="col-6 col-lg-4">
                                                <label
                                                    class="form-label fw-bold small text-secondary text-uppercase mb-1">Jenis
                                                    Kelamin</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-jk" name="jenis_kelamin[]" multiple
                                                        placeholder="Pilih..." autocomplete="off">
                                                        <option value="Laki-laki"
                                                            {{ in_array('Laki-laki', request('jenis_kelamin', [])) ? 'selected' : '' }}>
                                                            Laki-laki</option>
                                                        <option value="Perempuan"
                                                            {{ in_array('Perempuan', request('jenis_kelamin', [])) ? 'selected' : '' }}>
                                                            Perempuan</option>
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- PENDIDIKAN --}}
                                            <div class="col-6 col-lg-4">
                                                <label
                                                    class="form-label fw-bold small text-secondary text-uppercase mb-1">Pendidikan</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-pendidikan" name="pendidikan[]" multiple
                                                        placeholder="Pilih..." autocomplete="off">
                                                        @foreach (\App\Models\RehabPasien::Pendidikan as $p)
                                                            <option value="{{ $p }}"
                                                                {{ in_array($p, request('pendidikan', [])) ? 'selected' : '' }}>
                                                                {{ $p }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- SUMBER PASIEN --}}
                                            <div class="col-6 col-lg-4">
                                                <label
                                                    class="form-label fw-bold small text-secondary text-uppercase mb-1">Sumber
                                                    Pasien</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-sumber" name="sumber_pasien[]" multiple
                                                        placeholder="Pilih..." autocomplete="off">
                                                        @foreach (\App\Models\RehabPasien::Sumber_pasien as $p)
                                                            <option value="{{ $p }}"
                                                                {{ in_array($p, request('sumber_pasien', [])) ? 'selected' : '' }}>
                                                                {{ $p }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- JENIS NARKOTIKA --}}
                                            <div class="col-6 col-lg-4">
                                                <label
                                                    class="form-label fw-bold small text-secondary text-uppercase mb-1">Jenis
                                                    Narkotika</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-narkotika" name="narkotika_id[]" multiple
                                                        placeholder="Pilih..." autocomplete="off">
                                                        @foreach ($narkotikas as $narkotika)
                                                            <option value="{{ $narkotika->id }}"
                                                                {{ in_array($narkotika->id, request('narkotika_id', [])) ? 'selected' : '' }}>
                                                                {{ $narkotika->nama_narkotika }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- TAHUN --}}
                                            <div class="col-12 col-lg-4">
                                                <label
                                                    class="form-label fw-bold small text-secondary text-uppercase mb-1">Tahun</label>
                                                <div class="shadow-sm bg-white rounded">
                                                    <select id="select-tahun" name="tahun[]" multiple
                                                        placeholder="Pilih..." autocomplete="off">
                                                        @php
                                                            $selectedTahun = request()->filled('tahun')
                                                                ? (array) request('tahun')
                                                                : [$tahunSekarang];
                                                        @endphp

                                                        @foreach ($tahuns as $tahun)
                                                            <option value="{{ $tahun }}"
                                                                {{ in_array($tahun, $selectedTahun) ? 'selected' : '' }}>
                                                                {{ $tahun }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- BUTTON RESET & TERAPKAN --}}
                                            <div class="col-12 text-end pt-3 border-top mt-4 text-start">
                                                <a href="{{ route('rehab.pasien.index') }}"
                                                    class="btn btn-link text-decoration-none text-muted btn-sm me-2">Reset</a>
                                                <button type="submit" class="btn btn-primary px-4 shadow-sm"><i
                                                        class="bi bi-funnel-fill me-1"></i> Terapkan</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- SUMMARY & EXPORT --}}
                                <div
                                    class="d-flex flex-column flex-lg-row justify-content-between align-items-end align-items-lg-center mb-3 px-3 px-lg-0">
                                    <div class="mb-2 mb-lg-0">
                                        <button type="submit" formaction="{{ route('rehab.pasien.export') }}"
                                            class="btn btn-success btn-sm text-white d-flex align-items-center gap-2 px-3 shadow-none">
                                            <i class="bi bi-file-earmark-excel"></i> <span>Export Excel</span>
                                        </button>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <div
                                            class="d-flex align-items-center border border-secondary-subtle rounded-3 px-3 py-1 bg-light">
                                            <i class="bi bi-info-circle text-muted me-2" style="font-size: 0.85rem;"></i>
                                            <span class="text-muted" style="font-size: 0.9rem;">Total pasien : </span>
                                            <span class="text-dark ms-1"
                                                style="font-size: 0.9rem;">{{ number_format($pasien->total(), 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            {{-- TABEL DATA --}}
                            <div class="custom-table-scroll mb-3" id="data-table">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light sticky-top">
                                        <tr
                                            class="text-center align-middle small text-uppercase text-secondary text-nowrap">
                                            <th class="py-2 bg-light">No</th>
                                            <th class="py-3 bg-light text-start">No. Rekam Medis</th>
                                            <th class="py-3 bg-light text-start">{!! $sortLink('nama_pasien', 'Nama Pasien') !!}</th>
                                            <th class="py-3 bg-light">{!! $sortLink('satuan_kerja_id', 'Satuan Kerja') !!}</th>
                                            <th class="py-3 bg-light">{!! $sortLink('jenis_kelamin', 'Jenis Kelamin') !!}</th>
                                            <th class="py-3 bg-light">Jenis Narkotika</th>
                                            <th class="py-3 bg-light">{!! $sortLink('sumber_pasien', 'Sumber Pasien') !!}</th>
                                            <th class="py-3 bg-light">{!! $sortLink('created_at', 'Dibuat') !!}</th>
                                            <th class="py-3 bg-light pe-3">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="border-top-0">
                                        @forelse ($pasien as $data)
                                            <tr class="text-center align-middle">
                                                <td class="fw-bold text-secondary ps-3">
                                                    {{ $pasien->firstItem() + $loop->index }}</td>
                                                <td class="text-start"><span
                                                        class="fw-semibold text-dark">{{ $data->rekam_medis }}</span></td>
                                                <td class="text-center"><span
                                                        class="fw-semibold text-dark">{{ $data->nama_pasien }}</span></td>
                                                <td class="text-start"><span
                                                        class="fw-semibold text-dark">{{ $data->satuanKerja->satuan_kerja }}</span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge rounded-pill {{ $data->jenis_kelamin == 'Laki-laki' ? 'bg-primary' : 'bg-danger' }} bg-opacity-10 {{ $data->jenis_kelamin == 'Laki-laki' ? 'text-primary' : 'text-danger' }} border {{ $data->jenis_kelamin == 'Laki-laki' ? 'border-primary' : 'border-danger' }} border-opacity-25">
                                                        {{ $data->jenis_kelamin }}
                                                    </span>
                                                </td>
                                                <td class="text-start">
                                                    @if ($data->narkotikas->count())
                                                        @foreach ($data->narkotikas as $narkotika)
                                                            <span class="d-block mb-1 ms-3">
                                                                {{-- <i class="bi bi-capsule text-danger me-2"></i> --}}
                                                                <span
                                                                    class="text-dark me-1">
                                                                    {{ $narkotika->nama_narkotika }}
                                                                </span>
                                                            </span>
                                                        @endforeach
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge rounded-pill {{ $data->sumber_pasien == 'Compulsory' ? 'bg-warning' : 'bg-secondary' }} bg-opacity-10 {{ $data->sumber_pasien == 'Compulsory' ? 'text-warning' : 'text-secondary' }} border {{ $data->sumber_pasien == 'Compulsory' ? 'border-warning' : 'border-secondary' }} border-opacity-25">
                                                        {{ $data->sumber_pasien }}
                                                    </span>
                                                </td>
                                                <td class="small text-muted text-nowrap text-center">
                                                    {{ $data->created_at->locale('id')->translatedFormat('d M Y') }}</td>
                                                <td class="pe-3">
                                                    <div class="btn-group btn-group-sm shadow-sm">
                                                        <button type="button" class="btn btn-light border text-secondary"
                                                            @click="toggleExpand({{ $data->id }})">
                                                            <i class="bi"
                                                                :class="isExpanded({{ $data->id }}) ?
                                                                    'bi-chevron-up text-primary' :
                                                                    'bi-eye text-secondary'"></i>
                                                        </button>
                                                        @if (auth()->user()->hasRole(['operator_satker', 'operator_rehab']))
                                                            <a href="{{ route('rehab.pasien.edit', $data->id) }}"
                                                                class="btn btn-light border text-primary"
                                                                title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                            <button type="button"
                                                                class="btn btn-light border text-danger"
                                                                onclick="confirmDelete({{ $data->id }})"
                                                                title="Hapus"><i class="bi bi-trash"></i></button>
                                                            <form id="delete-form-{{ $data->id }}"
                                                                action="{{ route('rehab.pasien.destroy', $data->id) }}"
                                                                method="POST" class="d-none">@csrf @method('DELETE')
                                                            </form>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>

                                            {{-- TR DETAIL --}}
                                            <tr x-show="isExpanded({{ $data->id }})" x-transition>
                                                <td colspan="11" class="p-0 border-0">
                                                    <div
                                                        class="bg-body-tertiary p-4 border-bottom shadow-inner text-start">
                                                        <div class="card border-0 shadow-sm">
                                                            <div class="card-body">
                                                                <h6
                                                                    class="card-title fw-bold text-primary border-bottom pb-2 mb-3">
                                                                    <i class="bi bi-info-circle me-2"></i>Detail Lengkap
                                                                    Pasien
                                                                </h6>

                                                                <div class="row g-2 text-start">
                                                                    <div class="col-lg-4">
                                                                        <dl class="row mb-0 small text-start">
                                                                            <dt class="col-sm-4 text-secondary mb-2">No.
                                                                                Rekam Medis</dt>
                                                                            <dd class="col-sm-8 text-dark">
                                                                                {{ $data->rekam_medis }}</dd>
                                                                            <dt class="col-sm-4 text-secondary mb-2">Satuan
                                                                                Kerja</dt>
                                                                            <dd class="col-sm-8 text-dark">
                                                                                {{ $data->satuanKerja->satuan_kerja ?? '-' }}
                                                                            </dd>
                                                                            <dt class="col-sm-4 text-secondary mb-2">Nama
                                                                                Pasien</dt>
                                                                            <dd class="col-sm-8 text-dark">
                                                                                {{ $data->nama_pasien }}</dd>
                                                                            <dt class="col-sm-4 text-secondary mb-2">Usia
                                                                            </dt>
                                                                            <dd class="col-sm-8 text-dark">
                                                                                {{ $data->usia }} tahun</dd>
                                                                            <dt class="col-sm-4 text-secondary mb-2">Jenis
                                                                                Kelamin</dt>
                                                                            <dd class="col-sm-8">
                                                                                <span
                                                                                    class="badge rounded-pill {{ $data->jenis_kelamin == 'Laki-laki' ? 'bg-primary' : 'bg-danger' }} bg-opacity-10 {{ $data->jenis_kelamin == 'Laki-laki' ? 'text-primary' : 'text-danger' }} border {{ $data->jenis_kelamin == 'Laki-laki' ? 'border-primary' : 'border-danger' }} border-opacity-25">{{ $data->jenis_kelamin }}</span>
                                                                            </dd>
                                                                        </dl>
                                                                    </div>
                                                                    <div class="col-lg-4">
                                                                        <dl class="row mb-0 small text-start">
                                                                            <dt class="col-sm-4 text-secondary mb-2">
                                                                                Pekerjaan</dt>
                                                                            <dd class="col-sm-8 text-dark">
                                                                                {{ $data->pekerjaan }}</dd>
                                                                            <dt class="col-sm-4 text-secondary mb-2">
                                                                                Pendidikan</dt>
                                                                            <dd class="col-sm-8 text-dark">
                                                                                {{ $data->pendidikan }}</dd>
                                                                            <dt class="col-sm-4 text-secondary mb-2">Sumber
                                                                                Pasien</dt>
                                                                            <dd class="col-sm-8"><span
                                                                                    class="badge rounded-pill {{ $data->sumber_pasien == 'Compulsory' ? 'bg-warning' : 'bg-secondary' }} bg-opacity-10 {{ $data->sumber_pasien == 'Compulsory' ? 'text-warning' : 'text-secondary' }} border {{ $data->sumber_pasien == 'Compulsory' ? 'border-warning' : 'border-secondary' }} border-opacity-25">{{ $data->sumber_pasien }}</span>
                                                                            </dd>
                                                                            <dt class="col-sm-4 text-secondary mb-2">Jenis
                                                                                Narkotika</dt>
                                                                            <dd class="col-sm-8">
                                                                                @if ($data->narkotikas->count())
                                                                                    @foreach ($data->narkotikas as $narkotika)
                                                                                        <div class="mb-1">
                                                                                            <i
                                                                                                class="bi bi-capsule me-2 text-muted"></i>
                                                                                            {{ $narkotika->nama_narkotika }}
                                                                                        </div>
                                                                                    @endforeach
                                                                                @else
                                                                                    <span class="text-muted">-</span>
                                                                                @endif
                                                                            </dd>
                                                                        </dl>
                                                                    </div>
                                                                    <div class="col-lg-4">
                                                                        <dl class="row mb-0 small text-start">
                                                                            <dt class="col-sm-4 text-secondary mb-2">Dibuat
                                                                                Pada</dt>
                                                                            <dd class="col-sm-8 text-dark">
                                                                                {{ $data->created_at->locale('id')->translatedFormat('l, d F Y H:i') }}
                                                                            </dd>
                                                                            <dt class="col-sm-4 text-secondary mb-2">
                                                                                Terakhir Diubah</dt>
                                                                            <dd class="col-sm-8 text-dark">
                                                                                {{ $data->updated_at->locale('id')->translatedFormat('l, d F Y H:i') }}
                                                                            </dd>
                                                                        </dl>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="11" class="text-center py-5 text-muted">
                                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                                    <p class="mt-2 mb-0">Tidak ada data pasien</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- PAGINATION --}}
                            @if ($pasien->hasPages())
                                <nav class="d-flex justify-content-center justify-content-lg-end mt-4">
                                    {{ $pasien->appends(request()->query())->links('pagination::bootstrap-5') }}
                                </nav>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('styles')
    <style>
        /* CSS FIX Z-INDEX DROPDOWN */
        .ts-dropdown,
        .ts-dropdown.single {
            z-index: 9999 !important;
        }

        .ts-control {
            border: none !important;
            box-shadow: none !important;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            background-color: transparent !important;
            min-height: 40px;
        }

        /* Prevent layout shift when filter is hidden */
        html {
            overflow-y: scroll;
        }

        .custom-table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .custom-table-scroll table {
            min-width: 1000px;
        }

        .transition-all {
            transition: all 0.3s ease;
        }

        .page-link {
            border: none;
            color: #6c757d;
            border-radius: 50% !important;
            margin: 0 2px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-item.active .page-link {
            background-color: #0d6efd;
            color: white;
            box-shadow: 0 2px 4px rgba(13, 110, 253, 0.3);
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

@push('scripts')
    <script type="module">
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof TomSelect !== 'undefined') {
                const configTomSelect = {
                    plugins: ['remove_button', 'clear_button'],
                    persist: false,
                    create: false,
                    maxOptions: null
                };
                const ids = ['select-pekerjaan', 'select-satker', 'select-jk', 'select-pendidikan', 'select-sumber',
                    'select-narkotika',
                    'select-tahun'
                ];
                ids.forEach(id => {
                    if (document.getElementById(id)) new TomSelect('#' + id, configTomSelect);
                });
            }
        });

        window.confirmDelete = function(id) {
            Swal.fire({
                title: 'Hapus Data?',
                text: "Data ini akan dihapus permanen.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }
    </script>
@endpush
