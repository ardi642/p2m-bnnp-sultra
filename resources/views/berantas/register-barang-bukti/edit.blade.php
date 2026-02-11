@extends('admin')

@section('content')
<main class="admin-main" x-data="registerBBForm">
    <div class="container-fluid p-4 p-lg-5">
        
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Edit Register Barang Bukti</h1>
                <p class="text-secondary small mb-0">Update Data Pencatatan Barang Bukti</p>
            </div>
            <a href="{{ route('berantas.register-barang-bukti.index') }}" 
               class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        {{-- ALERT ERROR --}}
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
                    <div>
                        <strong>Periksa Kembali Inputan!</strong><br>
                        <small>File yang sudah diupload tersimpan sementara di server.</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('berantas.register-barang-bukti.update', $register->id) }}" 
              method="POST" 
              enctype="multipart/form-data" 
              id="form-edit" 
              @submit.prevent="submitForm">
            @csrf 
            @method('PUT')
            
            {{-- CARD 1: INFORMASI REGISTER --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-info-circle me-2"></i>Informasi Register
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3">
                        {{-- SATUAN KERJA --}}
                        @if(Auth::user()->isAdmin())
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Satuan Kerja</label>
                            <input type="text" 
                                   class="form-control py-2 bg-light" 
                                   value="{{ $register->satuanKerja->satuan_kerja ?? '-' }}" 
                                   readonly>
                        </div>
                        @endif

                        {{-- TANGGAL --}}
                        <div class="col-md-{{ Auth::user()->isAdmin() ? '6' : '12' }}">
                            <label class="form-label fw-semibold small text-secondary">
                                Tanggal Perolehan <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                   name="tanggal_perolehan" 
                                   value="{{ old('tanggal_perolehan', $register->tanggal_perolehan->format('Y-m-d')) }}" 
                                   class="form-control py-2 @error('tanggal_perolehan') is-invalid @enderror">
                            @error('tanggal_perolehan') 
                                <div class="invalid-feedback">{{ $message }}</div> 
                            @enderror
                        </div>

                        {{-- LOKASI --}}
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Lokasi Perolehan</label>
                            <textarea name="lokasi_perolehan" 
                                      class="form-control py-2" 
                                      rows="2"
                                      placeholder="Masukkan alamat lengkap TKP...">{{ old('lokasi_perolehan', $register->lokasi_perolehan) }}</textarea>
                            @error('lokasi_perolehan') 
                                <div class="text-danger small mt-1">{{ $message }}</div> 
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD 2: TABEL ITEMS --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-box-seam me-2"></i>Daftar Barang Bukti
                    </h5>
                    <button type="button" 
                            class="btn btn-dark btn-sm px-3 shadow-sm" 
                            @click="addItem">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Item
                    </button>
                </div>
                <div class="card-body p-4">
                    @error('items') 
                        <div class="alert alert-danger small py-2 mb-3">
                            <i class="bi bi-exclamation-circle me-1"></i> {{ $message }}
                        </div> 
                    @enderror
                    
                    <div class="table-responsive border rounded">
                        <table class="table table-bordered align-middle mb-0 bg-white">
                            <thead class="bg-light small text-uppercase text-secondary">
                                <tr>
                                    <th width="15%">Sumber</th>
                                    <th width="15%">Kategori</th>
                                    <th>Nama Barang</th>
                                    <th width="15%">Jumlah</th>
                                    <th width="15%">Satuan</th>
                                    <th width="50" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="border-top-0">
                                <template x-for="(item, i) in items" :key="item.temp_id">
                                    <tr>
                                        {{-- SUMBER --}}
                                        <td class="align-top bg-white">
                                            <select :name="`items[${i}][sumber_perolehan]`" 
                                                    x-model="item.sumber_perolehan" 
                                                    class="form-select form-select-sm py-2">
                                                <option value="Hasil Tangkap">Hasil Tangkap</option>
                                                <option value="Temuan">Temuan</option>
                                            </select>
                                        </td>
                                        
                                        {{-- KATEGORI --}}
                                        <td class="align-top bg-white">
                                            <select :name="`items[${i}][kategori]`" 
                                                    x-model="item.kategori" 
                                                    class="form-select form-select-sm py-2" 
                                                    @change="resetItem(item)">
                                                <option value="Narkotika">Narkotika</option>
                                                <option value="Non-Narkotika">Non-Narkotika</option>
                                            </select>
                                        </td>
                                        
                                        {{-- NAMA BARANG --}}
                                        <td class="align-top bg-white">
                                            <div x-show="item.kategori === 'Narkotika'" class="w-100">
                                                <div wire:ignore 
                                                     :class="{'border border-danger rounded': hasError('items', i, 'narkotika_id')}">
                                                    <select :id="'select_narkotika_' + item.temp_id" 
                                                            :name="`items[${i}][narkotika_id]`" 
                                                            x-init="initTS($el, item)">
                                                    </select>
                                                </div>
                                                <div class="text-danger small mt-1" 
                                                     x-show="hasError('items', i, 'narkotika_id')" 
                                                     x-text="getErrorMessage('items', i, 'narkotika_id')">
                                                </div>
                                            </div>
                                            <div x-show="item.kategori === 'Non-Narkotika'" class="w-100">
                                                <input type="text" 
                                                       :name="`items[${i}][nama_barang_non_narkotika]`" 
                                                       x-model="item.nama_barang_non_narkotika" 
                                                       class="form-control form-control-sm py-2" 
                                                       :class="{'is-invalid': hasError('items', i, 'nama_barang_non_narkotika')}"
                                                       placeholder="Contoh: Handphone...">
                                                <div class="invalid-feedback" 
                                                     x-text="getErrorMessage('items', i, 'nama_barang_non_narkotika')">
                                                </div>
                                            </div>
                                        </td>
                                        
                                        {{-- JUMLAH --}}
                                        <td class="align-top bg-white">
                                            <input type="number" 
                                                   step="0.0001" 
                                                   :name="`items[${i}][jumlah]`" 
                                                   x-model="item.jumlah" 
                                                   class="form-control form-control-sm py-2" 
                                                   :class="{'is-invalid': hasError('items', i, 'jumlah')}" 
                                                   placeholder="0">
                                            <div class="invalid-feedback" 
                                                 x-text="getErrorMessage('items', i, 'jumlah')">
                                            </div>
                                        </td>
                                        
                                        {{-- SATUAN --}}
                                        <td class="align-top bg-white">
                                            <template x-if="item.kategori === 'Narkotika'">
                                                <div>
                                                    <select :name="`items[${i}][satuan_narkotika]`" 
                                                            x-model="item.satuan_narkotika" 
                                                            class="form-select form-select-sm py-2" 
                                                            :class="{'is-invalid': hasError('items', i, 'satuan_narkotika')}">
                                                        <option value="Gram">Gram</option>
                                                        <option value="Kg">Kg</option>
                                                        <option value="Ton">Ton</option>
                                                    </select>
                                                    <div class="invalid-feedback" 
                                                         x-text="getErrorMessage('items', i, 'satuan_narkotika')">
                                                    </div>
                                                </div>
                                            </template>
                                            <template x-if="item.kategori === 'Non-Narkotika'">
                                                <div>
                                                    <input type="text" 
                                                           :name="`items[${i}][satuan_non_narkotika]`" 
                                                           x-model="item.satuan_non_narkotika" 
                                                           class="form-control form-control-sm py-2" 
                                                           :class="{'is-invalid': hasError('items', i, 'satuan_non_narkotika')}" 
                                                           placeholder="Pcs/Unit/Buah">
                                                    <div class="invalid-feedback" 
                                                         x-text="getErrorMessage('items', i, 'satuan_non_narkotika')">
                                                    </div>
                                                </div>
                                            </template>
                                        </td>
                                        
                                        <td class="text-center align-top bg-white">
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-sm" 
                                                    @click="removeItem(i)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- CARD 3: LAMPIRAN --}}
            <div class="card shadow-sm border-0 mb-5">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-paperclip me-2"></i>Lampiran
                    </h5>
                </div>
                <div class="card-body p-4">
                    
                    {{-- LIST FILE TERSIMPAN --}}
                    @if($register->dokumentasi->count() > 0)
                        <div class="mb-4">
                            <h6 class="fw-bold text-secondary small mb-3 text-uppercase">File Tersimpan</h6>
                            <div class="row g-3" id="existing-files-container">
                                @foreach($register->dokumentasi as $doc)
                                    @php 
                                        $isMarkedDeleted = old('delete_files') && in_array($doc->id, old('delete_files')); 
                                        $fileUrl = Storage::url($doc->path_file);
                                    @endphp
                                    <div class="col-6 col-md-4 col-lg-3 file-item" id="file-card-{{ $doc->id }}">
                                        <div class="card h-100 shadow-sm border border-secondary-subtle position-relative overflow-hidden file-card-inner {{ $isMarkedDeleted ? 'border-danger-thick' : '' }}" 
                                             style="transition: all 0.3s ease;">
                                            
                                            {{-- OVERLAY DELETE --}}
                                            <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" 
                                                 style="background-color: rgba(255, 255, 255, 0.85); z-index: 20;">
                                                <div class="text-danger mb-2"><i class="bi bi-trash3-fill display-4"></i></div>
                                                <span class="text-danger fw-bold small text-uppercase px-2 py-1 border border-danger rounded">
                                                    AKAN DIHAPUS
                                                </span>
                                            </div>
                                            
                                            {{-- PREVIEW IMAGE / ICON --}}
                                            <div class="ratio ratio-16x9 bg-secondary bg-opacity-10 border-bottom d-flex align-items-center justify-content-center overflow-hidden">
                                                @if(Str::contains($doc->tipe_file, 'image'))
                                                    <img src="{{ $fileUrl }}" 
                                                         class="object-fit-cover w-100 h-100" 
                                                         alt="File Image" 
                                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                                    {{-- Fallback Icon --}}
                                                    <div style="display:none;" class="text-secondary">
                                                        <i class="bi bi-file-image display-4"></i>
                                                    </div>
                                                @elseif(Str::contains($doc->tipe_file, 'pdf')) 
                                                    <div class="text-danger"><i class="bi bi-file-earmark-pdf-fill display-4"></i></div>
                                                @elseif(Str::contains($doc->tipe_file, ['word', 'officedocument'])) 
                                                    <div class="text-primary"><i class="bi bi-file-earmark-word-fill display-4"></i></div>
                                                @else 
                                                    <div class="text-secondary"><i class="bi bi-file-earmark-text-fill display-4"></i></div> 
                                                @endif
                                            </div>

                                            {{-- INFO & TOMBOL --}}
                                            <div class="card-body p-2 text-center d-flex flex-column justify-content-between position-relative" style="z-index: 50;"> 
                                                <div class="mb-2">
                                                    <div class="small text-truncate fw-bold text-dark" title="{{ $doc->nama_file_asli }}">
                                                        {{ $doc->nama_file_asli }}
                                                    </div>
                                                    <div class="text-muted" style="font-size: 0.7rem;">
                                                        {{ $doc->ukuran_file >= 1048576 ? number_format($doc->ukuran_file / 1048576, 2) . ' MB' : number_format($doc->ukuran_file / 1024, 0) . ' KB' }}
                                                    </div>
                                                </div>
                                                <div class="d-flex gap-1">
                                                                     <a href="{{ route('dokumen.download', $doc->id) }}" 
                                                                         class="btn btn-outline-secondary btn-sm px-3" 
                                                                         title="Download">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                    <button type="button" 
                                                            id="btn-delete-{{ $doc->id }}" 
                                                            class="btn btn-sm flex-grow-1 py-0 {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" 
                                                            onclick="markForDeletion({{ $doc->id }})" 
                                                            style="font-size: 0.75rem;">
                                                        @if($isMarkedDeleted) Batal @else Hapus @endif
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <div id="delete-inputs-container">
                                @if(old('delete_files'))
                                    @foreach(old('delete_files') as $deletedId) 
                                        <input type="hidden" name="delete_files[]" value="{{ $deletedId }}" id="input-delete-{{ $deletedId }}"> 
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- UPLOAD BARU --}}
                    <div class="bg-body-tertiary p-4 rounded-3 border border-dashed">
                        <label class="form-label fw-bold h6 mb-1 text-dark">
                            <i class="bi bi-cloud-arrow-up me-2"></i>Upload File Baru
                        </label>
                        <p class="text-muted small mb-3">Format: .jpg, .png, .pdf, .docx. Maks 10MB/file.</p>
                        
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
                </div>
            </div>

            {{-- SUBMIT BUTTONS --}}
            <div class="d-flex justify-content-end gap-2 pt-4 border-top mt-5 mb-5">
                <button type="button" 
                        onclick="window.location.reload()" 
                        class="btn btn-light border text-secondary px-4">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </button>
                <button type="submit" 
                        id="btn-submit" 
                        class="btn btn-primary px-5 shadow-sm" 
                        :disabled="isUploading">
                    <span x-show="isUploading" class="spinner-border spinner-border-sm me-2"></span>
                    <span x-text="isUploading ? 'Mengupload...' : 'Simpan Perubahan'"></span>
                </button>
            </div>
        </form>
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
        .border-danger-thick { border-color: #dc3545 !important; border-width: 2px !important; }
        .delete-overlay { display: flex; flex-direction: column; justify-content: center; align-items: center; }
    </style>
@endpush

@push('scripts')
{{-- SCRIPT HAPUS FILE (MANUAL) --}}
<script>
    window.markForDeletion = function(id) {
        const cardInner = document.querySelector('#file-card-' + id + ' .file-card-inner');
        const overlay = cardInner.querySelector('.delete-overlay');
        const btnDelete = document.getElementById('btn-delete-' + id);
        const containerInputs = document.getElementById('delete-inputs-container');
        let input = document.getElementById('input-delete-' + id);
        
        if (input) {
            // BATAL
            input.remove(); 
            overlay.classList.add('d-none'); overlay.classList.remove('d-flex');
            cardInner.classList.remove('border-danger-thick');
            btnDelete.classList.remove('btn-secondary'); btnDelete.classList.add('btn-outline-danger'); 
            btnDelete.innerHTML = 'Hapus';
        } else {
            // HAPUS
            input = document.createElement('input'); 
            input.type = 'hidden'; input.name = 'delete_files[]'; input.value = id; input.id = 'input-delete-' + id;
            containerInputs.appendChild(input); 
            overlay.classList.remove('d-none'); overlay.classList.add('d-flex');
            cardInner.classList.add('border-danger-thick');
            btnDelete.classList.remove('btn-outline-danger'); btnDelete.classList.add('btn-secondary'); 
            btnDelete.innerHTML = 'Batal';
        }
    };
</script>

<script type="module">
    document.addEventListener('alpine:init', () => {
        Alpine.data('registerBBForm', () => ({
            items: [], tsInstances: {}, isUploading: false, pond: null,
            errors: @json($errors->toArray()),
            masterNarkotika: @json($masterNarkotika),

            init() {
                const dbItems = @json($register->items);
                const oldItems = @json(old('items', []));

                // 1. Load Data
                if (oldItems.length > 0) {
                    oldItems.forEach(i => {
                        this.items.push({
                            temp_id: 'i_' + Math.random(),
                            sumber_perolehan: i.sumber_perolehan, 
                            kategori: i.kategori,
                            narkotika_id: i.narkotika_id,
                            nama_barang_non_narkotika: i.nama_barang_non_narkotika,
                            jumlah: i.jumlah,
                            satuan_narkotika: i.satuan_narkotika,
                            satuan_non_narkotika: i.satuan_non_narkotika
                        });
                    });
                } else if (dbItems.length > 0) {
                    dbItems.forEach(i => {
                        this.items.push({
                            temp_id: 'i_' + i.id,
                            sumber_perolehan: i.sumber_perolehan,
                            kategori: i.kategori,
                            narkotika_id: i.narkotika_id,
                            nama_barang_non_narkotika: i.nama_barang_non_narkotika,
                            jumlah: parseFloat(i.kuantitas),
                            satuan_narkotika: i.satuan_narkotika || 'Gram',
                            satuan_non_narkotika: i.satuan_non_narkotika || ''
                        });
                    });
                } else { 
                    this.addItem(); 
                }

                this.initFilePond();
                this.$nextTick(() => { 
                    this.items.forEach(item => { 
                        if(item.kategori === 'Narkotika') 
                            this.initTS(document.getElementById('select_narkotika_'+item.temp_id), item); 
                    }); 
                });
            },

            addItem() { 
                this.items.push({ 
                    temp_id: 'i_' + Date.now(),
                    sumber_perolehan: 'Hasil Tangkap', 
                    kategori: 'Narkotika', 
                    narkotika_id: '', 
                    nama_barang_non_narkotika: '', 
                    jumlah: '', 
                    satuan_narkotika: 'Gram', 
                    satuan_non_narkotika: '' 
                }); 
            },
            
            removeItem(i) { 
                if(this.items.length > 1) { 
                    const id = this.items[i].temp_id; 
                    if(this.tsInstances[id]) { 
                        this.tsInstances[id].destroy(); 
                        delete this.tsInstances[id]; 
                    } 
                    this.items.splice(i, 1); 
                } else { 
                    Swal.fire('Info', 'Minimal harus ada satu barang bukti.', 'info'); 
                }
            },
            
            resetItem(item) {
                item.narkotika_id = ''; item.nama_barang_non_narkotika = ''; 
                item.satuan_narkotika = 'Gram'; item.satuan_non_narkotika = '';
                
                if(this.tsInstances[item.temp_id]) this.tsInstances[item.temp_id].clear();
                
                this.$nextTick(() => { 
                    if(item.kategori === 'Narkotika') 
                        this.initTS(document.getElementById('select_narkotika_'+item.temp_id), item); 
                });
            },

            initTS(el, item) {
                if(!el) return;
                const ts = new TomSelect(el, { 
                    plugins: ['remove_button'], create: false, valueField: 'id', 
                    labelField: 'text', searchField: 'text', 
                    options: this.masterNarkotika.map(n => ({id: n.id, text: n.nama_narkotika})), 
                    placeholder: 'Pilih...', dropdownParent: 'body' 
                });
                if(item.narkotika_id) ts.setValue(item.narkotika_id);
                ts.on('change', (val) => { item.narkotika_id = val; });
                this.tsInstances[item.temp_id] = ts;
            },
            
            initFilePond() { 
                const inputEl = document.querySelector('.filepond');
                const btn = document.getElementById('btn-submit');
                this.pond = FilePond.create(inputEl, {
                    server: { 
                        process: { url: '{{ route("upload.temp") }}', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}, onerror: () => this.isUploading = false },
                        revert: { url: '{{ route("revert.temp") }}', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'} },
                        load: { url: '{{ route("load.temp") }}/?file=', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'} } 
                    },
                    files: [ 
                        @if(old('dokumentasi')) 
                            @foreach(old('dokumentasi') as $file) 
                                { source: '{{ $file }}', options: { type: 'local' } }, 
                            @endforeach 
                        @endif 
                    ],
                    onprocessstart: () => { this.isUploading = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengupload...'; },
                    onprocessfiles: () => { this.isUploading = false; btn.innerHTML = 'Simpan Perubahan'; },
                    onremovefile: () => { 
                        if (this.pond) { 
                            const files = this.pond.getFiles(); 
                            if(!files.some(f => f.status === 3 || f.status === 9)) { 
                                this.isUploading = false; 
                                btn.innerHTML = 'Simpan Perubahan'; 
                            } 
                        } 
                    }
                });
            },

            hasError(field, index, key) { const k = `${field}.${index}.${key}`; return this.errors && this.errors[k]; },
            getErrorMessage(field, index, key) { const k = `${field}.${index}.${key}`; return this.errors[k] ? this.errors[k][0] : ''; },

            submitForm(e) {
                if(this.pond) { 
                    const files = this.pond.getFiles(); 
                    if(files.some(f => f.status !== 2 && f.status !== 5)) { 
                        Swal.fire({
                            icon: 'warning', 
                            title: 'Upload Belum Selesai', 
                            text: 'Silakan tunggu proses upload file selesai atau hapus file yang macet.', 
                            showConfirmButton: true
                        }); 
                        return; 
                    } 
                }
                
                if (this.isUploading) { 
                    Swal.fire({
                        icon: 'warning', 
                        title: 'Upload Belum Selesai', 
                        text: 'Silakan tunggu proses upload file selesai atau hapus file yang macet.',
                        showConfirmButton: true
                    }); 
                    return; 
                }
                e.target.submit();
            }
        }));
    });
</script>
@endpush