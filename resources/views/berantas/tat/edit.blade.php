@extends('admin')

@section('content')
<main class="admin-main" x-data="tatForm">
    <div class="container-fluid p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Edit Data TAT</h4>
                <p class="text-secondary small mb-0">Update Data Tim Asesmen Terpadu</p>
            </div>
            <a href="{{ route('berantas.tat.index') }}" class="btn btn-secondary btn-sm px-3">Kembali</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><strong>Periksa Kembali Inputan!</strong> Terdapat kesalahan pengisian data.</div>
                </div>
            </div>
        @endif

        <form action="{{ route('berantas.tat.update', $tat->id) }}" method="POST" enctype="multipart/form-data" id="form-tat" @submit.prevent="submitForm">
            @csrf @method('PUT')
            
            {{-- DATA UTAMA --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4 text-dark border-bottom pb-2">Informasi Umum</h5>
                    <div class="row g-3">
                        @if(Auth::user()->isAdmin())
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Satuan Kerja</label>
                            <select name="satuan_kerja_id" class="form-select py-2">
                                @foreach($satuanKerjas as $s) 
                                    <option value="{{ $s->id }}" @selected(old('satuan_kerja_id', $tat->satuan_kerja_id) == $s->id)>{{ $s->satuan_kerja }}</option> 
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">No. Register TAT <span class="text-danger">*</span></label>
                            <input type="text" name="no_register" value="{{ old('no_register', $tat->no_register) }}" class="form-control py-2 @error('no_register') is-invalid @enderror">
                            @error('no_register') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan', $tat->tanggal_pelaksanaan->format('Y-m-d')) }}" class="form-control py-2 @error('tanggal_pelaksanaan') is-invalid @enderror">
                            @error('tanggal_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- TERSANGKA --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title fw-bold mb-0 text-dark">Daftar Tersangka</h5>
                        <button type="button" class="btn btn-dark btn-sm px-3" @click="addTersangka">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Tersangka
                        </button>
                    </div>

                    @error('tersangka') <div class="alert alert-danger small py-1">{{ $message }}</div> @enderror

                    <template x-for="(t, index) in tersangkaList" :key="t.temp_id">
                        <div class="card bg-light border mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                    <span class="fw-bold text-secondary small">Data Tersangka #<span x-text="index+1"></span></span>
                                    <button type="button" class="btn btn-sm text-danger" @click="removeTersangka(index)" x-show="tersangkaList.length > 1">
                                        <i class="bi bi-trash me-1"></i> Hapus
                                    </button>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary">Nama Lengkap <span class="text-danger">*</span></label>
                                        <input type="text" :name="`tersangka[${index}][nama]`" x-model="t.nama" 
                                               class="form-control py-2" :class="{'is-invalid': hasError('tersangka', index, 'nama')}"
                                               placeholder="Masukkan nama lengkap...">
                                        <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'nama')"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary">NIK <span class="text-danger">*</span></label>
                                        <input type="text" :name="`tersangka[${index}][nik]`" x-model="t.nik" 
                                               class="form-control py-2" :class="{'is-invalid': hasError('tersangka', index, 'nik')}"
                                               placeholder="16 Digit NIK...">
                                        <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'nik')"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary">Jenis Kelamin</label>
                                        <select :name="`tersangka[${index}][jk]`" x-model="t.jk" class="form-select py-2">
                                            <option value="Laki-laki">Laki-laki</option><option value="Perempuan">Perempuan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold text-secondary">Usia <span class="text-danger">*</span></label>
                                        <input type="number" :name="`tersangka[${index}][usia]`" x-model="t.usia" 
                                               class="form-control py-2" :class="{'is-invalid': hasError('tersangka', index, 'usia')}" placeholder="Usia...">
                                        <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'usia')"></div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold text-secondary">Pendidikan <span class="text-danger">*</span></label>
                                        <input type="text" :name="`tersangka[${index}][pendidikan]`" x-model="t.pendidikan" 
                                               class="form-control py-2" :class="{'is-invalid': hasError('tersangka', index, 'pendidikan')}" placeholder="Pendidikan...">
                                        <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'pendidikan')"></div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold text-secondary">Pekerjaan <span class="text-danger">*</span></label>
                                        <input type="text" :name="`tersangka[${index}][pekerjaan]`" x-model="t.pekerjaan" 
                                               class="form-control py-2" :class="{'is-invalid': hasError('tersangka', index, 'pekerjaan')}" placeholder="Pekerjaan...">
                                        <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'pekerjaan')"></div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold text-secondary">No Telepon <span class="text-danger">*</span></label>
                                        <input type="text" :name="`tersangka[${index}][no_telepon]`" x-model="t.no_telepon" 
                                               class="form-control py-2" :class="{'is-invalid': hasError('tersangka', index, 'no_telepon')}" placeholder="08xxx...">
                                        <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'no_telepon')"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- BARANG BUKTI --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title fw-bold mb-0 text-dark">Barang Bukti</h5>
                        <button type="button" class="btn btn-dark btn-sm px-3" @click="addBB">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Baris
                        </button>
                    </div>

                    @error('barang_bukti') <div class="alert alert-danger small py-1">{{ $message }}</div> @enderror

                    <div class="table-responsive border rounded">
                        <table class="table table-bordered align-middle mb-0 bg-white">
                            <thead class="bg-light small">
                                <tr>
                                    <th width="20%">Kategori</th>
                                    <th>Nama Barang (Cari/Ketik)</th>
                                    <th width="15%" x-text="getQuantityLabel()">Berat / Jumlah</th>
                                    <th width="15%">Satuan</th>
                                    <th width="50" class="text-center">Hapus</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(bb, i) in bbList" :key="bb.temp_id">
                                    <tr>
                                        <td class="align-top">
                                            <select :name="`barang_bukti[${i}][kategori]`" x-model="bb.kategori" class="form-select py-2" @change="resetBB(bb)">
                                                <option value="Narkotika">Narkotika</option><option value="Non-Narkotika">Non-Narkotika</option>
                                            </select>
                                        </td>
                                        <td class="align-top">
                                            <div x-show="bb.kategori === 'Narkotika'" class="w-100">
                                                <div wire:ignore :class="{'border border-danger rounded': hasError('barang_bukti', i, 'narkotika_ids')}">
                                                    <select :id="'select_bb_' + bb.temp_id" :name="`barang_bukti[${i}][narkotika_ids][]`" multiple x-init="initTS($el, bb)"></select>
                                                </div>
                                                <div class="text-danger small mt-1" x-show="hasError('barang_bukti', i, 'narkotika_ids')" x-text="getErrorMessage('barang_bukti', i, 'narkotika_ids')"></div>
                                            </div>
                                            <div x-show="bb.kategori === 'Non-Narkotika'" class="w-100">
                                                <input type="text" :name="`barang_bukti[${i}][nama_barang_bukti]`" x-model="bb.nama_barang_bukti" 
                                                       class="form-control py-2" :class="{'is-invalid': hasError('barang_bukti', i, 'nama_barang_bukti')}"
                                                       placeholder="Ketik nama barang...">
                                                <div class="invalid-feedback" x-text="getErrorMessage('barang_bukti', i, 'nama_barang_bukti')"></div>
                                            </div>
                                        </td>
                                        <td class="align-top">
                                            <input type="number" step="0.0001" :name="`barang_bukti[${i}][jumlah]`" x-model="bb.jumlah" 
                                                   class="form-control py-2" :class="{'is-invalid': hasError('barang_bukti', i, 'jumlah')}"
                                                   placeholder="0.00">
                                            <div class="invalid-feedback" x-text="getErrorMessage('barang_bukti', i, 'jumlah')"></div>
                                        </td>
                                        <td class="align-top">
                                            <template x-if="bb.kategori === 'Narkotika'">
                                                <div>
                                                    <select :name="`barang_bukti[${i}][satuan]`" x-model="bb.satuan" class="form-select py-2" :class="{'is-invalid': hasError('barang_bukti', i, 'satuan')}">
                                                        <option value="Gram">Gram</option><option value="Kg">Kg</option><option value="Butir">Butir</option>
                                                    </select>
                                                    <div class="invalid-feedback d-block" x-show="hasError('barang_bukti', i, 'satuan')" x-text="getErrorMessage('barang_bukti', i, 'satuan')"></div>
                                                </div>
                                            </template>
                                            <template x-if="bb.kategori === 'Non-Narkotika'">
                                                <div class="w-100">
                                                    <input type="text" :name="`barang_bukti[${i}][satuan]`" x-model="bb.satuan" 
                                                           class="form-control py-2" :class="{'is-invalid': hasError('barang_bukti', i, 'satuan')}" placeholder="Pcs/Unit">
                                                    <div class="invalid-feedback d-block" x-show="hasError('barang_bukti', i, 'satuan')" x-text="getErrorMessage('barang_bukti', i, 'satuan')"></div>
                                                </div>
                                            </template>
                                        </td>
                                        <td class="text-center align-top"><button type="button" class="btn btn-sm text-danger" @click="removeBB(i)"><i class="bi bi-trash"></i></button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- DETAIL --}}
            <div class="card shadow-sm border-0 mb-5">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4 text-dark border-bottom pb-2">Detail Kasus & Asesmen</h5>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label fw-semibold small text-secondary">Pasal Disangkakan</label><textarea name="pasal_disangkakan" class="form-control py-2 auto-resize" rows="2" placeholder="Masukkan pasal...">{{ old('pasal_disangkakan', $tat->pasal_disangkakan) }}</textarea></div>
                        <div class="col-md-6"><label class="form-label fw-semibold small text-secondary">Instansi Pengirim</label><input type="text" name="instansi_pengirim" value="{{ old('instansi_pengirim', $tat->instansi_pengirim) }}" class="form-control py-2" placeholder="Nama instansi..."></div>
                        <div class="col-md-3"><label class="form-label fw-semibold small text-secondary">Tgl Penangkapan</label><input type="date" name="tanggal_penangkapan" value="{{ old('tanggal_penangkapan', $tat->tanggal_penangkapan?->format('Y-m-d')) }}" class="form-control py-2"></div>
                        <div class="col-md-3"><label class="form-label fw-semibold small text-secondary">Tgl Permohonan</label><input type="date" name="tanggal_permohonan" value="{{ old('tanggal_permohonan', $tat->tanggal_permohonan?->format('Y-m-d')) }}" class="form-control py-2"></div>
                        <div class="col-md-6"><label class="form-label fw-semibold small text-secondary">Tim Hukum</label><textarea name="tim_hukum" class="form-control py-2 auto-resize" rows="2" placeholder="Tim Hukum...">{{ old('tim_hukum', $tat->tim_hukum) }}</textarea></div>
                        <div class="col-md-6"><label class="form-label fw-semibold small text-secondary">Tim Medis</label><textarea name="tim_medis" class="form-control py-2 auto-resize" rows="2" placeholder="Tim Medis...">{{ old('tim_medis', $tat->tim_medis) }}</textarea></div>
                        <div class="col-12"><label class="form-label fw-semibold small text-secondary">Lembaga Rehab</label><input type="text" name="lembaga_rehab" value="{{ old('lembaga_rehab', $tat->lembaga_rehab) }}" class="form-control py-2" placeholder="Nama lembaga..."></div>
                        <div class="col-md-6"><label class="form-label fw-semibold small text-secondary">Rekomendasi</label><select name="tindak_lanjut_rekomendasi" class="form-select py-2"><option value="dilaksanakan" @selected(old('tindak_lanjut_rekomendasi', $tat->tindak_lanjut_rekomendasi)=='dilaksanakan')>Dilaksanakan</option><option value="tidak dilaksanakan" @selected(old('tindak_lanjut_rekomendasi', $tat->tindak_lanjut_rekomendasi)=='tidak dilaksanakan')>Tidak Dilaksanakan</option></select></div>
                        <div class="col-md-6"><label class="form-label fw-semibold small text-secondary">Biaya (Rp)</label><input type="number" name="biaya" value="{{ old('biaya', $tat->biaya) }}" class="form-control py-2"></div>
                        <div class="col-12"><label class="form-label fw-semibold small text-secondary">Proses Hukum Lanjut</label><textarea name="proses_hukum_lanjut" class="form-control py-2 auto-resize" rows="2" placeholder="Keterangan...">{{ old('proses_hukum_lanjut', $tat->proses_hukum_lanjut) }}</textarea></div>
                        
                        <div class="col-12 mt-4">
                            <div class="bg-light p-3 rounded border">
                                <label class="form-label fw-semibold small text-secondary mb-2 d-block">Lampiran Tersimpan</label>
                                @foreach($tat->dokumentasi as $doc)
                                    <div class="card d-inline-block me-2 mb-2 shadow-sm text-center align-top" id="file-{{ $doc->id }}" style="width: 120px; font-size: 12px;">
                                        <div class="p-2 border-bottom text-truncate" title="{{ $doc->nama_file_asli }}">{{ Str::limit($doc->nama_file_asli, 12) }}</div>
                                        <button type="button" class="btn btn-link text-danger text-decoration-none w-100 p-1" onclick="markDel({{ $doc->id }})" style="font-size: 11px;">Hapus</button>
                                    </div>
                                @endforeach
                                <div id="del-container"></div>
                                <div class="mt-3">
                                    <label class="form-label fw-semibold small text-secondary">Upload File Baru</label>
                                    <input type="file" class="filepond" name="dokumentasi[]" multiple>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pb-5">
                <button type="button" onclick="window.location.reload()" class="btn btn-light border px-4 py-2">Reset Form</button>
                <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-2 fw-bold" :disabled="isUploading">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</main>
