@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4 p-lg-5">
        
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm mb-4 py-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill text-success fs-3 me-3"></i>
                    <div>
                        <div class="fw-bold fs-5 text-success">{{ session('success') }}</div>
                        <div class="text-muted small mt-1">Sistem berhasil memproses data rekam medis pasien.</div>
                    </div>
                </div>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-lg mb-4" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white;">
                    <div class="card-body p-5 position-relative overflow-hidden">
                        <i class="bi bi-person-vcard position-absolute opacity-10" style="font-size: 15rem; right: -2rem; top: -3rem;"></i>
                        
                        <div class="d-flex justify-content-between align-items-start position-relative" style="z-index: 2;">
                            <div>
                                <div class="mb-2 text-uppercase fw-bold text-info" style="letter-spacing: 2px; font-size: 0.8rem;">
                                    Kartu Identitas Pasien Rehabilitasi
                                </div>
                                <h1 class="display-4 fw-bolder mb-0 font-monospace text-warning">
                                    {{ $pasien->id_pasien }}
                                </h1>
                            </div>
                            @if (auth()->user()->hasRole(['operator_satker', 'operator_rehab', 'admin']))
                                <a href="{{ route('rehab.pasien.edit', ['pasien' => $pasien->id, 'ref' => 'show']) }}" class="btn btn-outline-light btn-sm">
                                    <i class="bi bi-pencil-square me-1"></i> Edit Identitas
                                </a>
                            @endif
                        </div>
                        
                        <hr class="border-light opacity-25 my-4">
                        
                        <div class="row g-4 position-relative" style="z-index: 2;">
                            <div class="col-md-4">
                                <div class="small text-white-50 text-uppercase fw-bold mb-1">Nama Pasien / Inisial</div>
                                <div class="fs-5 fw-bold">{{ $pasien->nama_pasien }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-white-50 text-uppercase fw-bold mb-1">Jenis Kelamin & Usia Saat Ini</div>
                                <div class="fs-5">
                                    <i class="bi bi-gender-ambiguous me-2 text-info"></i>{{ $pasien->jenis_kelamin }} ({{ $pasien->usia }} Thn)
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-white-50 text-uppercase fw-bold mb-1">Satuan Kerja</div>
                                <div class="fs-5">{{ $pasien->satuanKerja->satuan_kerja ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm" x-data="{ expanded: [] }">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="card-title fw-bold text-dark m-0">
                            <i class="bi bi-clock-history me-2"></i>Histori Perjalanan Rehabilitasi
                        </h5>
                        <div>
                            @if (auth()->user()->hasRole(['operator_satker', 'operator_rehab', 'admin']))
                                <a href="{{ route('rehab.pasien.riwayat.create', $pasien->id) }}" class="btn btn-primary btn-sm px-3 me-2">
                                    <i class="bi bi-plus-circle me-1"></i> Tambah Riwayat Baru
                                </a>
                            @endif
                            <a href="{{ route('rehab.pasien.index') }}" class="btn btn-outline-secondary btn-sm px-4">Kembali</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center text-nowrap">
                                <thead class="bg-light">
                                    <tr class="small text-uppercase text-secondary">
                                        <th class="py-3">No</th>
                                        <th class="py-3 text-start">Tgl Masuk Rehab</th>
                                        <th class="py-3">Usia Saat Masuk</th>
                                        <th class="py-3 text-start">Pekerjaan</th>
                                        <th class="py-3 text-start">Pendidikan</th>
                                        <th class="py-3">Sumber Pasien</th>
                                        <th class="py-3 text-start">Narkotika</th>
                                        <th class="py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pasien->riwayat as $idx => $r)
                                    <tr :class="expanded.includes({{ $r->id }}) ? 'bg-light' : ''">
                                        <td class="fw-bold text-muted">{{ $idx + 1 }}</td>
                                        <td class="fw-bold text-primary text-start">{{ $r->tanggal_rehab->format('d F Y') }}</td>
                                        <td>{{ $r->usia_saat_rehab }} Tahun</td>
                                        <td class="text-start">{{ $r->pekerjaan }}</td>
                                        <td class="text-start">{{ $r->pendidikan }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ $r->sumber_pasien }}</span>
                                        </td>
                                        <td class="text-start">
                                            <div class="text-wrap" style="max-width: 150px;">
                                                @foreach($r->narkotika as $n) 
                                                    <span class="badge bg-secondary mb-1">{{ $n->nama_narkotika }}</span> 
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-light border" @click="expanded.includes({{ $r->id }}) ? expanded = expanded.filter(id => id !== {{ $r->id }}) : expanded.push({{ $r->id }})" title="Lihat Dokumen">
                                                    <i class="bi" :class="expanded.includes({{ $r->id }}) ? 'bi-chevron-up text-primary' : 'bi-folder2-open text-primary'"></i>
                                                </button>
                                                @if (auth()->user()->hasRole(['operator_satker', 'operator_rehab', 'admin']))
                                                    <a href="{{ route('rehab.pasien.riwayat.edit', ['id' => $r->id, 'ref' => 'show']) }}" class="btn btn-light border text-warning" title="Edit Riwayat Kedatangan">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- ROW DOKUMEN --}}
                                    <tr x-show="expanded.includes({{ $r->id }})" x-cloak x-data="fileDownloader">
                                        <td colspan="8" class="p-0 border-0">
                                            <div class="bg-body-tertiary p-4 border-bottom shadow-inner text-start">
                                                <div class="card border-0 shadow-sm">
                                                    <div class="card-body">
                                                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                                                            <i class="bi bi-paperclip me-2"></i>Dokumentasi & Lampiran
                                                        </h6>
                                                        <form class="row" action="{{ route('dokumen.zip.selected') }}" method="POST" x-ref="formZip">
                                                            @csrf
                                                            <div class="col-12 text-start">
                                                                <div class="row g-4">
                                                                    @php
                                                                        $fotos = $r->dokumen->where('kategori', 'dokumentasi');
                                                                        $lampirans = $r->dokumen->where('kategori', 'lampiran');
                                                                        $fotoIds = $fotos->where('is_link', false)->pluck('id')->values()->toArray();
                                                                        $lampiranIds = $lampirans->where('is_link', false)->pluck('id')->values()->toArray();
                                                                    @endphp
                                                                    
                                                                    <div class="col-lg-6">
                                                                        <div class="card h-100 border bg-light shadow-none">
                                                                            <div class="card-header bg-transparent border-bottom fw-bold text-primary d-flex justify-content-between align-items-center">
                                                                                <div class="form-check">
                                                                                    @if(count($fotoIds) > 0)
                                                                                        <input class="form-check-input cursor-pointer" type="checkbox" @change="toggleAll({{ json_encode($fotoIds) }})" :checked="isAllSelected({{ json_encode($fotoIds) }})">
                                                                                    @endif
                                                                                    <label class="form-check-label cursor-pointer select-none"><i class="bi bi-images me-2"></i>Dokumentasi</label>
                                                                                </div>
                                                                                <span class="badge bg-primary rounded-pill">{{ $fotos->count() }}</span>
                                                                            </div>
                                                                            <div class="card-body p-2" style="max-height: 250px; overflow-y: auto;">
                                                                                @forelse($fotos as $doc)
                                                                                    <div class="d-flex align-items-center bg-white border rounded p-2 mb-2 shadow-sm" :class="isSelected({{ $doc->id }}) ? 'border-primary bg-primary bg-opacity-10' : ''">
                                                                                        @if(!$doc->is_link)
                                                                                            <div class="form-check me-2 d-flex align-items-center">
                                                                                                <input class="form-check-input shadow-none cursor-pointer" type="checkbox" name="ids[]" value="{{ $doc->id }}" x-model="selected">
                                                                                            </div>
                                                                                        @endif
                                                                                        <div class="flex-grow-1 text-truncate small d-flex align-items-center">
                                                                                            <div class="flex-shrink-0 me-2 text-primary bg-primary bg-opacity-10 p-1 rounded">
                                                                                                @if($doc->is_link) <i class="bi bi-link-45deg"></i> @else <i class="bi bi-file-image"></i> @endif
                                                                                            </div>
                                                                                            <span class="text-truncate" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</span>
                                                                                        </div>
                                                                                        <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                                                                            @if(!$doc->is_link) 
                                                                                                <a href="{{ Storage::disk($doc->disk ?? 'public')->url($doc->path_file) }}" target="_blank" class="btn btn-xs btn-outline-secondary"><i class="bi bi-eye"></i></a> 
                                                                                                <a href="{{ route('dokumen.download', $doc->id) }}" class="btn btn-xs btn-outline-primary"><i class="bi bi-download"></i></a> 
                                                                                            @else 
                                                                                                <a href="{{ $doc->path_url }}" target="_blank" class="btn btn-xs btn-outline-info w-100"><i class="bi bi-box-arrow-up-right me-1"></i>Buka</a> 
                                                                                            @endif
                                                                                        </div>
                                                                                    </div>
                                                                                @empty 
                                                                                    <div class="text-center py-3 text-muted small fst-italic">Tidak ada dokumentasi.</div> 
                                                                                @endforelse
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="col-lg-6">
                                                                        <div class="card h-100 border bg-light shadow-none">
                                                                            <div class="card-header bg-transparent border-bottom fw-bold text-danger d-flex justify-content-between align-items-center">
                                                                                <div class="form-check">
                                                                                    @if(count($lampiranIds) > 0)
                                                                                        <input class="form-check-input cursor-pointer" type="checkbox" @change="toggleAll({{ json_encode($lampiranIds) }})" :checked="isAllSelected({{ json_encode($lampiranIds) }})">
                                                                                    @endif
                                                                                    <label class="form-check-label cursor-pointer select-none"><i class="bi bi-paperclip me-2"></i>Lampiran</label>
                                                                                </div>
                                                                                <span class="badge bg-danger rounded-pill">{{ $lampirans->count() }}</span>
                                                                            </div>
                                                                            <div class="card-body p-2" style="max-height: 250px; overflow-y: auto;">
                                                                                @forelse($lampirans as $doc)
                                                                                    <div class="d-flex align-items-center bg-white border rounded p-2 mb-2 shadow-sm" :class="isSelected({{ $doc->id }}) ? 'border-danger bg-danger bg-opacity-10' : ''">
                                                                                        @if(!$doc->is_link)
                                                                                            <div class="form-check me-2 d-flex align-items-center">
                                                                                                <input class="form-check-input shadow-none cursor-pointer" type="checkbox" name="ids[]" value="{{ $doc->id }}" x-model="selected">
                                                                                            </div>
                                                                                        @endif
                                                                                        <div class="flex-grow-1 text-truncate small d-flex align-items-center">
                                                                                            <div class="flex-shrink-0 me-2 text-danger bg-danger bg-opacity-10 p-1 rounded">
                                                                                                @if($doc->is_link) 
                                                                                                    <i class="bi bi-link-45deg"></i> 
                                                                                                @elseif(Str::contains($doc->tipe_file, 'pdf')) 
                                                                                                    <i class="bi bi-file-pdf"></i> 
                                                                                                @else 
                                                                                                    <i class="bi bi-file-earmark-text"></i> 
                                                                                                @endif
                                                                                            </div>
                                                                                            <span class="text-truncate" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</span>
                                                                                        </div>
                                                                                        <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                                                                            @if(!$doc->is_link) 
                                                                                                <a href="{{ Storage::disk($doc->disk ?? 'public')->url($doc->path_file) }}" target="_blank" class="btn btn-xs btn-outline-secondary"><i class="bi bi-eye"></i></a> 
                                                                                                <a href="{{ route('dokumen.download', $doc->id) }}" class="btn btn-xs btn-outline-danger"><i class="bi bi-download"></i></a> 
                                                                                            @else 
                                                                                                <a href="{{ $doc->path_url }}" target="_blank" class="btn btn-xs btn-outline-info w-100"><i class="bi bi-box-arrow-up-right me-1"></i>Buka</a> 
                                                                                            @endif
                                                                                        </div>
                                                                                    </div>
                                                                                @empty 
                                                                                    <div class="text-center py-3 text-muted small fst-italic">Tidak ada lampiran.</div> 
                                                                                @endforelse
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                
                                                                @php $hasPhysicalFiles = $r->dokumen->where('is_link', false)->count() > 0; @endphp
                                                                @if($hasPhysicalFiles)
                                                                    <div class="col-12 text-end mt-3 border-top pt-3">
                                                                        <button type="button" @click="submitDownload" class="btn btn-dark btn-sm px-4 shadow-sm" :disabled="selected.length === 0">
                                                                            <i class="bi bi-file-earmark-zip-fill me-2"></i>Download File Terpilih (.ZIP)
                                                                        </button>
                                                                    </div>
                                                                @endif
                                                                
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
    [x-cloak] { display: none !important; }
    .btn-xs { padding: 1px 5px; font-size: 0.75rem; }
