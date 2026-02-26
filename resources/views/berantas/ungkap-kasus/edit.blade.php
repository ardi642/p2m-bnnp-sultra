@extends('admin')

@section('content')
<main class="admin-main" x-data="kasusForm">
    <div class="container-fluid p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Edit Ungkap Kasus</h4>
                <p class="text-secondary small mb-0">Perbarui Data Kasus, Tersangka, dan Barang Bukti</p>
            </div>
            <a href="{{ route('berantas.ungkap-kasus.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div><strong>Input Tidak Valid!</strong> Silakan cek pesan error di bawah field yang berwarna merah.</div>
                </div>
            </div>
        @endif

        <form action="{{ route('berantas.ungkap-kasus.update', $kasus->id) }}" method="POST" enctype="multipart/form-data" id="form-kasus">
            @csrf
            @method('PUT')

            {{-- 1. DATA LKN & LOKASI --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold m-0 text-primary"><i class="bi bi-geo-alt me-2"></i>Data LKN & Lokasi Kejadian</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        @if(Auth::user()->isAdmin())
                        <div class="col-12">
                            <label class="form-label fw-bold small text-secondary">Satuan Kerja</label>
                            <select name="satuan_kerja_id" class="form-select @error('satuan_kerja_id') is-invalid @enderror">
                                <option value="" disabled>Pilih Satuan Kerja...</option>
                                @foreach($satuanKerjas as $s) 
                                    <option value="{{ $s->id }}" @selected(old('satuan_kerja_id', $kasus->satuan_kerja_id) == $s->id)>{{ $s->satuan_kerja }}</option> 
                                @endforeach
                            </select>
                            @error('satuan_kerja_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Nomor LKN <span class="text-danger">*</span></label>
                            <input type="text" name="nomor_lkn" class="form-control @error('nomor_lkn') is-invalid @enderror" value="{{ old('nomor_lkn', $kasus->nomor_lkn) }}" placeholder="Contoh: LKN/...">
                            @error('nomor_lkn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-secondary">Tanggal Kejadian <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_kejadian" class="form-control @error('tanggal_kejadian') is-invalid @enderror" value="{{ old('tanggal_kejadian', $kasus->tanggal_kejadian->format('Y-m-d')) }}">
                            @error('tanggal_kejadian') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- MAPS & KOORDINAT --}}
                        <div class="col-12" x-data="locationPicker">
                            <label class="form-label fw-semibold small text-secondary">Titik Koordinat</label>
                            <div class="row g-2 mb-2">
                                <div class="col-12 col-md-5">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-secondary small">Lat</span>
                                        <input type="text" name="latitude" x-model="lat" class="form-control @error('latitude') is-invalid @enderror" placeholder="-4.xxxx" @input="updateMarker">
                                    </div>
                                    @error('latitude') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 col-md-5">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-secondary small">Lng</span>
                                        <input type="text" name="longitude" x-model="lng" class="form-control @error('longitude') is-invalid @enderror" placeholder="122.xxxx" @input="updateMarker">
                                    </div>
                                    @error('longitude') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 col-md-2">
                                    <button type="button" class="btn btn-outline-primary w-100" @click="getGPS" :disabled="isLoading" title="Ambil Lokasi Saat Ini">
                                        <span x-show="isLoading" style="display: none;">
                                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                            <span class="ms-1">...</span>
                                        </span>
                                        <span x-show="!isLoading"><i class="bi bi-geo-alt-fill me-1"></i>GPS</span>
                                    </button>
                                </div>
                            </div>
                            <div wire:ignore id="map" style="height: 350px; border-radius: 6px; border: 1px solid #dee2e6; z-index: 1;"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold small text-secondary">Alamat TKP Lengkap <span class="text-danger">*</span></label>
                            <textarea name="alamat_tkp" class="form-control @error('alamat_tkp') is-invalid @enderror" rows="2" placeholder="Jalan, RT/RW...">{{ old('alamat_tkp', $kasus->alamat_tkp) }}</textarea>
                            @error('alamat_tkp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold small text-secondary">Kronologis Kejadian</label>
                            <textarea name="kronologis" class="form-control auto-resize" rows="3" x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' } }" x-init="resize()" @input="resize()" placeholder="Ceritakan kronologis...">{{ old('kronologis', $kasus->kronologis) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. MASTER DATA TERSANGKA - RESPONSIVE GRID --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold m-0 text-dark"><i class="bi bi-people-fill me-2"></i>I. Data Tersangka</h6>
                    <button type="button" class="btn btn-dark btn-sm px-3" @click="addMaster">
                        <i class="bi bi-person-plus-fill me-1"></i> Tambah
                    </button>
                </div>
                <div class="card-body p-3 bg-light">
                    @error('tersangka') <div class="alert alert-danger small py-2 mb-3 fw-bold">{{ $message }}</div> @enderror

                    <template x-for="(tsk, idx) in masterSuspects" :key="tsk.temp_id">
                        <div class="card border border-secondary-subtle shadow-sm mb-3">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                <span class="fw-bold text-secondary small">Tersangka #<span x-text="idx+1"></span></span>
                                <button type="button" class="btn btn-sm text-danger p-0" @click="removeMaster(idx)" x-show="masterSuspects.length > 1" title="Hapus Tersangka">
                                    <i class="bi bi-trash fs-5"></i>
                                </button>
                            </div>
                            <div class="card-body p-3">
                                <input type="hidden" :name="`tersangka[${idx}][temp_id]`" :value="tsk.temp_id">
                                <input type="hidden" :name="`tersangka[${idx}][old_foto]`" :value="tsk.old_foto">
                                
                                <div class="row g-3">
                                    {{-- Kolom Foto --}}
                                    <div class="col-12 col-md-auto text-center d-flex flex-column align-items-center justify-content-center" style="min-width: 120px;">
                                        <div class="position-relative d-inline-block">
                                            <div class="cursor-pointer" @click="document.getElementById('file_'+tsk.temp_id).click()">
                                                <img :src="tsk.preview || '{{ asset('assets/images/user-placeholder.png') }}'" class="rounded-circle border object-fit-cover shadow-sm" width="80" height="80">
                                                <div class="position-absolute bottom-0 end-0 bg-white border rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                    <i class="bi bi-camera-fill text-secondary" style="font-size: 14px;"></i>
                                                </div>
                                            </div>
                                            <button type="button" x-show="tsk.preview" @click="removePhoto(idx)" 
                                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border-0 p-1 shadow" 
                                                    style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; z-index: 10;" title="Hapus Foto">
                                                <i class="bi bi-x text-white" style="font-size: 16px;"></i>
                                            </button>
                                            <input type="file" :name="`tersangka[${idx}][foto]`" :id="'file_'+tsk.temp_id" class="d-none" accept="image/*" @change="handleMasterFoto($event, idx)">
                                        </div>
                                        <template x-if="hasError('tersangka', idx, 'foto')">
                                            <div class="text-danger small mt-1 text-center" style="font-size: 0.75rem;" x-text="getErrorMessage('tersangka', idx, 'foto')"></div>
                                        </template>
                                    </div>

                                    {{-- Kolom Form --}}
                                    <div class="col-12 col-md">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fs-10px fw-bold text-secondary text-uppercase mb-1">Nama Lengkap <span class="text-danger">*</span></label>
                                                <input type="text" :name="`tersangka[${idx}][nama]`" x-model="tsk.nama" class="form-control form-control-sm fw-bold" :class="{'is-invalid': hasError('tersangka', idx, 'nama')}" placeholder="Nama Tersangka...">
                                                <template x-if="hasError('tersangka', idx, 'nama')">
                                                    <div class="invalid-feedback fs-10px" x-text="getErrorMessage('tersangka', idx, 'nama')"></div>
                                                </template>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fs-10px fw-bold text-secondary text-uppercase mb-1">Jenis Kelamin</label>
                                                <select :name="`tersangka[${idx}][jk]`" x-model="tsk.jk" class="form-select form-select-sm" :class="{'is-invalid': hasError('tersangka', idx, 'jk')}">
                                                    <option value="Laki-Laki">Laki-Laki</option>
                                                    <option value="Perempuan">Perempuan</option>
                                                </select>
                                                <template x-if="hasError('tersangka', idx, 'jk')">
                                                    <div class="invalid-feedback fs-10px" x-text="getErrorMessage('tersangka', idx, 'jk')"></div>
                                                </template>
                                            </div>
                                            
                                            {{-- PEKERJAAN (Opsi Lainnya) --}}
                                            <div class="col-md-6">
                                                <label class="form-label fs-10px fw-bold text-secondary text-uppercase mb-1">Pekerjaan</label>
                                                <select x-model="tsk.pekerjaan_select" 
                                                        @change="if(tsk.pekerjaan_select !== 'Lainnya') tsk.pekerjaan = tsk.pekerjaan_select; else tsk.pekerjaan = ''"
                                                        class="form-select form-select-sm mb-2" :class="{'is-invalid': hasError('tersangka', idx, 'pekerjaan')}">
                                                    <option value="" disabled selected>Pilih Pekerjaan...</option>
                                                    <template x-for="p in pekerjaanList" :key="p">
                                                        <option :value="p" x-text="p"></option>
                                                    </template>
                                                    <option value="Lainnya">Lainnya (Ketik Manual)...</option>
                                                </select>
                                                
                                                <div x-show="tsk.pekerjaan_select === 'Lainnya'" x-transition>
                                                    <input type="text" class="form-control form-control-sm" placeholder="Sebutkan pekerjaan..." 
                                                           x-model="tsk.pekerjaan" :class="{'is-invalid': hasError('tersangka', idx, 'pekerjaan')}">
                                                </div>
                                                
                                                <input type="hidden" :name="`tersangka[${idx}][pekerjaan]`" :value="tsk.pekerjaan">
                                                
                                                <template x-if="hasError('tersangka', idx, 'pekerjaan')">
                                                    <div class="invalid-feedback d-block fs-10px" x-text="getErrorMessage('tersangka', idx, 'pekerjaan')"></div>
                                                </template>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label fs-10px fw-bold text-secondary text-uppercase mb-1">Tahap Kasus</label>
                                                <input type="text" :name="`tersangka[${idx}][tahap]`" x-model="tsk.tahap" class="form-control form-control-sm" :class="{'is-invalid': hasError('tersangka', idx, 'tahap')}" placeholder="Lidik / Sidik">
                                                <template x-if="hasError('tersangka', idx, 'tahap')">
                                                    <div class="invalid-feedback fs-10px" x-text="getErrorMessage('tersangka', idx, 'tahap')"></div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- 3. GROUPING BARANG BUKTI - RESPONSIVE GRID --}}
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark m-0">II. Kelompok Barang Bukti</h5>
                    <button type="button" class="btn btn-primary btn-sm shadow-sm px-3" @click="addBBGroup">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Kotak BB
                    </button>
                </div>

                <template x-for="(group, gIdx) in bbGroups" :key="group.id">
                    <div class="card border border-primary-subtle shadow-sm mb-4">
                        <div class="card-header bg-primary-subtle d-flex justify-content-between align-items-center py-2">
                            <span class="fw-bold text-primary small text-uppercase"><i class="bi bi-box-seam me-1"></i>Kotak BB #<span x-text="gIdx+1"></span></span>
                            <button type="button" class="btn btn-close btn-sm" @click="removeBBGroup(gIdx)" x-show="bbGroups.length > 1"></button>
                        </div>
                        <div class="card-body">
                            {{-- Checklist Pemilik --}}
                            <div class="mb-4 bg-light p-3 rounded border">
                                <label class="form-label fw-bold small text-secondary mb-2">Pemilik Barang Bukti Ini (Checklist) <span class="text-danger">*</span></label>
                                <div class="d-flex flex-wrap gap-2">
                                    <template x-for="tsk in masterSuspects" :key="tsk.temp_id">
                                        <div class="form-check form-check-inline bg-white px-3 py-2 border rounded m-0 shadow-sm user-select-none cursor-pointer position-relative" 
                                             :class="group.selectedOwners.includes(tsk.temp_id) ? 'border-primary bg-primary bg-opacity-10' : 'border-secondary-subtle'">
                                            <input class="form-check-input cursor-pointer" type="checkbox" :name="`bb_groups[${gIdx}][owners][]`" :value="tsk.temp_id" :id="`chk-${gIdx}-${tsk.temp_id}`" x-model="group.selectedOwners">
                                            <label class="form-check-label small fw-semibold cursor-pointer stretched-link" :for="`chk-${gIdx}-${tsk.temp_id}`" x-text="tsk.nama || '(Tanpa Nama)'"></label>
                                        </div>
                                    </template>
                                </div>
                                <div class="text-danger fs-10px mt-2" x-show="group.selectedOwners.length === 0">* Wajib pilih minimal satu pemilik untuk Kotak BB ini.</div>
                                <template x-if="hasError('bb_groups', gIdx, 'owners')">
                                    <div class="text-danger fs-10px mt-1 fw-bold" x-text="getErrorMessage('bb_groups', gIdx, 'owners')"></div>
                                </template>
                            </div>

                            {{-- Daftar Item Barang Bukti --}}
                            <div class="d-flex flex-column gap-3">
                                <template x-for="(item, iIdx) in group.items" :key="item.id">
                                    <div class="p-3 border rounded bg-white position-relative shadow-sm">
                                        <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                            <span class="badge bg-secondary">Item #<span x-text="iIdx+1"></span></span>
                                            <button type="button" class="btn btn-sm text-danger p-0" @click="removeItem(gIdx, iIdx)" x-show="group.items.length > 1">
                                                <i class="bi bi-x-circle-fill fs-5"></i>
                                            </button>
                                        </div>
                                        
                                        <div class="row g-3">
                                            <div class="col-12 col-md-3">
                                                <label class="form-label fs-10px fw-bold text-secondary text-uppercase mb-1">Kategori</label>
                                                <select :name="`bb_groups[${gIdx}][items][${iIdx}][kategori]`" x-model="item.kategori" class="form-select form-select-sm" @change="resetSatuan(item)">
                                                    <option value="Narkotika">Narkotika</option>
                                                    <option value="Non-Narkotika">Non-Narkotika</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-12 col-md-5">
                                                <label class="form-label fs-10px fw-bold text-secondary text-uppercase mb-1">Nama Barang / Jenis</label>
                                                <div x-show="item.kategori === 'Narkotika'" class="w-100">
                                                    <div wire:ignore :class="{'border border-danger rounded': hasErrorNested('bb_groups', gIdx, 'items', iIdx, 'narkotika_id')}">
                                                        <select :id="`narkotika-select-${gIdx}-${iIdx}`" :name="`bb_groups[${gIdx}][items][${iIdx}][narkotika_id]`" class="narkotika-select" placeholder="Pilih jenis narkotika..." x-init="initNarkotikaSelect($el, item.narkotika_id)">
                                                            <option value="">Pilih...</option>
                                                            @foreach($masterNarkotika as $n) <option value="{{ $n->id }}">{{ $n->nama_narkotika }}</option> @endforeach
                                                        </select>
                                                    </div>
                                                    <template x-if="hasErrorNested('bb_groups', gIdx, 'items', iIdx, 'narkotika_id')">
                                                        <div class="text-danger fs-10px mt-1" x-text="getErrorMessageNested('bb_groups', gIdx, 'items', iIdx, 'narkotika_id')"></div>
                                                    </template>
                                                </div>
                                                <div x-show="item.kategori === 'Non-Narkotika'" class="w-100">
                                                    <input type="text" :name="`bb_groups[${gIdx}][items][${iIdx}][nama_barang_bukti]`" x-model="item.nama_barang_bukti" class="form-control form-control-sm" placeholder="Contoh: HP Samsung..." :class="{'is-invalid': hasErrorNested('bb_groups', gIdx, 'items', iIdx, 'nama_barang_bukti')}">
                                                    <template x-if="hasErrorNested('bb_groups', gIdx, 'items', iIdx, 'nama_barang_bukti')">
                                                        <div class="invalid-feedback d-block fs-10px" x-text="getErrorMessageNested('bb_groups', gIdx, 'items', iIdx, 'nama_barang_bukti')"></div>
                                                    </template>
                                                </div>
                                            </div>

                                            <div class="col-6 col-md-2">
                                                <label class="form-label fs-10px fw-bold text-secondary text-uppercase mb-1" x-text="item.kategori === 'Narkotika' ? 'Berat' : 'Jumlah'"></label>
                                                <input type="number" step="0.0001" :name="`bb_groups[${gIdx}][items][${iIdx}][jumlah]`" x-model="item.jumlah" class="form-control form-control-sm" placeholder="0" :class="{'is-invalid': hasErrorNested('bb_groups', gIdx, 'items', iIdx, 'jumlah')}">
                                                <template x-if="hasErrorNested('bb_groups', gIdx, 'items', iIdx, 'jumlah')">
                                                    <div class="invalid-feedback d-block fs-10px" x-text="getErrorMessageNested('bb_groups', gIdx, 'items', iIdx, 'jumlah')"></div>
                                                </template>
                                            </div>

                                            <div class="col-6 col-md-2">
                                                <label class="form-label fs-10px fw-bold text-secondary text-uppercase mb-1">Satuan</label>
                                                <template x-if="item.kategori === 'Narkotika'">
                                                    <div>
                                                        <select :name="`bb_groups[${gIdx}][items][${iIdx}][satuan]`" x-model="item.satuan" class="form-select form-select-sm">
                                                            <option value="Gram">Gram</option>
                                                            <option value="Kg">Kg</option>
                                                            <option value="Ton">Ton</option>
                                                        </select>
                                                    </div>
                                                </template>
                                                <template x-if="item.kategori === 'Non-Narkotika'">
                                                    <div class="w-100">
                                                        <input type="text" :name="`bb_groups[${gIdx}][items][${iIdx}][satuan]`" x-model="item.satuan" class="form-control form-control-sm" placeholder="Unit/Pcs" :class="{'is-invalid': hasErrorNested('bb_groups', gIdx, 'items', iIdx, 'satuan')}">
                                                        <template x-if="hasErrorNested('bb_groups', gIdx, 'items', iIdx, 'satuan')">
                                                            <div class="invalid-feedback d-block fs-10px" x-text="getErrorMessageNested('bb_groups', gIdx, 'items', iIdx, 'satuan')"></div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <button type="button" class="btn btn-light btn-sm w-100 mt-3 border text-primary fw-bold dashed-border" @click="addItem(gIdx)">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Item Barang Bukti
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- 5. LAMPIRAN --}}
            <div class="card shadow-sm border-0 mb-5">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-primary"><i class="bi bi-paperclip me-2"></i>Dokumentasi & Lampiran</h5>
                </div>
                <div class="card-body p-4">
                    <div class="bg-body-tertiary p-4 rounded-3 border border-dashed">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold h6 mb-3 text-dark d-block border-bottom pb-2">
                                    <i class="bi bi-cloud-arrow-up me-2"></i>Upload File & Link Baru (Opsional)
                                </label>
                                <div class="row g-3">
                                    <div class="col-12 mt-5">
                                        {{-- KOTAK KHUSUS DOKUMENTASI LAMA --}}
                                        @php $oldFotos = $kasus->dokumen->where('kategori', 'dokumentasi'); @endphp
                                        @if($oldFotos->count() > 0)
                                            <div class="card bg-light border border-dashed mb-4">
                                                <div class="card-body">
                                                    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-images me-2"></i>Dokumentasi Tersimpan</h6>
                                                    <div class="row g-3">
                                                        @foreach($oldFotos as $doc)
                                                            @php $isMarkedDeleted = old('delete_files') && in_array($doc->id, old('delete_files')); @endphp
                                                            <div class="col-6 col-md-4 col-lg-3 file-item" id="file-card-{{ $doc->id }}">
                                                                <div class="card h-100 shadow-sm border border-secondary-subtle position-relative overflow-hidden file-card-inner transition-all {{ $isMarkedDeleted ? 'border-danger-subtle-thick' : '' }}">
                                                                    <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" style="background-color: rgba(255, 255, 255, 0.9); z-index: 5;">
                                                                        <div class="text-danger mb-1"><i class="bi bi-trash3-fill fs-1"></i></div><span class="text-danger fw-bold small text-uppercase">Akan Dihapus</span>
                                                                    </div>
                                                                    <div class="ratio ratio-16x9 bg-secondary bg-opacity-10 border-bottom d-flex align-items-center justify-content-center overflow-hidden">
                                                                        @if($doc->is_link) <div class="text-info"><i class="bi bi-link-45deg display-4"></i></div>
                                                                        @elseif(Str::contains($doc->tipe_file, 'image')) <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100">
                                                                        @elseif(Str::contains($doc->tipe_file, 'pdf')) <div class="text-danger"><i class="bi bi-file-earmark-pdf-fill display-4"></i></div>
                                                                        @else <div class="text-secondary"><i class="bi bi-file-earmark-text-fill display-4"></i></div> @endif
                                                                    </div>
                                                                    <div class="card-body p-2 text-center d-flex flex-column justify-content-between">
                                                                        <div class="mb-2">
                                                                            <div class="small text-truncate fw-bold" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</div>
                                                                            @if($doc->is_link) <div class="text-muted small fst-italic text-truncate"><a href="{{ $doc->path_url }}" target="_blank">{{ $doc->path_url }}</a></div> @endif
                                                                        </div>
                                                                        <div class="d-flex gap-1 justify-content-center position-relative" style="z-index: 10;">
                                                                            @if(!$doc->is_link)
                                                                                <a href="{{ route('dokumen.download', $doc->id) }}" class="btn btn-outline-primary btn-sm w-100 py-0" title="Unduh"><i class="bi bi-download"></i></a>
                                                                            @else
                                                                                <a href="{{ $doc->path_url }}" target="_blank" class="btn btn-outline-info btn-sm w-100 py-0" title="Buka"><i class="bi bi-box-arrow-up-right"></i></a>
                                                                            @endif
                                                                            <button type="button" id="btn-delete-{{ $doc->id }}" class="btn btn-sm w-100 py-0 {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" onclick="markForDeletion({{ $doc->id }})">@if($isMarkedDeleted) Batal @else Hapus @endif</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- KOTAK KHUSUS LAMPIRAN LAMA --}}
                                        @php $oldLampirans = $kasus->dokumen->where('kategori', 'lampiran'); @endphp
                                        @if($oldLampirans->count() > 0)
                                            <div class="card bg-light border border-dashed mb-4">
                                                <div class="card-body">
                                                    <h6 class="fw-bold text-danger mb-3"><i class="bi bi-paperclip me-2"></i>Lampiran Tersimpan</h6>
                                                    <div class="row g-3">
                                                        @foreach($oldLampirans as $doc)
                                                            @php $isMarkedDeleted = old('delete_files') && in_array($doc->id, old('delete_files')); @endphp
                                                            <div class="col-6 col-md-4 col-lg-3 file-item" id="file-card-{{ $doc->id }}">
                                                                <div class="card h-100 shadow-sm border border-secondary-subtle position-relative overflow-hidden file-card-inner transition-all {{ $isMarkedDeleted ? 'border-danger-subtle-thick' : '' }}">
                                                                    <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" style="background-color: rgba(255, 255, 255, 0.9); z-index: 5;">
                                                                        <div class="text-danger mb-1"><i class="bi bi-trash3-fill fs-1"></i></div><span class="text-danger fw-bold small text-uppercase">Akan Dihapus</span>
                                                                    </div>
                                                                    <div class="ratio ratio-16x9 bg-secondary bg-opacity-10 border-bottom d-flex align-items-center justify-content-center overflow-hidden">
                                                                        @if($doc->is_link) <div class="text-info"><i class="bi bi-link-45deg display-4"></i></div>
                                                                        @elseif(Str::contains($doc->tipe_file, 'image')) <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100">
                                                                        @elseif(Str::contains($doc->tipe_file, 'pdf')) <div class="text-danger"><i class="bi bi-file-earmark-pdf-fill display-4"></i></div>
                                                                        @else <div class="text-secondary"><i class="bi bi-file-earmark-text-fill display-4"></i></div> @endif
                                                                    </div>
                                                                    <div class="card-body p-2 text-center d-flex flex-column justify-content-between">
                                                                        <div class="mb-2">
                                                                            <div class="small text-truncate fw-bold" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</div>
                                                                            @if($doc->is_link) <div class="text-muted small fst-italic text-truncate"><a href="{{ $doc->path_url }}" target="_blank">{{ $doc->path_url }}</a></div> @endif
                                                                        </div>
                                                                        <div class="d-flex gap-1 justify-content-center position-relative" style="z-index: 10;">
                                                                            @if(!$doc->is_link)
                                                                                <a href="{{ route('dokumen.download', $doc->id) }}" class="btn btn-outline-primary btn-sm w-100 py-0" title="Unduh"><i class="bi bi-download"></i></a>
                                                                            @else
                                                                                <a href="{{ $doc->path_url }}" target="_blank" class="btn btn-outline-info btn-sm w-100 py-0" title="Buka"><i class="bi bi-box-arrow-up-right"></i></a>
                                                                            @endif
                                                                            <button type="button" id="btn-delete-{{ $doc->id }}" class="btn btn-sm w-100 py-0 {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" onclick="markForDeletion({{ $doc->id }})">@if($isMarkedDeleted) Batal @else Hapus @endif</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        <div id="delete-inputs-container">
                                            @if(old('delete_files'))
                                                @foreach(old('delete_files') as $deletedId)
                                                    <input type="hidden" name="delete_files[]" value="{{ $deletedId }}" id="input-delete-{{ $deletedId }}">
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="bg-white p-3 rounded border h-100 d-flex flex-column shadow-sm">
                                            <label class="form-label fw-bold small text-primary mb-1"><i class="bi bi-folder2-open me-2"></i>Dokumentasi Baru</label>
                                            <div class="mb-3">
                                                <p class="text-muted small mb-2" style="font-size: 0.75rem">Upload dokumentasi. Maksimal 10MB.</p>
                                                <input type="file" id="fp-dokumentasi" name="dokumentasi[]" multiple>
                                            </div>
                                            <hr class="border-secondary-subtle my-3">
                                            <div x-data="linkManager( {{ \Illuminate\Support\Js::from(array_values(old('dokumentasi_links', []))) }} )">
                                                <label class="form-label fw-bold small text-primary mb-2"><i class="bi bi-link-45deg me-1"></i>Atau Tautkan Link</label>
                                                <template x-for="(link, index) in links" :key="index">
                                                    <div class="input-group mb-2 input-group-sm">
                                                        <input type="text" class="form-control" :name="`dokumentasi_links[${index}][nama]`" placeholder="Nama Tautan" x-model="link.nama" required>
                                                        <input type="url" class="form-control" :name="`dokumentasi_links[${index}][url]`" placeholder="https://" x-model="link.url" required>
                                                        <button type="button" class="btn btn-outline-danger" @click="removeLink(index)"><i class="bi bi-x"></i></button>
                                                    </div>
                                                </template>
                                                @error('dokumentasi_links.*') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                                                <button type="button" class="btn btn-xs btn-outline-primary dashed-border w-100 mt-1" @click="addLink()"><i class="bi bi-plus-circle me-1"></i> Tambah Link</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="bg-white p-3 rounded border h-100 d-flex flex-column shadow-sm">
                                            <label class="form-label fw-bold small text-danger mb-1"><i class="bi bi-paperclip me-2"></i>Lampiran Pendukung Baru</label>
                                            <div class="mb-3">
                                                <p class="text-muted small mb-2" style="font-size: 0.75rem">Upload file pendukung. Maksimal 10MB.</p>
                                                <input type="file" id="fp-lampiran" name="lampiran[]" multiple>
                                            </div>
                                            <hr class="border-secondary-subtle my-3">
                                            <div x-data="linkManager( {{ \Illuminate\Support\Js::from(array_values(old('lampiran_links', []))) }} )">
                                                <label class="form-label fw-bold small text-danger mb-2"><i class="bi bi-link-45deg me-1"></i>Atau Tautkan Link</label>
                                                <template x-for="(link, index) in links" :key="index">
                                                    <div class="input-group mb-2 input-group-sm">
                                                        <input type="text" class="form-control" :name="`lampiran_links[${index}][nama]`" placeholder="Nama Tautan" x-model="link.nama" required>
                                                        <input type="url" class="form-control" :name="`lampiran_links[${index}][url]`" placeholder="https://" x-model="link.url" required>
                                                        <button type="button" class="btn btn-outline-danger" @click="removeLink(index)"><i class="bi bi-x"></i></button>
                                                    </div>
                                                </template>
                                                @error('lampiran_links.*') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                                                <button type="button" class="btn btn-xs btn-outline-danger dashed-border w-100 mt-1" @click="addLink()"><i class="bi bi-plus-circle me-1"></i> Tambah Link</button>
                                            </div>
                                        </div>
                                    </div>
                                </div> 
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pb-5">
                <button type="button" onclick="window.location.reload()" class="btn btn-light border px-4 py-2">Reset</button>
                <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</main>
