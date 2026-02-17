<div class="bg-white">
    
    {{-- BAGIAN 1: HEADER INFORMASI REGISTER --}}
    <div class="p-4 border-bottom bg-light bg-opacity-25">
        <div class="row g-4 text-start">
            {{-- Satuan Kerja --}}
            <div class="col-md-4">
                <label class="small text-secondary fw-bold text-uppercase mb-1">Satuan Kerja</label>
                <div class="fw-bold text-dark">{{ $register->satuanKerja->satuan_kerja ?? '-' }}</div>
            </div>
            
            {{-- Tanggal --}}
            <div class="col-md-4">
                <label class="small text-secondary fw-bold text-uppercase mb-1">Tanggal Perolehan</label>
                <div class="text-dark">{{ $register->tanggal_perolehan->locale('id')->translatedFormat('l, d F Y') }}</div>
            </div>

            {{-- Lokasi / Koordinat --}}
            <div class="col-md-4">
                <label class="small text-secondary fw-bold text-uppercase mb-1">Lokasi Perolehan</label>
                <div class="text-dark mb-1 small">{{ $register->lokasi_perolehan ?? '-' }}</div>
                @if($register->latitude && $register->longitude)
                    <a href="https://www.google.com/maps/search/?api=1&query={{ $register->latitude }},{{ $register->longitude }}" target="_blank" 
                       class="btn btn-xs btn-outline-primary rounded-pill shadow-sm py-0 px-2" style="font-size: 0.7rem;">
                        <i class="bi bi-geo-alt-fill me-1"></i>Buka Maps
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- BAGIAN 2: TABEL ITEM BARANG BUKTI --}}
    <div class="p-4">
        <h6 class="fw-bold text-secondary mb-3 d-flex align-items-center">
            <i class="bi bi-box-seam-fill me-2 text-primary"></i>Daftar Item Barang Bukti
        </h6>

        <div class="table-responsive border rounded">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead class="bg-light text-secondary small text-uppercase">
                    <tr>
                        <th class="px-3 py-2">Nama Barang</th>
                        <th class="px-3 py-2 text-center">Kategori</th>
                        <th class="px-3 py-2 text-center">Sumber</th>
                        <th class="px-3 py-2 text-end">Jumlah</th>
                        <th class="px-3 py-2">Modus</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($register->items as $item)
                        <tr>
                            {{-- Nama Barang --}}
                            <td class="px-3 py-2">
                                @if($item->kategori == 'Narkotika')
                                    <span class="fw-bold text-dark">{{ $item->narkotika->nama_narkotika ?? 'Tanpa Nama' }}</span>
                                    @if($item->narkotika && $item->narkotika->golongan)
                                        <div class="text-muted small" style="font-size: 0.65rem;">{{ $item->narkotika->golongan }}</div>
                                    @endif
                                @else
                                    <span class="fw-bold text-dark">{{ $item->nama_barang_non_narkotika }}</span>
                                @endif
                            </td>

                            {{-- Kategori --}}
                            <td class="px-3 py-2 text-center">
                                @if($item->kategori == 'Narkotika')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Narkotika</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">Non-Narkotika</span>
                                @endif
                            </td>

                            {{-- Sumber Perolehan (Tangkap/Temuan) --}}
                            <td class="px-3 py-2 text-center">
                                @if($item->sumber_perolehan == 'Hasil Tangkap')
                                    <span class="badge bg-danger text-white shadow-sm">
                                        <i class="bi bi-handcuffs me-1"></i>Tangkap
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark shadow-sm">
                                        <i class="bi bi-search me-1"></i>Temuan
                                    </span>
                                @endif
                            </td>

                            {{-- Jumlah / Berat --}}
                            <td class="px-3 py-2 text-end font-monospace fw-bold text-dark">
                                {{ $formatAngka($item->kuantitas) }} 
                                <span class="text-muted small fw-normal">
                                    {{ $item->satuan_narkotika ?? $item->satuan_non_narkotika }}
                                </span>
                            </td>

                            {{-- Modus --}}
                            <td class="px-3 py-2 small text-muted">
                                {{ $item->modus_pengiriman ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted fst-italic">
                                Tidak ada item barang bukti dalam register ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer Ringkasan --}}
        <div class="mt-3 text-end">
            <small class="text-muted">Total Item:</small> 
            <strong class="text-dark">{{ $register->items->count() }}</strong>
        </div>
    </div>

</div>