</style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener('alpine:init', () => {
        Alpine.data('fileDownloader', () => ({
            selected: [],
            isSelected(id) { 
                return this.selected.includes(id.toString()) || this.selected.includes(id); 
            },
            toggle(id) { 
                const strId = id.toString(); 
                if (this.selected.includes(strId)) { 
                    this.selected = this.selected.filter(i => i !== strId); 
                } else { 
                    this.selected.push(strId); 
                } 
            },
            toggleAll(ids) { 
                const stringIds = ids.map(String); 
                const allSelected = stringIds.every(id => this.selected.includes(id)); 
                if (allSelected) { 
                    this.selected = this.selected.filter(id => !stringIds.includes(id)); 
                } else { 
                    this.selected = [...new Set([...this.selected, ...stringIds])]; 
                } 
            },
            isAllSelected(ids) { 
                if (ids.length === 0) return false; 
                const stringIds = ids.map(String); 
                return stringIds.every(id => this.selected.includes(id)); 
            },
            submitDownload() { 
                if (this.selected.length === 0) { 
                    Swal.fire({
                        icon: 'warning', 
                        title: 'Pilih File', 
                        text: 'Silakan centang minimal satu file.'
                    }); 
                    return; 
                } 
                this.$refs.formZip.submit(); 
            }
        }));
    });
</script>
@endpush