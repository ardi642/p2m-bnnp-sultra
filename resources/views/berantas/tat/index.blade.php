@extends('admin')
@section('content')
<main class="admin-main">
    <div class="container-fluid p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Data TAT</h4>
                <p class="text-secondary small mb-0">Kelola Data Tim Asesmen Terpadu</p>
            </div>
            <a href="{{ route('berantas.tat.create') }}" class="btn btn-primary px-4 shadow-sm fw-bold">
                <i class="bi bi-plus-lg me-1"></i> Tambah Data
            </a>
        </div>

        {{-- Alert --}}
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm mb-4 alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    {{-- Alpine JS untuk Expandable Row --}}
                    <table class="table table-hover align-middle mb-0" x-data="{ expanded: [] }">
                        <thead class="bg-light">
                            <tr class="text-secondary small text-uppercase">
                                <th class="ps-4 py-3" width="5%">No</th>
                                <th width="25%">Register & Satker</th>
                                <th width="30%">Tersangka</th>
                                <th width="30%">Barang Bukti</th>
                                <th class="text-center" width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $row)
                            <tr :class="expanded.includes({{ $row->id }}) ? 'bg-light' : ''">
                                <td class="ps-4 text-secondary text-center">{{ $data->firstItem() + $loop->index }}</td>
                                <td>
                                    <div class="fw-bold text-dark mb-1">{{ $row->no_register }}</div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-light text-secondary border fw-normal">
                                            <i class="bi bi-calendar-event me-1"></i> {{ $row->tanggal_pelaksanaan->translatedFormat('d M Y') }}
                                        </span>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="bi bi-building me-1"></i> {{ $row->satuanKerja->satuan_kerja ?? '-' }}
                                    </div>
                                </td>
                                <td class="align-top py-3">
                                    @if($row->tersangka->count() > 0)
                                        <div class="d-flex flex-column gap-1">
                                            @foreach($row->tersangka as $t)
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-person-fill text-secondary me-2"></i>
                                                    <div>
                                                        <span class="small fw-semibold text-dark">{{ $t->nama_tersangka }}</span>
                                                        <span class="text-muted ms-1 small" style="font-size: 0.75rem;">({{ $t->jenis_kelamin }})</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted small fst-italic">- Tidak ada data -</span>
                                    @endif
                                </td>
                                <td class="align-top py-3">
                                    @if($row->barangBukti->count() > 0)
                                        <div class="d-flex flex-column gap-1">
                                            @foreach($row->barangBukti as $bb)
                                                <div class="small d-flex align-items-center">
                                                    @if($bb->kategori === 'Narkotika') 
                                                        <i class="bi bi-capsule text-danger me-2" title="Narkotika"></i> 
                                                    @else 
                                                        <i class="bi bi-box-seam text-success me-2" title="Non-Narkotika"></i> 
                                                    @endif
                                                    <span class="text-dark me-1">{{ $bb->nama_barang }}</span>
                                                    <span class="text-muted">({{ (float)$bb->kuantitas }} {{ $bb->satuan }})</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-muted small fst-italic">- Tidak ada BB -</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        {{-- TOMBOL EXPAND --}}
                                        <button type="button" class="btn btn-sm" 
                                                :class="expanded.includes({{ $row->id }}) ? 'btn-light text-primary border' : 'btn-light border'"
                                                @click="expanded.includes({{ $row->id }}) ? expanded = expanded.filter(id => id !== {{ $row->id }}) : expanded.push({{ $row->id }})"
                                                title="Lihat Detail">
                                            <i class="bi" :class="expanded.includes({{ $row->id }}) ? 'bi-chevron-up' : 'bi-eye'"></i>
                                        </button>

                                        {{-- DROPDOWN AKSI LAIN --}}
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm border" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('berantas.tat.edit', $row->id) }}">
                                                        <i class="bi bi-pencil me-2 text-warning"></i> Edit Data
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form action="{{ route('berantas.tat.destroy', $row->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="bi bi-trash me-2"></i> Hapus Data
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            {{-- BARIS DETAIL EXPANDABLE --}}
                            <tr x-show="expanded.includes({{ $row->id }})" x-transition>
                                <td colspan="5" class="p-0 border-0">
                                    <div class="bg-light p-4 border-bottom shadow-inner">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body">
                                                <h6 class="fw-bold text-primary mb-3"><i class="bi bi-info-circle-fill me-2"></i>Detail Kasus & Asesmen</h6>
                                                <div class="row g-3">
                                                    <div class="col-md-12">
                                                        <label class="small text-secondary fw-bold text-uppercase">Pasal Disangkakan</label>
                                                        <div class="p-2 bg-light rounded border text-dark small">{{ $row->pasal_disangkakan ?? '-' }}</div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="small text-secondary fw-bold text-uppercase">Instansi Pengirim</label>
                                                        <div class="text-dark fw-bold small">{{ $row->instansi_pengirim ?? '-' }}</div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="small text-secondary fw-bold text-uppercase">Tgl Penangkapan</label>
                                                        <div class="text-dark small">{{ $row->tanggal_penangkapan ? $row->tanggal_penangkapan->translatedFormat('d F Y') : '-' }}</div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="small text-secondary fw-bold text-uppercase">Tgl Permohonan</label>
                                                        <div class="text-dark small">{{ $row->tanggal_permohonan ? $row->tanggal_permohonan->translatedFormat('d F Y') : '-' }}</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="small text-secondary fw-bold text-uppercase">Tim Hukum</label>
                                                        <div class="p-2 bg-light rounded border text-dark small">{{ $row->tim_hukum ?? '-' }}</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="small text-secondary fw-bold text-uppercase">Tim Medis</label>
                                                        <div class="p-2 bg-light rounded border text-dark small">{{ $row->tim_medis ?? '-' }}</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="small text-secondary fw-bold text-uppercase">Lembaga Rehab</label>
                                                        <div class="text-dark small">{{ $row->lembaga_rehab ?? '-' }}</div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="small text-secondary fw-bold text-uppercase">Rekomendasi</label>
                                                        <div>
                                                            @if($row->tindak_lanjut_rekomendasi == 'dilaksanakan')
                                                                <span class="badge bg-success">Dilaksanakan</span>
                                                            @else
                                                                <span class="badge bg-secondary">Tidak Dilaksanakan</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="small text-secondary fw-bold text-uppercase">Proses Hukum Lanjut</label>
                                                        <div class="p-2 bg-light rounded border text-dark small">{{ $row->proses_hukum_lanjut ?? '-' }}</div>
                                                    </div>
                                                    
                                                    {{-- LAMPIRAN FILE --}}
                                                    @if($row->dokumentasi->count() > 0)
                                                    <div class="col-12 mt-3">
                                                        <label class="small text-secondary fw-bold text-uppercase mb-2">Lampiran Dokumentasi</label>
                                                        <div class="d-flex gap-2 flex-wrap">
                                                            @foreach($row->dokumentasi as $doc)
                                                                <a href="{{ Storage::url($doc->path_file) }}" target="_blank" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2">
                                                                    @if(Str::contains($doc->tipe_file, 'image')) <i class="bi bi-file-earmark-image"></i>
                                                                    @elseif(Str::contains($doc->tipe_file, 'pdf')) <i class="bi bi-file-earmark-pdf"></i>
                                                                    @else <i class="bi bi-file-earmark-text"></i> @endif
                                                                    <span class="d-inline-block text-truncate" style="max-width: 150px;">{{ $doc->nama_file_asli }}</span>
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox display-4 d-block mb-3 opacity-25"></i>
                                        Belum ada data TAT yang diinput.
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($data->hasPages())
                    <div class="p-3 border-top">
                        {{ $data->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</main>
@endsection