@endsection

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @vite(['resources/css/filepond.css', 'resources/js/filepond.js'])
    <style>
        .form-control, .form-select { border-color: #ced4da; border-radius: 0.375rem; font-size: 0.9rem; }
        .form-control:focus, .form-select:focus { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25); }
        .ts-control { border-radius: 0.375rem; padding: 0.3rem 0.75rem; font-size: 0.9rem; }
        .ts-wrapper.focus .ts-control { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25); }
        .border-dashed { border: 1px dashed #ced4da !important; }
        .dashed-border { border-style: dashed !important; border-width: 2px !important; }
        textarea.auto-resize { resize: none; overflow-y: hidden; }
        .fs-10px { font-size: 10px; }
        .bg-danger-soft { background-color: #ffeaea; }
        .filepond--panel-root { background-color: #f8f9fa; border: 1px solid #ced4da; }
        .border-danger-subtle-thick { border-color: #dc3545 !important; border-width: 2px !important; }
        .delete-overlay { display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .transition-all { transition: all 0.3s ease; }
    </style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const commonConfig = {
            uploadRoute: '{{ route('upload.temp') }}',
            revertRoute: '{{ route('revert.temp') }}',
            loadRoute:   '{{ route('load.temp') }}',
            csrfToken:   '{{ csrf_token() }}',
            submitBtnId: 'btn-submit'
        };

        if (window.FilePondManager) {
            window.FilePondManager.create('#fp-dokumentasi', {
                ...commonConfig,
                maxSize: '10MB',
                existingFiles: @json(old('dokumentasi', [])),
            });
            window.FilePondManager.create('#fp-lampiran', {
                ...commonConfig,
                maxSize: '10MB',
                existingFiles: @json(old('lampiran', [])),
            });
            window.FilePondManager.attachFormSubmit('form-kasus', 'btn-submit');
        }
    });

    document.addEventListener('alpine:init', () => {
        
        Alpine.data('locationPicker', () => ({
            lat: {{ old('latitude', $kasus->latitude) ? old('latitude', $kasus->latitude) : 'null' }},
            lng: {{ old('longitude', $kasus->longitude) ? old('longitude', $kasus->longitude) : 'null' }},
            map: null, 
            marker: null,
            isLoading: false, 
            
            init() { 
                this.lat = (this.lat !== null) ? parseFloat(this.lat) : null;
                this.lng = (this.lng !== null) ? parseFloat(this.lng) : null;

                let center = (this.lat && this.lng) ? [this.lat, this.lng] : [-2.5489, 118.0149];
                let zoom = (this.lat && this.lng) ? 16 : 5;

                this.map = L.map('map').setView(center, zoom);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: 'OSM'
                }).addTo(this.map);

                if(this.lat && this.lng) {
                    this.setPt(this.lat, this.lng, false);
                }

                this.map.on('click', e => {
                    this.setPt(e.latlng.lat, e.latlng.lng);
                });
                
                setTimeout(() => { this.map.invalidateSize(); }, 200);
            },

            setPt(lat, lng, setView = true) {
                this.lat = parseFloat(lat).toFixed(7); 
                this.lng = parseFloat(lng).toFixed(7);
                
                if(this.marker) {
                    this.marker.setLatLng([this.lat, this.lng]); 
                } else {
                    this.marker = L.marker([this.lat, this.lng]).addTo(this.map);
                }
                
                if(setView) {
                    this.map.setView([this.lat, this.lng], 16); 
                }
            },

            updateMarker() {
                if (this.lat && this.lng && !isNaN(this.lat) && !isNaN(this.lng)) {
                    this.setPt(this.lat, this.lng);
                }
            },

            getGPS() { 
                this.isLoading = true;

                if(navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (p) => { 
                            this.setPt(p.coords.latitude, p.coords.longitude); 
                            this.isLoading = false; 
                        },
                        (err) => { 
                            this.isLoading = false; 
                            let msg = 'Gagal mengambil lokasi.';
                            if(err.code === 1) msg = 'Izin lokasi ditolak browser.';
                            if(err.code === 2) msg = 'Sinyal GPS tidak ditemukan.';
                            if(err.code === 3) msg = 'Waktu habis (Timeout). Coba di tempat terbuka.';
                            
                            Swal.fire('GPS Error', msg, 'error');
                        },
                        { 
                            enableHighAccuracy: true, 
                            timeout: 10000, 
                            maximumAge: 0 
                        } 
                    );
                } else {
                    this.isLoading = false;
                    Swal.fire('Error', 'Browser tidak mendukung Geolocation.', 'error');
                }
            }
        }));

        Alpine.data('linkManager', (initialData = []) => ({
            links: Array.isArray(initialData) ? initialData : [], 
            addLink() { this.links.push({ nama: '', url: '' }); },
            removeLink(index) { this.links.splice(index, 1); }
        }));

        Alpine.data('kasusForm', () => ({
            masterSuspects: [], bbGroups: [], errors: @json($errors->toArray()),
            pekerjaanList: @json(\App\Constants\Pekerjaan::ALL), // Daftar pekerjaan
            
            init() {
                const oldTsk = @json(old('tersangka', []));
                const oldGroups = @json(old('bb_groups', []));
                
                if(oldTsk.length > 0) {
                    oldTsk.forEach(t => {
                        const p_val = t.pekerjaan || '';
                        const p_select = this.pekerjaanList.includes(p_val) ? p_val : (p_val ? 'Lainnya' : '');
                        
                        this.masterSuspects.push({ 
                            temp_id: t.temp_id || 't_'+Math.random(), 
                            nama: t.nama || '', jk: t.jk || 'Laki-Laki', 
                            pekerjaan: p_val, pekerjaan_select: p_select, 
                            tahap: t.tahap || '', 
                            old_foto: t.old_foto || '', preview: t.old_foto ? '{{ asset('storage') }}/' + t.old_foto : null 
                        });
                    });
                } else {
                    const dbTersangka = @json($kasus->tersangka);
                    dbTersangka.forEach(t => {
                        const p_val = t.pekerjaan || '';
                        const p_select = this.pekerjaanList.includes(p_val) ? p_val : (p_val ? 'Lainnya' : '');
                        
                        this.masterSuspects.push({ 
                            temp_id: 'db_' + t.id, nama: t.nama_tersangka, jk: t.jenis_kelamin, 
                            pekerjaan: p_val, pekerjaan_select: p_select, 
                            tahap: t.tahap, 
                            old_foto: t.foto_tersangka, preview: t.foto_tersangka ? '{{ asset('storage') }}/' + t.foto_tersangka : null 
                        });
                    });
                }
                if (this.masterSuspects.length === 0) this.addMaster();

                if(oldGroups.length > 0) {
                      oldGroups.forEach(g => {
                        this.bbGroups.push({
                            id: 'g_'+Math.random(), selectedOwners: g.owners || [],
                            items: (g.items || []).map(i => ({
                                id: Math.random(), kategori: i.kategori || 'Narkotika',
                                narkotika_id: i.narkotika_id ? i.narkotika_id : '', nama_barang_bukti: i.nama_barang_bukti || '',
                                jumlah: i.jumlah || '', satuan: i.satuan || 'Gram'
                            }))
                        });
                    });
                } else {
                      const dbBB = @json($kasus->barangBukti->load('tersangka'));
                      const grouped = {};
                      dbBB.forEach(bb => {
                        const ownerIds = bb.tersangka.map(t => 'db_' + t.id).sort().join('-');
                        if (!grouped[ownerIds]) { grouped[ownerIds] = { id: 'g_' + Math.random(), selectedOwners: bb.tersangka.map(t => 'db_' + t.id), items: [] }; }
                        grouped[ownerIds].items.push({
                            id: Math.random(), kategori: bb.kategori,
                            narkotika_id: bb.narkotika_id ? String(bb.narkotika_id) : '', nama_barang_bukti: bb.nama_barang_non_narkotika || '',
                            jumlah: bb.kuantitas, satuan: bb.kategori === 'Narkotika' ? bb.satuan_narkotika : bb.satuan_non_narkotika
                        });
                      });
                      this.bbGroups = Object.values(grouped);
                }
                if (this.bbGroups.length === 0) this.addBBGroup();
            },
            
            removePhoto(idx) {
                this.masterSuspects[idx].old_foto = ''; 
                this.masterSuspects[idx].preview = null; 
                let fileInput = document.getElementById('file_' + this.masterSuspects[idx].temp_id);
                if(fileInput) fileInput.value = '';
            },

            addMaster() { 
                this.masterSuspects.push({ 
                    temp_id: 't_'+Date.now(), nama: '', jk: 'Laki-Laki', 
                    pekerjaan: '', pekerjaan_select: '', tahap: '', preview: null 
                }); 
            },
            removeMaster(i) { 
                const id = this.masterSuspects[i].temp_id; this.masterSuspects.splice(i, 1); 
                this.bbGroups.forEach(g => g.selectedOwners = g.selectedOwners.filter(x => x !== id));
            },
            handleMasterFoto(e, i) { const f = e.target.files[0]; if(f) this.masterSuspects[i].preview = URL.createObjectURL(f); },

            addBBGroup() { this.bbGroups.push({ id: 'g_'+Date.now(), selectedOwners: [], items: [{ id: Math.random(), kategori: 'Narkotika', jumlah: '', satuan: 'Gram', narkotika_id:'', nama_barang_bukti:'' }] }); },
            removeBBGroup(i) { this.bbGroups.splice(i, 1); },
            addItem(gIdx) { this.bbGroups[gIdx].items.push({ id: Math.random(), kategori: 'Narkotika', jumlah: '', satuan: 'Gram', narkotika_id:'', nama_barang_bukti:'' }); },
            removeItem(gIdx, iIdx) { this.bbGroups[gIdx].items.splice(iIdx, 1); },
            resetSatuan(item) { item.satuan = (item.kategori === 'Narkotika') ? 'Gram' : ''; },
            
            hasNonNarkotika(gIdx) {
                return this.bbGroups[gIdx].items.some(item => item.kategori === 'Non-Narkotika');
            },
            
            // --- FIX ERROR ALPINE ARRAY ---
            hasError(field, idx, key) { 
                return this.errors && this.errors[`${field}.${idx}.${key}`] !== undefined; 
            },
            getErrorMessage(field, idx, key) { 
                const err = this.errors[`${field}.${idx}.${key}`];
                return err ? err[0] : ''; 
            },
            hasErrorNested(field, gIdx, subField, iIdx, key) { 
                return this.errors && this.errors[`${field}.${gIdx}.${subField}.${iIdx}.${key}`] !== undefined; 
            },
            getErrorMessageNested(field, gIdx, subField, iIdx, key) { 
                const err = this.errors[`${field}.${gIdx}.${subField}.${iIdx}.${key}`];
                return err ? err[0] : ''; 
            },

            initNarkotikaSelect(el, initialValue) {
                if(el._tomselect) return;
                let ts = new TomSelect(el, { 
                    plugins: ['dropdown_input'], 
                    create: false, 
                    placeholder: 'Pilih jenis narkotika...', 
                    allowEmptyOption: true,
                    dropdownParent: 'body', 
                    maxItems: 1 
                });
                if(initialValue) ts.setValue(initialValue);
            }
        }));
        
        Alpine.data('linkManager', (initialData = []) => ({
            links: Array.isArray(initialData) ? initialData : [], 
            addLink() { this.links.push({ nama: '', url: '' }); },
            removeLink(index) { this.links.splice(index, 1); }
        }));
    });

    window.markForDeletion = function(id) {
        const cardInner = document.querySelector('#file-card-' + id + ' .file-card-inner');
        const overlay = cardInner.querySelector('.delete-overlay');
        const btnDelete = document.getElementById('btn-delete-' + id);
        const containerInputs = document.getElementById('delete-inputs-container');
        let input = document.getElementById('input-delete-' + id);
        
        if (input) { 
            input.remove();
            overlay.classList.add('d-none');
            overlay.classList.remove('d-flex');
            cardInner.classList.remove('border-danger-thick');
            btnDelete.classList.remove('btn-secondary');
            btnDelete.classList.add('btn-outline-danger');
            btnDelete.innerHTML = 'Hapus';
        } else { 
            input = document.createElement('input');
            input.type = 'hidden'; input.name = 'delete_files[]'; input.value = id; input.id = 'input-delete-' + id;
            containerInputs.appendChild(input);
            overlay.classList.remove('d-none');
            overlay.classList.add('d-flex');
            cardInner.classList.add('border-danger-thick');
            btnDelete.classList.remove('btn-outline-danger');
            btnDelete.classList.add('btn-secondary');
            btnDelete.innerHTML = 'Batal';
        }
    };
</script>
@endpush