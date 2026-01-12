@extends('admin')

@section('content')
<main class="admin-main" x-data="kasusForm">
    <div class="container-fluid p-4 p-lg-5">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Edit Ungkap Kasus</h1>
                <p class="text-muted mb-0">Update Data Penindakan dan Ungkap Kasus</p>
            </div>
            <a href="{{ route('berantas.ungkap-kasus.index') }}" 
               class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

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
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold text-primary">
                            <i class="bi bi-pencil-square me-2"></i>Form Edit Data
                        </h5>
                    </div>

                    <div class="card-body p-4 p-lg-5">
                        <form action="{{ route('berantas.ungkap-kasus.update', $kasus->id) }}" 
                              method="POST" 
                              enctype="multipart/form-data" 
                              @submit.prevent="submitData">
                            @csrf
                            @method('PUT')

                            {{-- SECTION 1: DATA LKN --}}
                            <h6 class="text-uppercase text-secondary fw-bold small mb-4 border-bottom pb-2">
                                <i class="bi bi-info-circle me-1"></i> Data LKN
                            </h6>
                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-secondary small">
                                        Nomor LKN <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           name="nomor_lkn" 
                                           class="form-control @error('nomor_lkn') is-invalid @enderror" 
                                           value="{{ old('nomor_lkn', $kasus->nomor_lkn) }}">
                                    @error('nomor_lkn') 
                                        <div class="invalid-feedback">{{ $message }}</div> 
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-secondary small">
                                        Tanggal Kejadian <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" 
                                           name="tanggal_kejadian" 
                                           class="form-control @error('tanggal_kejadian') is-invalid @enderror" 
                                           value="{{ old('tanggal_kejadian', $kasus->tanggal_kejadian->format('Y-m-d')) }}">
                                    @error('tanggal_kejadian') 
                                        <div class="invalid-feedback">{{ $message }}</div> 
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-secondary small">
                                        Lokasi / TKP <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="alamat_tkp" 
                                              class="form-control @error('alamat_tkp') is-invalid @enderror" 
                                              rows="2">{{ old('alamat_tkp', $kasus->alamat_tkp) }}</textarea>
                                    @error('alamat_tkp') 
                                        <div class="invalid-feedback">{{ $message }}</div> 
                                    @enderror
                                </div>
                            </div>

                            {{-- SECTION 2: TERSANGKA --}}
                            <div class="d-flex justify-content-between align-items-end mb-3 border-bottom pb-2">
                                <h6 class="text-uppercase text-secondary fw-bold small m-0">
                                    <i class="bi bi-people me-1"></i> Daftar Tersangka
                                </h6>
                                <button type="button" 
                                        class="btn btn-primary btn-sm shadow-sm" 
                                        @click="addTersangka">
                                    <i class="bi bi-plus-lg me-1"></i> Tambah Tersangka
                                </button>
                            </div>
                            
                            <div class="mb-5">
                                <table class="table table-bordered align-middle table-mobile-responsive">
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
                                                <input type="hidden" :name="`tersangka[${index}][id]`" :value="t.id">
                                                
                                                <td class="text-center bg-white" data-label="Foto">
                                                    <div class="position-relative d-inline-block" 
                                                         @click="document.getElementById('file_'+t.temp_id).click()" 
                                                         style="cursor: pointer;" 
                                                         title="Klik untuk ganti foto">
                                                        <img :src="t.preview_url || '{{ asset('assets/images/user-placeholder.png') }}'" 
                                                             class="rounded-circle border object-fit-cover shadow-sm" 
                                                             width="60" height="60">
                                                        <div class="position-absolute bottom-0 end-0 bg-white rounded-circle border p-1" 
                                                             style="width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;">
                                                            <i class="bi bi-camera-fill text-secondary" style="font-size: 10px;"></i>
                                                        </div>
                                                    </div>
                                                    <input type="file" :name="`tersangka[${index}][foto]`" 
                                                           class="d-none" :id="'file_'+t.temp_id" accept="image/*" @change="handleFoto($event, index)">
                                                </td>

                                                <td class="bg-white" data-label="Detail Tersangka">
                                                    <div class="row g-2">
                                                        <div class="col-md-6">
                                                            <label class="form-label small text-muted mb-1">Nama Lengkap</label>
                                                            <input type="text" :name="`tersangka[${index}][nama]`" x-model="t.nama" 
                                                                   @input.debounce.300ms="updateAllTomSelects()" 
                                                                   class="form-control form-control-sm" :class="{'is-invalid': hasError('tersangka', index, 'nama')}">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label small text-muted mb-1">Jenis Kelamin</label>
                                                            <select :name="`tersangka[${index}][jk]`" x-model="t.jk" class="form-select form-select-sm">
                                                                <option value="Laki-Laki">Laki-Laki</option>
                                                                <option value="Perempuan">Perempuan</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label small text-muted mb-1">Pekerjaan</label>
                                                            <input type="text" :name="`tersangka[${index}][pekerjaan]`" x-model="t.pekerjaan" class="form-control form-control-sm">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label small text-muted mb-1">Status / Tahap</label>
                                                            <input type="text" :name="`tersangka[${index}][tahap]`" x-model="t.tahap" class="form-control form-control-sm" placeholder="Contoh: Tahap II">
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center bg-white">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" @click="removeTersangka(index)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
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

                            <div class="mb-5">
                                <table class="table table-bordered align-middle table-mobile-responsive">
                                    <thead class="bg-light text-secondary small text-uppercase">
                                        <tr>
                                            <th width="25%">Pemilik (Tersangka)</th>
                                            <th width="15%">Kategori</th>
                                            <th>Nama Barang Bukti</th>
                                            <th width="15%" x-text="getQuantityLabel()">Berat / Jumlah</th>
                                            <th width="15%">Satuan</th>
                                            <th width="50" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="border-top-0">
                                        <template x-for="(bb, i) in bbList" :key="bb.temp_id">
                                            <tr>
                                                <input type="hidden" :name="`barang_bukti[${i}][id]`" :value="bb.id">
                                                <td class="bg-white">
                                                    <div wire:ignore>
                                                        <select :name="`barang_bukti[${i}][pemilik_id][]`" multiple x-init="initTomSelectOwner($el, bb)"></select>
                                                    </div>
                                                </td>
                                                <td class="bg-white">
                                                    <select :name="`barang_bukti[${i}][kategori]`" x-model="bb.kategori" class="form-select form-select-sm" @change="resetSatuan(bb)">
                                                        <option value="Narkotika">Narkotika</option>
                                                        <option value="Non-Narkotika">Non-Narkotika</option>
                                                    </select>
                                                </td>
                                                <td class="bg-white">
                                                    <div x-show="bb.kategori === 'Narkotika'">
                                                        <select :name="`barang_bukti[${i}][narkotika_id]`" x-init="initTomSelectNarkotika($el, bb)"></select>
                                                    </div>
                                                    <div x-show="bb.kategori === 'Non-Narkotika'">
                                                        <input type="text" :name="`barang_bukti[${i}][nama_barang_bukti]`" x-model="bb.nama_barang_bukti" class="form-control form-control-sm">
                                                    </div>
                                                </td>
                                                <td class="bg-white">
                                                    <input type="number" step="0.0001" :name="`barang_bukti[${i}][jumlah]`" x-model="bb.jumlah" class="form-control form-control-sm">
                                                </td>
                                                <td class="bg-white">
                                                    <div x-show="bb.kategori === 'Narkotika'">
                                                        <select :name="`barang_bukti[${i}][satuan]`" x-model="bb.satuan" class="form-select form-select-sm">
                                                            <option value="Gram">Gram</option>
                                                            <option value="Kg">Kg</option>
                                                            <option value="Ton">Ton</option>
                                                        </select>
                                                    </div>
                                                    <div x-show="bb.kategori === 'Non-Narkotika'">
                                                        <input type="text" :name="`barang_bukti[${i}][satuan]`" x-model="bb.satuan" class="form-control form-control-sm">
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-outline-danger btn-sm" @click="removeBB(i)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            {{-- SECTION 4: LAMPIRAN --}}
                            <h6 class="text-uppercase text-secondary fw-bold small mb-4 border-bottom pb-2">
                                <i class="bi bi-paperclip me-1"></i> Lampiran
                            </h6>
                            <div class="bg-body-tertiary p-4 rounded-3 border border-dashed mb-4">
                                @if($kasus->dokumentasi->count() > 0)
                                    <p class="small fw-bold text-secondary mb-2">File Tersimpan:</p>
                                    <div class="row g-3 mb-4" id="existing-files-container">
                                        @foreach($kasus->dokumentasi as $doc)
                                            @php $isMarkedDeleted = old('delete_files') && in_array($doc->id, old('delete_files')); @endphp
                                            <div class="col-6 col-md-4 col-lg-3 file-item" id="file-card-{{ $doc->id }}">
                                                <div class="card h-100 shadow-sm border position-relative overflow-hidden file-card-inner transition-all {{ $isMarkedDeleted ? 'border-danger border-2' : '' }}">
                                                    <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" style="background-color: rgba(255, 255, 255, 0.9); z-index: 5;">
                                                        <div class="text-danger mb-1"><i class="bi bi-trash3-fill fs-2"></i></div>
                                                        <span class="text-danger fw-bold small">AKAN DIHAPUS</span>
                                                    </div>
                                                    <div class="ratio ratio-16x9 bg-secondary bg-opacity-10 border-bottom d-flex align-items-center justify-content-center">
                                                        @if(Str::contains($doc->tipe_file, 'image')) 
                                                            <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100"> 
                                                        @else 
                                                            <i class="bi bi-file-earmark-text text-secondary fs-1"></i> 
                                                        @endif
                                                    </div>
                                                    <div class="card-body p-2 text-center">
                                                        <div class="small text-truncate fw-bold">{{ $doc->nama_file_asli }}</div>
                                                        <button type="button" class="btn btn-sm w-100 mt-2 {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" onclick="markForDeletion({{ $doc->id }})">
                                                            {{ $isMarkedDeleted ? 'Batal' : 'Hapus' }}
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

                                <label class="form-label fw-bold h6 mb-1 text-dark"><i class="bi bi-cloud-arrow-up me-2"></i>Upload File Baru</label>
                                <input type="file" class="filepond" name="dokumentasi[]" multiple>
                            </div>

                            <div class="d-flex flex-column-reverse flex-lg-row justify-content-end gap-2 pt-4 border-top mt-5">
                                <button type="button" onclick="window.location.reload()" class="btn btn-light border text-secondary px-4">Reset</button>
                                <button type="submit" class="btn btn-primary px-5 shadow-sm" :disabled="isUploading">
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
        .ts-control { border: 1px solid #dee2e6; padding: 0.4rem 0.75rem; border-radius: 0.375rem; font-size: 0.875rem; }
        .border-dashed { border-style: dashed !important; border-width: 2px !important; }
        .file-card-inner { transition: all 0.3s ease; }
    </style>
@endpush

@push('scripts')
<script>
    window.markForDeletion = function(id) {
        const cardInner = document.querySelector('#file-card-' + id + ' .file-card-inner');
        const overlay = cardInner.querySelector('.delete-overlay');
        const btnDelete = event.target;
        const containerInputs = document.getElementById('delete-inputs-container');
        
        if (!overlay.classList.contains('d-none')) {
            overlay.classList.add('d-none'); overlay.classList.remove('d-flex');
            cardInner.classList.remove('border-danger', 'border-2');
            btnDelete.classList.remove('btn-secondary');
            btnDelete.classList.add('btn-outline-danger');
            btnDelete.innerHTML = 'Hapus';
            const input = document.getElementById('input-delete-' + id);
            if(input) input.remove();
        } else {
            overlay.classList.remove('d-none'); overlay.classList.add('d-flex');
            cardInner.classList.add('border-danger', 'border-2');
            btnDelete.classList.remove('btn-outline-danger');
            btnDelete.classList.add('btn-secondary');
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
            tersangkaList: [],
            bbList: [],
            isUploading: false,
            tomSelectOwners: {}, 
            tomSelectNarkotika: {},
            errors: @json($errors->toArray()),
            masterNarkotika: @json($masterNarkotika),

            init() {
                const dbTersangka = {!! json_encode($kasus->tersangka) !!};
                const dbBB = {!! json_encode($kasus->barangBukti) !!};
                const oldTersangka = @json(old('tersangka', []));
                const oldBB = @json(old('barang_bukti', []));

                // 1. Inisialisasi Tersangka
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
                } else {
                    dbTersangka.forEach(t => {
                        this.tersangkaList.push({ 
                            temp_id: 't_' + t.id, 
                            id: t.id, 
                            nama: t.nama_tersangka, 
                            jk: t.jenis_kelamin, 
                            pekerjaan: t.pekerjaan, 
                            tahap: t.tahap, 
                            preview_url: t.foto_tersangka ? '/storage/' + t.foto_tersangka : null 
                        });
                    });
                }

                // 2. Inisialisasi Barang Bukti (Dengan parseFloat untuk hilangkan ,0000)
                if (oldBB.length > 0) {
                    oldBB.forEach(b => {
                        this.bbList.push({ 
                            temp_id: 'bb_' + Math.random().toString(36).substr(2, 9), 
                            id: b.id || null, 
                            kategori: b.kategori, 
                            narkotika_id: b.narkotika_id, 
                            nama_barang_bukti: b.nama_barang_bukti, 
                            jumlah: b.jumlah ? parseFloat(b.jumlah) : '', 
                            satuan: b.satuan, 
                            initial_pemilik: b.pemilik_id || [] 
                        });
                    });
                } else {
                    dbBB.forEach(b => {
                        this.bbList.push({ 
                            temp_id: 'bb_' + b.id, 
                            id: b.id, 
                            kategori: b.kategori, 
                            narkotika_id: b.narkotika_id, 
                            nama_barang_bukti: b.nama_barang_non_narkotika, 
                            jumlah: parseFloat(b.kuantitas), // Menggunakan parseFloat
                            satuan: b.kategori === 'Narkotika' ? b.satuan_narkotika : b.satuan_non_narkotika, 
                            initial_pemilik: b.tersangka.map(tsk => 't_' + tsk.id)
                        });
                    });
                }

                // 3. FilePond
                if(window.FilePond) {
                    FilePond.create(document.querySelector('input.filepond'), {
                        server: { 
                            process: { url: '{{ route('upload.temp') }}', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }, 
                            revert: { url: '{{ route('revert.temp') }}', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } } 
                        },
                        onprocessstart: () => { this.isUploading = true }, 
                        onprocessfiles: () => { this.isUploading = false }
                    });
                }
            },

            addTersangka() {
                this.tersangkaList.push({ 
                    temp_id: 't_' + Date.now(), id: null, nama: '', jk: 'Laki-Laki', pekerjaan: '', tahap: '', preview_url: null 
                });
                this.$nextTick(() => this.updateAllTomSelects());
            },

            removeTersangka(index) {
                if (this.tersangkaList.length === 1) return;
                this.tersangkaList.splice(index, 1);
                this.$nextTick(() => this.updateAllTomSelects());
            },

            handleFoto(e, index) {
                const file = e.target.files[0];
                if(file) this.tersangkaList[index].preview_url = URL.createObjectURL(file);
            },

            addBB() {
                this.bbList.push({ 
                    temp_id: 'bb_' + Date.now(), id: null, kategori: 'Narkotika', narkotika_id: '', nama_barang_bukti: '', jumlah: '', satuan: 'Gram', initial_pemilik: [] 
                });
            },

            removeBB(index) {
                this.bbList.splice(index, 1);
            },

            getQuantityLabel() {
                return this.bbList.every(bb => bb.kategori === 'Narkotika') ? 'Berat' : 'Berat / Jumlah';
            },

            resetSatuan(bb) {
                bb.satuan = bb.kategori === 'Narkotika' ? 'Gram' : '';
            },

            initTomSelectOwner(el, bbData) {
                const ts = new TomSelect(el, { 
                    plugins: ['remove_button'], 
                    valueField: 'value', 
                    labelField: 'text', 
                    searchField: 'text',
                    dropdownParent: 'body'
                });
                this.tomSelectOwners[bbData.temp_id] = ts;
                this.refreshOptionsForInstance(ts);
                if (bbData.initial_pemilik.length > 0) {
                    ts.setValue(bbData.initial_pemilik);
                    bbData.pemilik_id = bbData.initial_pemilik;
                }
                ts.on('change', (val) => bbData.pemilik_id = val);
            },

            updateAllTomSelects() {
                Object.values(this.tomSelectOwners).forEach(ts => this.refreshOptionsForInstance(ts));
            },

            refreshOptionsForInstance(ts) {
                const validIds = this.tersangkaList.map(t => {
                    const label = t.nama.trim() || '(Tanpa Nama)';
                    if (ts.options[t.temp_id]) {
                        ts.updateOption(t.temp_id, { value: t.temp_id, text: label });
                    } else {
                        ts.addOption({ value: t.temp_id, text: label });
                    }
                    return t.temp_id;
                });
                Object.keys(ts.options).forEach(opt => {
                    if (!validIds.includes(opt)) ts.removeOption(opt);
                });
                ts.refreshOptions(false);
            },

            initTomSelectNarkotika(el, bbData) {
                const options = this.masterNarkotika.map(m => ({ id: m.id, text: m.nama_narkotika }));
                const ts = new TomSelect(el, {
                    valueField: 'id', labelField: 'text', searchField: ['text'],
                    options: options, dropdownParent: 'body'
                });
                if (bbData.narkotika_id) ts.setValue(bbData.narkotika_id);
                ts.on('change', (val) => bbData.narkotika_id = val);
            },

            hasError(field, index, key) {
                return this.errors[`${field}.${index}.${key}`];
            },

            getErrorMessage(field, index, key) {
                return this.errors[`${field}.${index}.${key}`]?.[0] || '';
            },

            submitData(e) {
                if (this.isUploading) {
                    return Swal.fire('Tunggu', 'Upload file belum selesai', 'warning');
                }
                e.target.submit();
            }
        }));
    });
</script>
@endpush