@endsection

@push('styles')
    @vite(['resources/css/filepond.css', 'resources/js/filepond.js'])
    <style>
        .form-control, .form-select { border-color: #ced4da; border-radius: 0.375rem; }
        .form-control:focus, .form-select:focus { border-color: #6c757d; box-shadow: 0 0 0 0.25rem rgba(108, 117, 125, 0.15); outline: none; }
        .ts-control { border: 1px solid #ced4da; border-radius: 0.375rem; padding: 0.5rem 0.75rem; background-color: #fff; }
        .ts-wrapper.focus .ts-control { border-color: #6c757d; box-shadow: 0 0 0 0.25rem rgba(108, 117, 125, 0.15); }
        .border-danger.rounded .ts-control { border-color: #dc3545; }
        /* FIX DROPDOWN & TEXTAREA */
        .ts-dropdown { z-index: 9999 !important; }
        textarea.auto-resize { resize: none; overflow-y: hidden; min-height: 80px; }
    </style>
@endpush

@push('scripts')
<script type="module">
    window.markDel = function(id) {
        const input = document.createElement('input'); input.type = 'hidden'; input.name = 'delete_files[]'; input.value = id;
        document.getElementById('del-container').appendChild(input);
        const el = document.getElementById('file-'+id);
        el.style.opacity = '0.3'; el.style.pointerEvents = 'none';
    }

    document.addEventListener('alpine:init', () => {
        Alpine.data('tatForm', () => ({
            tersangkaList: [], bbList: [], tsInstances: {}, isUploading: false,
            errors: @json($errors->toArray()),
            masterNarkotika: @json($masterNarkotika),

            init() {
                const oldTersangka = @json(old('tersangka', []));
                const oldBB = @json(old('barang_bukti', []));
                const dbTersangka = @json($tat->tersangka);
                const dbBB = @json($tat->barangBukti);

                if (oldTersangka.length > 0) {
                    oldTersangka.forEach(t => {
                        this.tersangkaList.push({
                            temp_id: 't_' + Math.random(),
                            nama: t.nama, nik: t.nik, jk: t.jk, usia: t.usia, pendidikan: t.pendidikan, pekerjaan: t.pekerjaan, no_telepon: t.no_telepon
                        });
                    });
                } else if (dbTersangka.length > 0) {
                    dbTersangka.forEach(t => {
                        this.tersangkaList.push({
                            temp_id: 't_' + t.id,
                            nama: t.nama_tersangka, nik: t.nik, jk: t.jenis_kelamin, usia: t.usia, pendidikan: t.pendidikan, pekerjaan: t.pekerjaan, no_telepon: t.no_telepon
                        });
                    });
                } else { this.addTersangka(); }

                if (oldBB.length > 0) {
                    oldBB.forEach(b => {
                        this.bbList.push({
                            temp_id: 'bb_' + Math.random(),
                            kategori: b.kategori,
                            narkotika_ids: b.narkotika_ids || [], 
                            nama_barang_bukti: b.nama_barang_bukti,
                            jumlah: b.jumlah,
                            // PERBAIKAN LOGIKA: Jika satuan kosong, cek kategori. 
                            satuan: b.satuan || (b.kategori === 'Narkotika' ? 'Gram' : '')
                        });
                    });
                } else if (dbBB.length > 0) {
                    dbBB.forEach(b => {
                        this.bbList.push({
                            temp_id: 'bb_' + b.id,
                            kategori: b.kategori,
                            narkotika_ids: b.narkotika_id ? [b.narkotika_id] : [],
                            nama_barang_bukti: b.nama_barang_non_narkotika,
                            jumlah: parseFloat(b.kuantitas),
                            satuan: b.satuan
                        });
                    });
                } else { this.addBB(); }

                this.initFilePond();
                this.$nextTick(() => { document.querySelectorAll('textarea.auto-resize').forEach(el => { el.style.height = 'auto'; el.style.height = el.scrollHeight + 'px'; }) });
            },
            
            getQuantityLabel() {
                if (this.bbList.length === 0) return 'Berat / Jumlah';
                const allNarkotika = this.bbList.every(bb => bb.kategori === 'Narkotika');
                return allNarkotika ? 'Berat' : 'Berat / Jumlah';
            },

            hasError(field, index, key) { const errorKey = `${field}.${index}.${key}`; return this.errors && this.errors[errorKey]; },
            getErrorMessage(field, index, key) { const errorKey = `${field}.${index}.${key}`; return this.errors[errorKey] ? this.errors[errorKey][0] : ''; },

            addTersangka() { this.tersangkaList.push({ temp_id: 't_'+Date.now(), nama: '', nik: '', jk: 'Laki-laki', usia: '', pendidikan: '', pekerjaan: '', no_telepon: '' }); },
            removeTersangka(i) { if(this.tersangkaList.length > 1) this.tersangkaList.splice(i, 1); },
            addBB() { this.bbList.push({ temp_id: 'bb_'+Date.now(), kategori: 'Narkotika', narkotika_ids: [], nama_barang_bukti: '', jumlah: '', satuan: 'Gram' }); },
            removeBB(i) { const id = this.bbList[i].temp_id; if(this.tsInstances[id]) this.tsInstances[id].destroy(); this.bbList.splice(i, 1); },
            
            resetBB(bb) {
                if(this.tsInstances[bb.temp_id]) this.tsInstances[bb.temp_id].destroy();
                bb.narkotika_ids = []; 
                bb.nama_barang_bukti = '';
                bb.satuan = ''; // RESET SATUAN
                this.$nextTick(() => this.initTS(document.getElementById('select_bb_'+bb.temp_id), bb));
            },

            initTS(el, bb) {
                if(!el || bb.kategori !== 'Narkotika') return; 
                const ts = new TomSelect(el, {
                    plugins: ['remove_button'], create: false, valueField: 'id', labelField: 'text', searchField: 'text',
                    options: this.masterNarkotika.map(n => ({id: n.id, text: n.nama_narkotika})), placeholder: 'Pilih Narkotika...',
                    dropdownParent: 'body' // FIX DROPDOWN
                });
                if (bb.narkotika_ids && bb.narkotika_ids.length > 0) ts.setValue(bb.narkotika_ids);
                ts.on('change', (val) => { bb.narkotika_ids = val; });
                this.tsInstances[bb.temp_id] = ts;
            },
            initFilePond() {
                const submitBtn = document.getElementById('btn-submit');
                const pond = FilePond.create(document.querySelector('input.filepond'), {
                    server: { process: '{{ route("upload.temp") }}', revert: '{{ route("revert.temp") }}', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}},
                    onprocessstart: () => { this.isUploading = true; submitBtn.innerHTML = 'Mengupload...'; },
                    onprocessfiles: () => { this.isUploading = false; submitBtn.innerHTML = 'Simpan Perubahan'; }
                });
            },
            submitForm(e) { if(this.isUploading) { alert('Tunggu upload selesai!'); return; } e.target.submit(); }
        }));
    });
</script>
@endpush