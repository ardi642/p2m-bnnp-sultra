@extends('admin')

@section('content')
<main class="admin-main" x-data="kasusForm">
    <div class="container-fluid p-4 p-lg-5">
        
        {{-- HEADER TITLE --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Edit Ungkap Kasus</h1>
                <p class="text-muted mb-0">Update Data Penindakan dan Ungkap Kasus</p>
            </div>
            <a href="{{ route('berantas.ungkap-kasus.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        <div class="row justify-content-center mt-4">
            <div class="col-12 col-lg-12">
                <div class="card border-0 shadow-sm">
                    
                    {{-- CARD HEADER --}}
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-pencil-square me-2"></i>Form Edit Data
                        </h5>
                    </div>

                    <div class="card-body p-4 p-lg-5">
                        <form action="{{ route('berantas.ungkap-kasus.update', $kasus->id) }}" method="POST" enctype="multipart/form-data" @submit.prevent="submitData">
                            @csrf
                            @method('PUT')

                            {{-- SECTION 1: DATA LKN --}}
                            <h6 class="text-uppercase text-secondary fw-bold small mb-4 border-bottom pb-2">
                                <i class="bi bi-info-circle me-1"></i> Data LKN
                            </h6>

                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-secondary small">Nomor LKN</label>
                                    <input type="text" name="nomor_lkn" class="form-control @error('nomor_lkn') is-invalid @enderror" value="{{ old('nomor_lkn', $kasus->nomor_lkn) }}">
                                    @error('nomor_lkn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-secondary small">Tanggal Kejadian</label>
                                    <input type="date" name="tanggal_kejadian" class="form-control @error('tanggal_kejadian') is-invalid @enderror" value="{{ old('tanggal_kejadian', $kasus->tanggal_kejadian->format('Y-m-d')) }}">
                                    @error('tanggal_kejadian') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-secondary small">Lokasi / TKP</label>
                                    <textarea name="alamat_tkp" class="form-control @error('alamat_tkp') is-invalid @enderror" rows="2">{{ old('alamat_tkp', $kasus->alamat_tkp) }}</textarea>
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
                                                {{-- Hidden Inputs --}}
                                                <input type="hidden" :name="`tersangka[${index}][temp_id]`" :value="t.temp_id">
                                                <input type="hidden" :name="`tersangka[${index}][id]`" :value="t.id">
                                                
                                                {{-- FOTO --}}
                                                <td class="text-center bg-white">
                                                    <div class="position-relative d-inline-block" @click="$refs['file_'+t.temp_id].click()" style="cursor: pointer;" title="Klik untuk ganti foto">
                                                        <img :src="t.preview_url || '{{ asset('assets/images/user-placeholder.png') }}'" 
                                                             class="rounded-circle border object-fit-cover shadow-sm" 
                                                             width="60" height="60">
                                                        <div class="position-absolute bottom-0 end-0 bg-white rounded-circle border p-1" style="width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;">
                                                            <i class="bi bi-camera-fill text-secondary" style="font-size: 10px;"></i>
                                                        </div>
                                                    </div>
                                                    <input type="file" :name="`tersangka[${index}][foto]`" class="d-none" 
                                                           :x-ref="'file_'+t.temp_id" accept="image/*" @change="handleFoto($event, index)">
                                                    
                                                    <div class="text-danger small mt-1" 
                                                         x-show="hasError('tersangka', index, 'foto')" 
                                                         x-text="getErrorMessage('tersangka', index, 'foto')"></div>
                                                </td>

                                                {{-- INPUT DATA --}}
                                                <td class="bg-white">
                                                    <div class="row g-2">
                                                        <div class="col-md-6">
                                                            <label class="small text-muted">Nama Lengkap</label>
                                                            <input type="text" :name="`tersangka[${index}][nama]`" x-model="t.nama" 
                                                                   @input.debounce.300ms="updateAllTomSelects()" 
                                                                   class="form-control form-control-sm" 
                                                                   :class="{'is-invalid': hasError('tersangka', index, 'nama')}"
                                                                   placeholder="Nama Tersangka">
                                                            <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'nama')"></div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="small text-muted">Jenis Kelamin</label>
                                                            <select :name="`tersangka[${index}][jk]`" x-model="t.jk" 
                                                                    class="form-select form-select-sm"
                                                                    :class="{'is-invalid': hasError('tersangka', index, 'jk')}">
                                                                <option value="Laki-Laki">Laki-Laki</option>
                                                                <option value="Perempuan">Perempuan</option>
                                                            </select>
                                                            <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'jk')"></div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="small text-muted">Pekerjaan</label>
                                                            <input type="text" :name="`tersangka[${index}][pekerjaan]`" x-model="t.pekerjaan" 
                                                                   class="form-control form-control-sm"
                                                                   :class="{'is-invalid': hasError('tersangka', index, 'pekerjaan')}"
                                                                   placeholder="Pekerjaan">
                                                            <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'pekerjaan')"></div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="small text-muted">Status / Tahap</label>
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
                                    <i class="bi bi-plus-lg me-1"></i> Tambah BB
                                </button>
                            </div>

                            <div class="table-responsive mb-5" style="overflow-x: visible;">
                                <table class="table table-bordered align-middle">
                                    <thead class="bg-light text-secondary small text-uppercase">
                                        <tr>
                                            <th width="35%">Pemilik (Bisa Banyak)</th>
                                            <th>Jenis Barang</th>
                                            <th width="15%">Jumlah</th>
                                            <th width="15%">Satuan</th>
                                            <th width="50" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="border-top-0">
                                        <template x-for="(bb, i) in bbList" :key="bb.temp_id">
                                            <tr>
                                                <input type="hidden" :name="`barang_bukti[${i}][id]`" :value="bb.id">
                                                
                                                <td class="bg-white">
                                                    <div wire:ignore 
                                                         :class="{'border border-danger rounded': hasError('barang_bukti', i, 'pemilik_id')}">
                                                        <select :name="`barang_bukti[${i}][pemilik_id][]`" 
                                                                multiple 
                                                                placeholder="Pilih Pemilik..."
                                                                autocomplete="off"
                                                                x-init="initTomSelect($el, bb)">
                                                        </select>
                                                    </div>
                                                    <small class="text-muted fst-italic" style="font-size: 0.75rem" x-show="!hasError('barang_bukti', i, 'pemilik_id')">*Kosong = Milik Kasus (Tak Bertuan)</small>
                                                    
                                                    <div class="text-danger small mt-1" 
                                                         style="font-size: 0.875em;"
                                                         x-show="hasError('barang_bukti', i, 'pemilik_id')" 
                                                         x-text="getErrorMessage('barang_bukti', i, 'pemilik_id')"></div>
                                                </td>

                                                <td class="bg-white">
                                                    <input type="text" :name="`barang_bukti[${i}][jenis]`" x-model="bb.jenis" 
                                                           class="form-control form-control-sm" 
                                                           :class="{'is-invalid': hasError('barang_bukti', i, 'jenis')}"
                                                           placeholder="Jenis BB">
                                                    <div class="invalid-feedback" x-text="getErrorMessage('barang_bukti', i, 'jenis')"></div>
                                                </td>
                                                <td class="bg-white">
                                                    <input type="number" step="0.01" :name="`barang_bukti[${i}][jumlah]`" x-model="bb.jumlah" 
                                                           class="form-control form-control-sm" 
                                                           :class="{'is-invalid': hasError('barang_bukti', i, 'jumlah')}"
                                                           placeholder="0">
                                                    <div class="invalid-feedback" x-text="getErrorMessage('barang_bukti', i, 'jumlah')"></div>
                                                </td>
                                                <td class="bg-white">
                                                    <input type="text" :name="`barang_bukti[${i}][satuan]`" x-model="bb.satuan" 
                                                           class="form-control form-control-sm" 
                                                           :class="{'is-invalid': hasError('barang_bukti', i, 'satuan')}"
                                                           placeholder="Gram/Pcs">
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

                            {{-- SECTION 4: LAMPIRAN & FILE MANAGEMENT --}}
                            <h6 class="text-uppercase text-secondary fw-bold small mb-4 border-bottom pb-2">
                                <i class="bi bi-paperclip me-1"></i> Lampiran & Bukti Fisik
                            </h6>
                            
                            <div class="bg-body-tertiary p-4 rounded-3 border border-dashed mb-4">
                                
                                {{-- A. File Tersimpan --}}
                                @if($kasus->dokumentasi->count() > 0)
                                    <p class="small fw-bold text-secondary mb-2">File Tersimpan:</p>
                                    <div class="row g-3 mb-4" id="existing-files-container">
                                        @foreach($kasus->dokumentasi as $doc)
                                            @php
                                                $isMarkedDeleted = old('delete_files') && in_array($doc->id, old('delete_files'));
                                            @endphp
                                            <div class="col-6 col-md-4 col-lg-3 file-item" id="file-card-{{ $doc->id }}">
                                                <div class="card h-100 shadow-sm border border-secondary-subtle position-relative overflow-hidden file-card-inner transition-all {{ $isMarkedDeleted ? 'border-danger-subtle-thick' : '' }}">
                                                    
                                                    {{-- Overlay Hapus --}}
                                                    <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" 
                                                         style="background-color: rgba(255, 255, 255, 0.9); z-index: 5;">
                                                        <div class="text-danger mb-1"><i class="bi bi-trash3-fill fs-1"></i></div>
                                                        <span class="text-danger fw-bold small text-uppercase">Akan Dihapus</span>
                                                    </div>

                                                    {{-- Preview --}}
                                                    <div class="ratio ratio-16x9 bg-secondary bg-opacity-10 border-bottom d-flex align-items-center justify-content-center overflow-hidden">
                                                        @if(Str::contains($doc->tipe_file, 'image'))
                                                            <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100">
                                                        @else
                                                            <div class="text-secondary"><i class="bi bi-file-earmark-text-fill display-4"></i></div>
                                                        @endif
                                                    </div>
                                                    
                                                    <div class="card-body p-2 text-center d-flex flex-column justify-content-between">
                                                        <div class="small text-truncate fw-bold">{{ $doc->nama_file_asli }}</div>
                                                        <button type="button" 
                                                                id="btn-delete-{{ $doc->id }}"
                                                                class="btn btn-sm w-100 py-0 mt-2 {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" 
                                                                onclick="markForDeletion({{ $doc->id }})">
                                                            @if($isMarkedDeleted) Batal @else Hapus @endif
                                                        </button>
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
                                @endif

                                {{-- B. Upload Baru --}}
                                <label class="form-label fw-bold h6 mb-1 text-dark mt-2">
                                    <i class="bi bi-cloud-arrow-up me-2"></i>Upload File Baru
                                </label>
                                <p class="text-muted small mb-3">Format: .jpg, .png, .pdf, .docx. Maks 10MB/file.</p>
                                
                                <input type="file" class="filepond" name="dokumentasi[]" multiple data-allow-reorder="true" data-max-file-size="10MB" data-max-files="10">
                                
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
                                    <span x-text="isUploading ? 'Mengupload...' : 'Simpan Perubahan'"></span>
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
        .ts-control {
            border: 1px solid #dee2e6;
            padding: 0.4rem 0.75rem; 
            border-radius: 0.375rem;
            box-shadow: none;
            font-size: 0.875rem; 
        }
        .ts-control.focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .ts-dropdown { z-index: 9999 !important; }
        .filepond--panel-root { background-color: #ffffff; border: 1px solid #dee2e6; }
        .border-dashed { border-style: dashed !important; border-width: 2px !important; }
        .border-danger-subtle-thick { border-color: #dc3545 !important; border-width: 2px !important; }
    </style>
@endpush

@push('scripts')
<script>
    // Logic Hapus File Lama (Vanilla JS)
    window.markForDeletion = function(id) {
        const cardInner = document.querySelector('#file-card-' + id + ' .file-card-inner');
        const overlay = cardInner.querySelector('.delete-overlay');
        const btnDelete = document.getElementById('btn-delete-' + id);
        const containerInputs = document.getElementById('delete-inputs-container');
        
        if (!overlay.classList.contains('d-none')) {
            overlay.classList.add('d-none'); overlay.classList.remove('d-flex');
            cardInner.classList.remove('border-danger-subtle-thick');
            btnDelete.classList.remove('btn-secondary'); btnDelete.classList.add('btn-outline-danger');
            btnDelete.innerHTML = 'Hapus';
            const input = document.getElementById('input-delete-' + id);
            if(input) input.remove();
        } else {
            overlay.classList.remove('d-none'); overlay.classList.add('d-flex');
            cardInner.classList.add('border-danger-subtle-thick');
            btnDelete.classList.remove('btn-outline-danger'); btnDelete.classList.add('btn-secondary');
            btnDelete.innerHTML = 'Batal';
            const input = document.createElement('input');
            input.type = 'hidden'; input.name = 'delete_files[]'; input.value = id; input.id = 'input-delete-' + id;
            containerInputs.appendChild(input);
        }
    };
</script>

<script type="module">
    document.addEventListener('alpine:init', () => {
        Alpine.data('kasusForm', () => ({
            // STATE
            tersangkaList: [],
            bbList: [],
            isUploading: false,
            tomSelectInstances: {}, 
            errors: @json($errors->toArray()),

            // INITIALIZATION (EDIT MODE)
            init() {
                // 1. Data dari Database
                const dbTersangka = {!! json_encode($kasus->tersangka) !!};
                const dbBB = {!! json_encode($kasus->barangBukti) !!};
                
                // 2. Data Old Input (Jika Validasi Gagal)
                const oldTersangka = @json(old('tersangka', []));
                const oldBB = @json(old('barang_bukti', []));

                // A. SETUP TERSANGKA
                if (oldTersangka.length > 0) {
                    oldTersangka.forEach(t => {
                        this.tersangkaList.push({
                            temp_id: t.temp_id || ('t_' + Math.random().toString(36).substr(2, 9)),
                            id: t.id || null,
                            nama: t.nama || '',
                            jk: t.jk || 'Laki-Laki',
                            pekerjaan: t.pekerjaan || '',
                            tahap: t.tahap || '',
                            preview_url: null 
                        });
                    });
                } else if (dbTersangka.length > 0) {
                    dbTersangka.forEach(t => {
                        this.tersangkaList.push({
                            temp_id: 't_' + t.id, // Gunakan ID asli sebagai basis temp_id
                            id: t.id,
                            nama: t.nama_tersangka,
                            jk: t.jenis_kelamin,
                            pekerjaan: t.pekerjaan,
                            tahap: t.tahap,
                            preview_url: t.foto_tersangka ? '/storage/' + t.foto_tersangka : null
                        });
                    });
                } else {
                    this.addTersangka();
                }

                // B. SETUP BARANG BUKTI
                if (oldBB.length > 0) {
                    oldBB.forEach(b => {
                        this.bbList.push({
                            temp_id: 'bb_' + Math.random().toString(36).substr(2, 9),
                            id: b.id || null,
                            jenis: b.jenis || '',
                            jumlah: b.jumlah || '',
                            satuan: b.satuan || '',
                            initial_pemilik: b.pemilik_id || []
                        });
                    });
                } else if (dbBB.length > 0) {
                    dbBB.forEach(b => {
                        // Mapping relasi pivot ke array ID (t_ID)
                        let owners = b.tersangka.map(tsk => 't_' + tsk.id);
                        this.bbList.push({
                            temp_id: 'bb_' + b.id,
                            id: b.id,
                            jenis: b.jenis_barang_bukti,
                            jumlah: b.jumlah_barang_bukti,
                            satuan: b.satuan_barang_bukti,
                            initial_pemilik: owners
                        });
                    });
                } else {
                    this.addBB();
                }

                // C. INIT FILEPOND
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
                    id: null,
                    nama: '', jk: 'Laki-Laki', pekerjaan: '', tahap: '', preview_url: null 
                });
                this.$nextTick(() => { this.updateAllTomSelects(); });
            },

            removeTersangka(index) {
                const suspectId = this.tersangkaList[index].temp_id;
                const suspectName = this.tersangkaList[index].nama || 'Tersangka ini';

                let isUsed = false;
                Object.values(this.tomSelectInstances).forEach(ts => {
                    const selectedValues = ts.getValue();
                    if (selectedValues.includes(suspectId)) isUsed = true;
                });

                if (isUsed) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Hapus',
                        text: `"${suspectName}" sedang dipilih sebagai pemilik Barang Bukti. Harap hapus dari daftar pemilik terlebih dahulu.`,
                        confirmButtonColor: '#d33'
                    });
                    return;
                }

                if (this.tersangkaList.length === 1) {
                    Swal.fire('Info', 'Minimal harus ada satu data tersangka.', 'info');
                    return;
                }
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
                    id: null,
                    jenis: '', jumlah: '', satuan: 'Gram', initial_pemilik: [] 
                });
            },

            removeBB(index) {
                if (this.bbList.length === 1) {
                    Swal.fire('Info', 'Minimal harus ada satu data Barang Bukti.', 'info');
                    return;
                }
                const bbTempId = this.bbList[index].temp_id;
                if(this.tomSelectInstances[bbTempId]) {
                    this.tomSelectInstances[bbTempId].destroy();
                    delete this.tomSelectInstances[bbTempId];
                }
                this.bbList.splice(index, 1);
            },

            initTomSelect(el, bbData) {
                const ts = new TomSelect(el, {
                    plugins: ['remove_button', 'dropdown_input'],
                    valueField: 'value', 
                    labelField: 'text', 
                    searchField: 'text', 
                    create: false, 
                    maxOptions: null,
                    placeholder: "Pilih pemilik...",
                    dropdownParent: 'body'
                });
                
                this.tomSelectInstances[bbData.temp_id] = ts;
                this.refreshOptionsForInstance(ts);

                // Set Value (Pre-fill)
                if (bbData.initial_pemilik && bbData.initial_pemilik.length > 0) {
                    ts.setValue(bbData.initial_pemilik);
                }
            },

            updateAllTomSelects() {
                Object.values(this.tomSelectInstances).forEach(ts => { this.refreshOptionsForInstance(ts); });
            },

            refreshOptionsForInstance(ts) {
                this.tersangkaList.forEach(t => {
                    const label = t.nama.trim() === '' ? '(Tanpa Nama)' : t.nama;
                    if (ts.options[t.temp_id]) {
                        ts.updateOption(t.temp_id, { value: t.temp_id, text: label });
                    } else {
                        ts.addOption({ value: t.temp_id, text: label });
                    }
                });

                const validIds = this.tersangkaList.map(t => t.temp_id);
                Object.keys(ts.options).forEach(optVal => {
                    if (!validIds.includes(optVal)) {
                        ts.removeOption(optVal);
                    }
                });
                ts.refreshOptions(false); 
            },

            submitData(e) {
                if (this.isUploading) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Upload Belum Selesai',
                        text: 'Mohon tunggu hingga semua file selesai diupload.',
                        confirmButtonColor: '#0d6efd'
                    });
                    return;
                }

                if (this.tersangkaList.length === 0 || this.bbList.length === 0) {
                     Swal.fire('Data Belum Lengkap', 'Mohon isi minimal 1 Tersangka dan 1 Barang Bukti.', 'warning');
                     return;
                }

                e.target.submit();
            }
        }));
    });
</script>
@endpush