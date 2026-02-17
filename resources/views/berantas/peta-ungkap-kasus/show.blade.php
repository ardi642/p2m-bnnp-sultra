{{-- 
    LOGIKA JAVASCRIPT INLINE
    Agar berfungsi 100% saat di-load via AJAX/Modal 
--}}
<div class="bg-white" x-data="{
    selected: [],
    
    // Cek apakah ID terpilih
    isSelected(id) { 
        return this.selected.includes(id.toString()) || this.selected.includes(id); 
    },
    
    // Toggle Select All (Bisa untuk Dokumen saja atau Lampiran saja tergantung input array IDs)
    toggleAll(ids) {
        const stringIds = ids.map(String);
        // Cek apakah semua ID dalam list ini sudah terpilih
        const allSelected = stringIds.every(id => this.selected.includes(id));
        
        if (allSelected) { 
            // Uncheck: Hapus ID yang ada di list ini dari selected global
            this.selected = this.selected.filter(id => !stringIds.includes(id)); 
        } else { 
            // Check: Gabungkan selected lama dengan ID baru (Set untuk unik)
            this.selected = [...new Set([...this.selected, ...stringIds])]; 
        }
    },
    
    // Cek status checkbox 'Pilih Semua' untuk grup tertentu
    isAllSelected(ids) {
        if (ids.length === 0) return false;
        const stringIds = ids.map(String);
        return stringIds.every(id => this.selected.includes(id));
    },
    
    // LOGIKA DOWNLOAD ZIP
    submitDownload() {
        if (this.selected.length === 0) return;
        
        // Ambil elemen form menggunakan x-ref
        const form = this.$refs.zipFormModal;
        if (!form) return;
        
        // 1. Bersihkan input hidden lama (jika ada)
        form.querySelectorAll('input[name=\'ids[]\']').forEach(el => el.remove());
        
        // 2. Inject input hidden baru berdasarkan pilihan
        this.selected.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });
        
        // 3. Submit Form
        form.submit();
    }
}">
    
    {{-- BAGIAN 1: HEADER & INFO UTAMA --}}
    <div class="p-4 border-bottom bg-light bg-opacity-25">
        <div class="row g-4 text-start">
            <div class="col-md-3">
                <label class="small text-secondary fw-bold text-uppercase mb-1">Satuan Kerja</label>
                <div class="fw-bold text-dark">{{ $item->satuanKerja->satuan_kerja ?? '-' }}</div>
            </div>
            <div class="col-md-3">
                <label class="small text-secondary fw-bold text-uppercase mb-1">Nomor LKN</label>
                <div class="fw-bold text-dark font-monospace">{{ $item->nomor_lkn }}</div>
            </div>
            <div class="col-md-3">
                <label class="small text-secondary fw-bold text-uppercase mb-1">Tanggal Kejadian</label>
                <div class="text-dark">{{ $item->tanggal_kejadian->locale('id')->translatedFormat('l, d F Y') }}</div>
            </div>
            <div class="col-md-3">
                <label class="small text-secondary fw-bold text-uppercase mb-1">Lokasi TKP</label>
                <div class="text-dark mb-1">{{ $item->alamat_tkp }}</div>
                @if($item->latitude && $item->longitude)
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $item->latitude }},{{ $item->longitude }}" target="_blank" class="btn btn-xs btn-outline-primary rounded-pill shadow-sm py-0 px-2" style="font-size: 0.7rem;">
                        <i class="bi bi-geo-alt-fill me-1"></i>Buka Maps
                    </a>
                @endif
            </div>
        </div>
        
        <div class="mt-4 pt-3 border-top border-dashed">
            <label class="small text-secondary fw-bold text-uppercase mb-1">Kronologis Singkat</label>
            <div class="text-muted small" style="white-space: pre-wrap;">{{ $item->kronologis ?? '-' }}</div>
        </div>
    </div>

    <div class="p-4">
        {{-- BAGIAN 2: TABEL TERSANGKA & BB --}}
        <h6 class="fw-bold text-secondary mb-3 d-flex align-items-center">
            <i class="bi bi-people-fill me-2 text-primary"></i>Rincian Tersangka & Barang Bukti
        </h6>
        
        <div class="table-responsive border rounded mb-3">
            <table class="table table-bordered mb-0 align-middle">
                <thead class="bg-light text-secondary small text-uppercase">
                    <tr>
                        <th class="px-3 py-2 bg-light">Nama Tersangka</th>
                        <th class="px-3 py-2 bg-light">Pekerjaan / Tahap</th>
                        <th class="px-3 py-2 bg-light">Barang Bukti</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $totalBeratGram = 0; 
                        $totalItemBB = 0; 
                    @endphp

                    @foreach($evidenceGroups as $group)
                        <tr>
                            <td class="align-top px-3 py-2 bg-white">
                                @foreach($group->first()->tersangka as $t)
                                    <div class="mb-1 d-flex align-items-center">
                                        <span class="fw-bold text-dark me-2">{{ $t->nama_tersangka }}</span>
                                        <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.65rem;">{{ $t->jenis_kelamin == 'Laki-Laki' ? 'L' : 'P' }}</span>
                                    </div>
                                @endforeach
                            </td>
                            <td class="align-top px-3 py-2 bg-white">
                                @foreach($group->first()->tersangka as $t)
                                    <div class="mb-1 small text-muted">{{ $t->pekerjaan ?? '-' }} / {{ $t->tahap }}</div>
                                @endforeach
                            </td>
                            <td class="align-top px-3 py-2 bg-white">
                                @foreach($group as $bb)
                                    @php
                                        $berat = $bb->kuantitas;
                                        if($bb->kategori == 'Narkotika') {
                                            if($bb->satuan_narkotika == 'Kg') $berat *= 1000;
                                            if($bb->satuan_narkotika == 'Ton') $berat *= 1000000;
                                            $totalBeratGram += $berat;
                                        }
                                        $totalItemBB++;
                                    @endphp
                                    <div class="d-flex align-items-center small mb-1 p-1 border rounded bg-light">
                                        <i class="bi {{ $bb->kategori == 'Narkotika' ? 'bi-capsule text-danger' : 'bi-box-seam text-success' }} me-2"></i>
                                        <span class="fw-semibold me-1">{{ $bb->nama_barang_non_narkotika ?? $bb->narkotika->nama_narkotika }}</span>
                                        <span class="text-muted">({{ $formatAngka($bb->kuantitas) }} {{ $bb->satuan_narkotika ?? $bb->satuan_non_narkotika }})</span>
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach

                    @foreach($orphanSuspects as $t)
                        <tr>
                            <td class="px-3 py-2 bg-white">
                                <span class="fw-bold text-danger">{{ $t->nama_tersangka }}</span>
                                <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $t->jenis_kelamin }}</span>
                            </td>
                            <td class="px-3 py-2 text-muted small bg-white">{{ $t->pekerjaan ?? '-' }} / {{ $t->tahap }}</td>
                            <td class="px-3 py-2 text-muted fst-italic small bg-white">Tidak ada barang bukti</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- RINGKASAN TOTAL (STATS) --}}
        <div class="card bg-primary bg-opacity-10 border-primary border-opacity-25 mb-4">
            <div class="card-body py-2 px-3">
                <div class="row text-center text-md-start align-items-center">
                    <div class="col-md-4 mb-2 mb-md-0">
                        <small class="text-uppercase fw-bold text-primary opacity-75" style="font-size: 0.7rem;">Total Tersangka</small>
                        <div class="fs-5 fw-bold text-dark">{{ $item->tersangka->count() }} <span class="fs-6 fw-normal text-muted">Orang</span></div>
                    </div>
                    <div class="col-md-4 mb-2 mb-md-0 border-start border-primary border-opacity-25">
                        <small class="text-uppercase fw-bold text-primary opacity-75" style="font-size: 0.7rem;">Total Berat Narkotika</small>
                        <div class="fs-5 fw-bold text-danger">{{ number_format($totalBeratGram, 2, ',', '.') }} <span class="fs-6 fw-normal text-muted">Gram</span></div>
                    </div>
                    <div class="col-md-4 border-start border-primary border-opacity-25">
                        <small class="text-uppercase fw-bold text-primary opacity-75" style="font-size: 0.7rem;">Total Item Barang</small>
                        <div class="fs-5 fw-bold text-dark">{{ $totalItemBB }} <span class="fs-6 fw-normal text-muted">Item</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BAGIAN 3: FILE DOKUMENTASI & LAMPIRAN (LAYOUT SPLIT KOLOM SEPERTI INDEX) --}}
    <div class="p-4 border-top bg-light bg-opacity-25">
        
        {{-- FORM WRAPPER UNTUK DOWNLOAD ZIP --}}
        <form action="{{ route('dokumen.zip.selected') }}" method="POST" x-ref="zipFormModal">
            @csrf
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-secondary m-0">
                    <i class="bi bi-folder2-open me-2 text-warning"></i>Dokumentasi & Lampiran
                </h6>
                
                {{-- TOMBOL DOWNLOAD ZIP --}}
                @php $hasFiles = $item->dokumen->where('is_link', false)->count() > 0; @endphp
                @if($hasFiles)
                    <button type="button" @click="submitDownload" class="btn btn-sm btn-dark shadow-sm" :disabled="selected.length === 0">
                        <i class="bi bi-file-earmark-zip-fill me-2"></i>Download File Terpilih (.ZIP)
                    </button>
                @endif
            </div>

            <div class="row g-4">
                @php
                    $fotos = $item->dokumen->where('kategori', 'dokumentasi');
                    $lampirans = $item->dokumen->where('kategori', 'lampiran');
                    $fotoIds = $fotos->where('is_link', false)->pluck('id')->values()->toArray();
                    $lampiranIds = $lampirans->where('is_link', false)->pluck('id')->values()->toArray();
                @endphp
                
                {{-- KOLOM KIRI: DOKUMENTASI --}}
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
                        <div class="card-body p-2" style="max-height: 300px; overflow-y: auto;">
                            @forelse($fotos as $doc)
                                <div class="d-flex align-items-center bg-white border rounded p-2 mb-2 shadow-sm hover-shadow transition-all" 
                                     :class="isSelected({{ $doc->id }}) ? 'border-primary bg-primary bg-opacity-10' : ''">
                                    
                                    @if(!$doc->is_link)
                                        <div class="form-check me-2 d-flex align-items-center">
                                            {{-- Value ID disimpan di x-model 'selected' --}}
                                            <input class="form-check-input shadow-none cursor-pointer" type="checkbox" value="{{ $doc->id }}" x-model="selected">
                                        </div>
                                    @endif
                                    
                                    <label class="flex-grow-1 text-truncate small d-flex align-items-center m-0 cursor-pointer" @click="if(!'{{ $doc->is_link }}') { if(isSelected({{ $doc->id }})) selected = selected.filter(id => id != {{ $doc->id }}); else selected.push('{{ $doc->id }}'); }">
                                        <div class="flex-shrink-0 me-2 text-primary bg-primary bg-opacity-10 p-1 rounded">
                                            @if($doc->is_link) <i class="bi bi-link-45deg"></i> @else <i class="bi bi-file-image"></i> @endif
                                        </div>
                                        <span class="text-truncate fw-bold text-dark" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</span>
                                    </label>
                                    
                                    <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                        @if(!$doc->is_link) 
                                            <a href="{{ route('dokumen.download', $doc->id) }}" class="btn btn-xs btn-outline-primary"><i class="bi bi-download"></i></a> 
                                        @else 
                                            <a href="{{ $doc->path_url }}" target="_blank" class="btn btn-xs btn-outline-info w-100"><i class="bi bi-box-arrow-up-right me-1"></i>Buka</a> 
                                        @endif
                                    </div>
                                </div>
                            @empty 
                                <div class="text-center py-4 text-muted fst-italic">Tidak ada dokumentasi.</div> 
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- KOLOM KANAN: LAMPIRAN --}}
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
                        <div class="card-body p-2" style="max-height: 300px; overflow-y: auto;">
                            @forelse($lampirans as $doc)
                                <div class="d-flex align-items-center bg-white border rounded p-2 mb-2 shadow-sm hover-shadow transition-all" 
                                     :class="isSelected({{ $doc->id }}) ? 'border-danger bg-danger bg-opacity-10' : ''">
                                    
                                    @if(!$doc->is_link)
                                        <div class="form-check me-2 d-flex align-items-center">
                                            <input class="form-check-input shadow-none cursor-pointer" type="checkbox" value="{{ $doc->id }}" x-model="selected">
                                        </div>
                                    @endif
                                    
                                    <label class="flex-grow-1 text-truncate small d-flex align-items-center m-0 cursor-pointer" @click="if(!'{{ $doc->is_link }}') { if(isSelected({{ $doc->id }})) selected = selected.filter(id => id != {{ $doc->id }}); else selected.push('{{ $doc->id }}'); }">
                                        <div class="flex-shrink-0 me-2 text-danger bg-danger bg-opacity-10 p-1 rounded">
                                            @if($doc->is_link) <i class="bi bi-link-45deg"></i> @elseif(Str::contains($doc->tipe_file, 'pdf')) <i class="bi bi-file-pdf"></i> @else <i class="bi bi-file-earmark-text"></i> @endif
                                        </div>
                                        <span class="text-truncate fw-bold text-dark" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</span>
                                    </label>
                                    
                                    <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                        @if(!$doc->is_link) 
                                            <a href="{{ route('dokumen.download', $doc->id) }}" class="btn btn-xs btn-outline-danger"><i class="bi bi-download"></i></a> 
                                        @else 
                                            <a href="{{ $doc->path_url }}" target="_blank" class="btn btn-xs btn-outline-info w-100"><i class="bi bi-box-arrow-up-right me-1"></i>Buka</a> 
                                        @endif
                                    </div>
                                </div>
                            @empty 
                                <div class="text-center py-4 text-muted fst-italic">Tidak ada lampiran.</div> 
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>