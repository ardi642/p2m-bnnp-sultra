@extends('admin')

@section('content')
<main class="admin-main" x-data="kasusForm">
    <div class="container-fluid p-4 p-lg-5">
        
        {{-- HEADER TITLE --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Input Ungkap Kasus</h1>
                <p class="text-muted mb-0">Data Penindakan dan Ungkap Kasus Narkoba</p>
            </div>
            <a href="{{ route('berantas.ungkap-kasus.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        {{-- ALERT ERROR KHUSUS ORPHAN SUSPECT --}}
        @error('tersangka_orphan')
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-octagon-fill fs-4 me-3"></i>
                    <div>
                        <strong>Data Tidak Konsisten!</strong><br>
                        {{ $message }}
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @enderror

        <div class="row justify-content-center mt-4">
            <div class="col-12 col-lg-12">
                <div class="card border-0 shadow-sm">
                    
                    {{-- CARD HEADER --}}
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-file-earmark-plus me-2"></i>Form Input Data
                        </h5>
                    </div>

                    <div class="card-body p-4 p-lg-5">
                        <form action="{{ route('berantas.ungkap-kasus.store') }}" method="POST" enctype="multipart/form-data" @submit.prevent="submitData">
                            @csrf

                            {{-- SECTION 1: DATA LKN --}}
                            <h6 class="text-uppercase text-secondary fw-bold small mb-4 border-bottom pb-2">
                                <i class="bi bi-info-circle me-1"></i> Data LKN
                            </h6>

                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-secondary small">Nomor LKN</label>
                                    <input type="text" name="nomor_lkn" class="form-control @error('nomor_lkn') is-invalid @enderror" placeholder="Contoh: LKN/01/I/2025/BNN" value="{{ old('nomor_lkn') }}">
                                    @error('nomor_lkn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-secondary small">Tanggal Kejadian</label>
                                    <input type="date" name="tanggal_kejadian" class="form-control @error('tanggal_kejadian') is-invalid @enderror" value="{{ old('tanggal_kejadian') }}">
                                    @error('tanggal_kejadian') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-secondary small">Lokasi / TKP</label>
                                    <textarea name="alamat_tkp" class="form-control @error('alamat_tkp') is-invalid @enderror" rows="2" placeholder="Masukkan alamat lengkap TKP">{{ old('alamat_tkp') }}</textarea>
                                    @error('alamat_tkp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            {{-- SECTION 2: TERSANGKA --}}
                            <div class="d-flex justify-content-between align-items-end mb-3 border-bottom pb-2">
                                <h6 class="text-uppercase text-secondary fw-bold small m-0">
                                    <i class="bi bi-people me-1"></i> Daftar Tersangka
                                </h6>
                                <button type="button" class="btn btn-primary btn-sm shadow-sm" @click="addTersangka">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Tersangka
                                </button>
                            </div>
                            
                            <div class="table-responsive mb-5" style="overflow-x: visible;">
                                <table class="table table-bordered align-middle">
                                    <thead class="bg-light text-secondary small text-uppercase">
                                        <tr>
                                            <th width="80" class="text-center">Foto</th>
                                            <th>Data Tersangka</th>
                                            <th width="50" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="border-top-0">
                                        <template x-for="(t, index) in tersangkaList" :key="t.temp_id">
                                            <tr>
                                                <input type="hidden" :name="`tersangka[${index}][temp_id]`" :value="t.temp_id">
                                                
                                                {{-- FOTO --}}
                                                <td class="text-center bg-white">
                                                    <div class="position-relative d-inline-block" 
                                                         @click="document.getElementById('file_'+t.temp_id).click()" 
                                                         style="cursor: pointer;" 
                                                         title="Klik untuk ganti foto">
                                                        <img :src="t.preview_url || '{{ asset('assets/images/user-placeholder.png') }}'" 
                                                             class="rounded-circle border object-fit-cover shadow-sm" 
                                                             width="60" height="60">
                                                        <div class="position-absolute bottom-0 end-0 bg-white rounded-circle border p-1" style="width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;">
                                                            <i class="bi bi-camera-fill text-secondary" style="font-size: 10px;"></i>
                                                        </div>
                                                    </div>
                                                    <input type="file" 
                                                           :name="`tersangka[${index}][foto]`" 
                                                           class="d-none" 
                                                           :id="'file_'+t.temp_id" 
                                                           accept="image/*" 
                                                           @change="handleFoto($event, index)">
                                                    
                                                    <div class="text-danger small mt-1" 
                                                         x-show="hasError('tersangka', index, 'foto')" 
                                                         x-text="getErrorMessage('tersangka', index, 'foto')"></div>
                                                </td>

                                                {{-- INPUT DATA --}}
                                                <td class="bg-white">
                                                    <div class="row g-2">
                                                        <div class="col-md-6">
                                                            <label class="small text-muted">Nama Lengkap <span class="text-danger">*</span></label>
                                                            <input type="text" :name="`tersangka[${index}][nama]`" x-model="t.nama" 
                                                                   @input.debounce.300ms="updateAllTomSelects()" 
                                                                   class="form-control form-control-sm" 
                                                                   :class="{'is-invalid': hasError('tersangka', index, 'nama')}"
                                                                   placeholder="Nama Tersangka">
                                                            <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'nama')"></div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="small text-muted">Jenis Kelamin <span class="text-danger">*</span></label>
                                                            <select :name="`tersangka[${index}][jk]`" x-model="t.jk" 
                                                                    class="form-select form-select-sm"
                                                                    :class="{'is-invalid': hasError('tersangka', index, 'jk')}">
                                                                <option value="Laki-Laki">Laki-Laki</option>
                                                                <option value="Perempuan">Perempuan</option>
                                                            </select>
                                                            <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'jk')"></div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="small text-muted">Pekerjaan <span class="text-danger">*</span></label>
                                                            <input type="text" :name="`tersangka[${index}][pekerjaan]`" x-model="t.pekerjaan" 
                                                                   class="form-control form-control-sm"
                                                                   :class="{'is-invalid': hasError('tersangka', index, 'pekerjaan')}"
                                                                   placeholder="Pekerjaan">
                                                            <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'pekerjaan')"></div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="small text-muted">Status / Tahap <span class="text-danger">*</span></label>
                                                            <input type="text" :name="`tersangka[${index}][tahap]`" x-model="t.tahap" 
                                                                   class="form-control form-control-sm" 
                                                                   :class="{'is-invalid': hasError('tersangka', index, 'tahap')}"
                                                                   placeholder="Cth: Sidik / Lidik">
                                                            <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'tahap')"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center bg-white">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" @click="removeTersangka(index)" title="Hapus Baris">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                                @error('tersangka') <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i> {{ $message }}</div> @enderror
                            </div>

                            {{-- SECTION 3: BARANG BUKTI --}}
                            <div class="d-flex justify-content-between align-items-end mb-3 border-bottom pb-2">
                                <h6 class="text-uppercase text-secondary fw-bold small m-0">
                                    <i class="bi bi-box-seam me-1"></i> Daftar Barang Bukti
                                </h6>
                                <button type="button" class="btn btn-primary btn-sm shadow-sm" @click="addBB">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Barang Bukti
                                </button>
                            </div>

                            <div class="table-responsive mb-5" style="overflow-x: visible;">
                                <table class="table table-bordered align-middle">
                                    <thead class="bg-light text-secondary small text-uppercase">
                                        <tr>
                                            <th width="25%">Pemilik</th>
                                            <th width="15%">Kategori</th>
                                            <th>Barang Bukti</th>
                                            <th width="12%">Jumlah</th>
                                            <th width="12%">Satuan</th>
                                            <th width="50" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="border-top-0">
                                        <template x-for="(bb, i) in bbList" :key="bb.temp_id">
                                            <tr>
                                                <td class="bg-white">
                                                    <div wire:ignore :class="{'border border-danger rounded': hasError('barang_bukti', i, 'pemilik_id')}">
                                                        <select :name="`barang_bukti[${i}][pemilik_id][]`" multiple placeholder="Pilih Pemilik..." autocomplete="off" x-init="initTomSelectOwner($el, bb)"></select>
                                                    </div>
                                                    <div class="text-danger small mt-1" x-show="hasError('barang_bukti', i, 'pemilik_id')" x-text="getErrorMessage('barang_bukti', i, 'pemilik_id')"></div>
                                                </td>

                                                {{-- KATEGORI --}}
                                                <td class="bg-white">
                                                    <select :name="`barang_bukti[${i}][kategori]`" x-model="bb.kategori" 
                                                            class="form-select form-select-sm"
                                                            :class="{'is-invalid': hasError('barang_bukti', i, 'kategori')}">
                                                        <option value="Narkotika">Narkotika</option>
                                                        <option value="Non-Narkotika">Non-Narkotika</option>
                                                    </select>
                                                </td>

                                                {{-- DINAMIS: NARKOTIKA VS NON-NARKOTIKA --}}
                                                <td class="bg-white">
                                                    
                                                    {{-- JIKA NARKOTIKA: TOMSELECT MASTER --}}
                                                    <div x-show="bb.kategori === 'Narkotika'" class="w-100">
                                                        <div wire:ignore :class="{'border border-danger rounded': hasError('barang_bukti', i, 'narkotika_id')}">
                                                            <select :name="`barang_bukti[${i}][narkotika_id]`" 
                                                                    placeholder="Cari Narkotika..." 
                                                                    autocomplete="off" 
                                                                    x-init="initTomSelectNarkotika($el, bb)">
                                                            </select>
                                                        </div>
                                                        <div class="text-danger small mt-1" x-show="hasError('barang_bukti', i, 'narkotika_id')" x-text="getErrorMessage('barang_bukti', i, 'narkotika_id')"></div>
                                                    </div>

                                                    {{-- JIKA NON-NARKOTIKA: TEXT INPUT --}}
                                                    <div x-show="bb.kategori === 'Non-Narkotika'" class="w-100">
                                                        <input type="text" :name="`barang_bukti[${i}][nama_barang_bukti]`" x-model="bb.nama_barang_bukti" 
                                                               class="form-control form-control-sm" 
                                                               :class="{'is-invalid': hasError('barang_bukti', i, 'nama_barang_bukti')}"
                                                               placeholder="Nama Barang Bukti (HP, Uang, dll)">
                                                        <div class="invalid-feedback" x-text="getErrorMessage('barang_bukti', i, 'nama_barang_bukti')"></div>
                                                    </div>
                                                </td>

                                                <td class="bg-white">
                                                    <input type="number" step="0.0001" :name="`barang_bukti[${i}][jumlah]`" x-model="bb.jumlah" 
                                                           class="form-control form-control-sm" 
                                                           :class="{'is-invalid': hasError('barang_bukti', i, 'jumlah')}"
                                                           placeholder="0">
                                                    <div class="invalid-feedback" x-text="getErrorMessage('barang_bukti', i, 'jumlah')"></div>
                                                </td>
                                                <td class="bg-white">
                                                    <select :name="`barang_bukti[${i}][satuan]`" x-model="bb.satuan" 
                                                            class="form-select form-select-sm" 
                                                            :class="{'is-invalid': hasError('barang_bukti', i, 'satuan')}">
                                                        <option value="Gram">Gram</option>
                                                        <option value="Kg">Kg</option>
                                                        <option value="Ton">Ton</option>
                                                    </select>
                                                    <div class="invalid-feedback" x-text="getErrorMessage('barang_bukti', i, 'satuan')"></div>
                                                </td>
                                                <td class="text-center bg-white">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" @click="removeBB(i)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                                @error('barang_bukti') <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i> {{ $message }}</div> @enderror
                            </div>

                            {{-- SECTION 4: LAMPIRAN --}}
                            <h6 class="text-uppercase text-secondary fw-bold small mb-4 border-bottom pb-2">
                                <i class="bi bi-paperclip me-1"></i> Lampiran
                            </h6>
                            
                            <div class="bg-body-tertiary p-4 rounded-3 border border-dashed mb-4">
                                <label class="form-label fw-bold h6 mb-1 text-dark">
                                    <i class="bi bi-cloud-arrow-up me-2"></i>Upload Dokumentasi
                                </label>
                                <p class="text-muted small mb-3">
                                    Format: .jpg, .png, .pdf, .docx. Maks 10MB/file.
                                </p>
                                
                                <input type="file" 
                                       class="filepond" 
                                       name="dokumentasi[]" 
                                       multiple 
                                       data-allow-reorder="true"
                                       data-max-file-size="10MB"
                                       data-max-files="10">
                                
                                @error('dokumentasi')
                                    <div class="alert alert-danger py-2 mt-2 small border-0 shadow-sm">
                                        <i class="bi bi-exclamation-circle me-1"></i> {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- FOOTER BUTTONS --}}
                            <div class="d-flex flex-column-reverse flex-lg-row justify-content-end gap-2 pt-4 border-top mt-5">
                                <button type="button" onclick="window.location.reload()" class="btn btn-light border text-secondary px-4">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                </button>
                                <button type="submit" id="btn-submit" class="btn btn-primary px-5 shadow-sm" :disabled="isUploading">
                                    <span x-show="isUploading" class="spinner-border spinner-border-sm me-2"></span>
                                    <span x-text="isUploading ? 'Mengupload...' : 'Simpan Data'"></span>
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@push('styles')
    @vite(['resources/css/filepond.css', 'resources/js/filepond.js'])
    <style>
        .ts-control { border: 1px solid #dee2e6; padding: 0.4rem 0.75rem; border-radius: 0.375rem; box-shadow: none; font-size: 0.875rem; }
        .ts-control.focus { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
        .ts-dropdown { z-index: 9999 !important; }
        .filepond--panel-root { background-color: #ffffff; border: 1px solid #dee2e6; }
        .border-dashed { border-style: dashed !important; border-width: 2px !important; }
    </style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener('alpine:init', () => {
        Alpine.data('kasusForm', () => ({
            // STATE
            tersangkaList: [],
            bbList: [],
            isUploading: false,
            tomSelectOwners: {}, 
            tomSelectNarkotika: {},
            errors: @json($errors->toArray()),
            masterNarkotika: @json($masterNarkotika),

            init() {
                const oldTersangka = @json(old('tersangka', []));
                const oldBB = @json(old('barang_bukti', []));

                // Init Tersangka
                if (oldTersangka.length > 0) {
                    oldTersangka.forEach(t => {
                        this.tersangkaList.push({
                            temp_id: t.temp_id || ('t_' + Math.random().toString(36).substr(2, 9)),
                            nama: t.nama || '',
                            jk: t.jk || 'Laki-Laki',
                            pekerjaan: t.pekerjaan || '',
                            tahap: t.tahap || '',
                            preview_url: null 
                        });
                    });
                } else {
                    this.addTersangka(); 
                }

                // Init BB
                if (oldBB.length > 0) {
                    oldBB.forEach(b => {
                        this.bbList.push({
                            temp_id: 'bb_' + Math.random().toString(36).substr(2, 9),
                            kategori: b.kategori || 'Narkotika',
                            narkotika_id: b.narkotika_id || '',
                            nama_barang_bukti: b.nama_barang_bukti || '',
                            jumlah: b.jumlah || '',
                            satuan: b.satuan || 'Gram', 
                            initial_pemilik: b.pemilik_id || [] 
                        });
                    });
                } else {
                    this.addBB(); 
                }

                // Init FilePond (Code sama persis)
                if(window.FilePond) {
                    const el = document.querySelector('input.filepond');
                    FilePond.create(el, {
                        server: {
                            process: { url: '{{ route('upload.temp') }}', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, onerror: () => { this.isUploading = false; } },
                            revert: { url: '{{ route('revert.temp') }}', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } },
                        },
                        onprocessstart: () => { this.isUploading = true },
                        onprocessfiles: () => { this.isUploading = false },
                        onremovefile: () => {
                            const pond = FilePond.find(el);
                            if (pond) {
                                const files = pond.getFiles();
                                if(!files.some(f => f.status === 3 || f.status === 9)) this.isUploading = false;
                            }
                        }
                    });
                }
            },

            hasError(field, index, key) {
                const errorKey = `${field}.${index}.${key}`;
                return this.errors && this.errors[errorKey];
            },

            getErrorMessage(field, index, key) {
                const errorKey = `${field}.${index}.${key}`;
                return this.errors[errorKey] ? this.errors[errorKey][0] : '';
            },

            addTersangka() {
                this.tersangkaList.push({ 
                    temp_id: 't_' + Date.now() + Math.random(), 
                    nama: '', jk: 'Laki-Laki', pekerjaan: '', tahap: '', preview_url: null 
                });
                this.$nextTick(() => { this.updateAllTomSelects(); });
            },

            removeTersangka(index) {
                const suspectId = this.tersangkaList[index].temp_id;
                let isUsed = false;
                Object.values(this.tomSelectOwners).forEach(ts => {
                    if (ts.getValue().includes(suspectId)) isUsed = true;
                });
                if (isUsed) {
                    Swal.fire({icon: 'error', title: 'Gagal Hapus', text: `Tersangka sedang dipilih sebagai pemilik Barang Bukti.`});
                    return;
                }
                if (this.tersangkaList.length === 1) return;
                this.tersangkaList.splice(index, 1);
                this.$nextTick(() => { this.updateAllTomSelects(); });
            },

            handleFoto(e, index) {
                const file = e.target.files[0];
                if(file) this.tersangkaList[index].preview_url = URL.createObjectURL(file);
            },

            addBB() {
                this.bbList.push({ 
                    temp_id: 'bb_' + Date.now() + Math.random(), 
                    kategori: 'Narkotika',
                    narkotika_id: '',
                    nama_barang_bukti: '',
                    jumlah: '', 
                    satuan: 'Gram', 
                    initial_pemilik: [] 
                });
            },

            removeBB(index) {
                if (this.bbList.length === 1) return;
                const bbTempId = this.bbList[index].temp_id;
                if(this.tomSelectOwners[bbTempId]) {
                    this.tomSelectOwners[bbTempId].destroy();
                    delete this.tomSelectOwners[bbTempId];
                }
                if(this.tomSelectNarkotika[bbTempId]) {
                    this.tomSelectNarkotika[bbTempId].destroy();
                    delete this.tomSelectNarkotika[bbTempId];
                }
                this.bbList.splice(index, 1);
            },

            // --- TOM SELECT OWNERS (Pemilik) ---
            initTomSelectOwner(el, bbData) {
                const ts = new TomSelect(el, {
                    plugins: ['remove_button', 'dropdown_input'],
                    valueField: 'value', labelField: 'text', searchField: 'text', create: false, maxOptions: null, placeholder: "Pilih pemilik...", dropdownParent: 'body'
                });
                this.tomSelectOwners[bbData.temp_id] = ts;
                this.refreshOptionsForInstance(ts);
                if (bbData.initial_pemilik && bbData.initial_pemilik.length > 0) {
                    ts.setValue(bbData.initial_pemilik);
                    bbData.pemilik_id = bbData.initial_pemilik; 
                }
                ts.on('change', (val) => { bbData.pemilik_id = val; });
            },

            updateAllTomSelects() {
                Object.values(this.tomSelectOwners).forEach(ts => { this.refreshOptionsForInstance(ts); });
            },

            refreshOptionsForInstance(ts) {
                this.tersangkaList.forEach(t => {
                    const label = t.nama.trim() === '' ? '(Tanpa Nama)' : t.nama;
                    if (ts.options[t.temp_id]) ts.updateOption(t.temp_id, { value: t.temp_id, text: label });
                    else ts.addOption({ value: t.temp_id, text: label });
                });
                const validIds = this.tersangkaList.map(t => t.temp_id);
                Object.keys(ts.options).forEach(optVal => {
                    if (!validIds.includes(optVal)) ts.removeOption(optVal);
                });
                ts.refreshOptions(false); 
            },

            // --- TOM SELECT NARKOTIKA (Master Data) ---
            initTomSelectNarkotika(el, bbData) {
                // Mapping options dari Data Master (Blade -> JS)
                const options = this.masterNarkotika.map(m => ({
                    id: m.id,
                    text: m.nama_narkotika,
                    golongan: m.golongan
                }));

                const ts = new TomSelect(el, {
                    valueField: 'id',
                    labelField: 'text',
                    searchField: ['text', 'golongan'],
                    options: options,
                    create: false,
                    placeholder: 'Cari Narkotika...',
                    dropdownParent: 'body',
                    // Custom Render untuk menampilkan nama + golongan (kecil)
                    render: {
                        option: function(data, escape) {
                            return '<div>' + escape(data.text) + ' <span class="text-muted small ms-2" style="font-size: 0.8em; opacity: 0.7;">' + escape(data.golongan) + '</span></div>';
                        },
                        item: function(data, escape) {
                            return '<div>' + escape(data.text) + '</div>';
                        }
                    }
                });

                this.tomSelectNarkotika[bbData.temp_id] = ts;

                // Set value jika old input ada
                if (bbData.narkotika_id) {
                    ts.setValue(bbData.narkotika_id);
                }

                ts.on('change', (val) => { bbData.narkotika_id = val; });
            },

            submitData(e) {
                if (this.isUploading) return; 
                if (this.tersangkaList.length === 0 || this.bbList.length === 0) {
                     Swal.fire('Data Belum Lengkap', 'Mohon isi minimal 1 Tersangka dan 1 Barang Bukti.', 'warning');
                     return;
                }

                // Validasi Client Side Tambahan
                let valid = true;
                this.bbList.forEach(bb => {
                    if (bb.kategori === 'Narkotika' && !bb.narkotika_id) valid = false;
                    if (bb.kategori === 'Non-Narkotika' && !bb.nama_barang_bukti.trim()) valid = false;
                });

                if(!valid) {
                     Swal.fire('Data Belum Lengkap', 'Mohon lengkapi jenis narkotika atau nama barang bukti.', 'warning');
                     return;
                }

                const selectedOwners = this.bbList.flatMap(bb => bb.pemilik_id || []);
                const orphanSuspects = this.tersangkaList.filter(t => !selectedOwners.includes(t.temp_id));
                if (orphanSuspects.length > 0) {
                     Swal.fire({icon: 'error', title: 'Validasi Gagal', html: 'Ada tersangka belum punya BB.'});
                     return;
                }

                e.target.submit();
            }
        }));
    });
</script>
@endpush