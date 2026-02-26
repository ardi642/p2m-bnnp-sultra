@extends('admin')

@section('content')
<main class="admin-main" x-data="tatForm">
    <div class="container-fluid p-4">
        
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Input Data TAT</h4>
                <p class="text-secondary small mb-0">Tambah Data Tim Asesmen Terpadu Baru</p>
            </div>
            <a href="{{ route('berantas.tat.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        {{-- ALERT ERROR --}}
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><strong>Periksa Kembali Inputan!</strong> File yang diupload telah disimpan sementara.</div>
                </div>
            </div>
        @endif

        {{-- FORM --}}
        <form action="{{ route('berantas.tat.store') }}" method="POST" enctype="multipart/form-data" id="form-tat">
            @csrf
            
            {{-- CARD 1: DATA UTAMA --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4 text-dark border-bottom pb-2">Informasi Umum</h5>
                    <div class="row g-3">
                        @if(Auth::user()->isAdmin())
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Satuan Kerja</label>
                            <select name="satuan_kerja_id" class="form-select py-2">
                                <option value="" selected disabled>Pilih Satuan Kerja...</option>
                                @foreach($satuanKerjas as $s) 
                                    <option value="{{ $s->id }}" {{ old('satuan_kerja_id') == $s->id ? 'selected' : '' }}>
                                        {{ $s->satuan_kerja }}
                                    </option> 
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">No. Register TAT <span class="text-danger">*</span></label>
                            <input type="text" name="no_register" value="{{ old('no_register') }}" 
                                   class="form-control py-2 @error('no_register') is-invalid @enderror" 
                                   placeholder="Contoh: REG-001/TAT/2025">
                            @error('no_register') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small text-secondary">Tanggal Pelaksanaan <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_pelaksanaan" value="{{ old('tanggal_pelaksanaan') }}"
                                   class="form-control py-2 @error('tanggal_pelaksanaan') is-invalid @enderror">
                            @error('tanggal_pelaksanaan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD 2: TERSANGKA --}}
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
                                        <label class="form-label small fw-semibold text-secondary">NIK</label>
                                        <input type="text" :name="`tersangka[${index}][nik]`" x-model="t.nik" 
                                               class="form-control py-2" :class="{'is-invalid': hasError('tersangka', index, 'nik')}"
                                               placeholder="16 Digit NIK...">
                                       <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'nik')"></div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold text-secondary">Jenis Kelamin</label>
                                        <select :name="`tersangka[${index}][jk]`" x-model="t.jk" class="form-select py-2">
                                            <option value="Laki-laki">Laki-laki</option>
                                            <option value="Perempuan">Perempuan</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold text-secondary">Usia <span class="text-danger">*</span></label>
                                        <input type="number" :name="`tersangka[${index}][usia]`" x-model="t.usia" 
                                               class="form-control py-2" :class="{'is-invalid': hasError('tersangka', index, 'usia')}" 
                                               placeholder="Contoh: 30">
                                        <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'usia')"></div>
                                    </div>
                                    
                                    {{-- PENDIDIKAN DARI CONSTANT --}}
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold text-secondary">Pendidikan <span class="text-danger">*</span></label>
                                        <select :name="`tersangka[${index}][pendidikan]`" x-model="t.pendidikan" 
                                                class="form-select py-2" :class="{'is-invalid': hasError('tersangka', index, 'pendidikan')}">
                                            <option value="" selected disabled>Pilih Pendidikan...</option>
                                            @foreach(\App\Constants\Pendidikan::ALL as $p)
                                                <option value="{{ $p }}">{{ $p }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'pendidikan')"></div>
                                    </div>

                                    {{-- PEKERJAAN DENGAN OPSI LAINNYA --}}
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold text-secondary">Pekerjaan <span class="text-danger">*</span></label>
                                        <select x-model="t.pekerjaan_select" 
                                                @change="if(t.pekerjaan_select !== 'Lainnya') t.pekerjaan = t.pekerjaan_select; else t.pekerjaan = ''"
                                                class="form-select py-2 mb-2" :class="{'is-invalid': hasError('tersangka', index, 'pekerjaan')}">
                                            <option value="" selected disabled>Pilih Pekerjaan...</option>
                                            <template x-for="p in pekerjaanList" :key="p">
                                                <option :value="p" x-text="p"></option>
                                            </template>
                                            <option value="Lainnya">Lainnya (Isi Manual)</option>
                                        </select>
                                        
                                        <div x-show="t.pekerjaan_select === 'Lainnya'" x-transition>
                                            <input type="text" class="form-control py-2" placeholder="Sebutkan pekerjaan..." 
                                                   x-model="t.pekerjaan" :class="{'is-invalid': hasError('tersangka', index, 'pekerjaan')}">
                                        </div>
                                        
                                        <input type="hidden" :name="`tersangka[${index}][pekerjaan]`" :value="t.pekerjaan">
                                        
                                        <div class="invalid-feedback d-block" x-show="hasError('tersangka', index, 'pekerjaan')" x-text="getErrorMessage('tersangka', index, 'pekerjaan')"></div>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold text-secondary">No Telepon</label>
                                        <input type="text" :name="`tersangka[${index}][no_telepon]`" x-model="t.no_telepon" 
                                               class="form-control py-2" :class="{'is-invalid': hasError('tersangka', index, 'no_telepon')}" 
                                               placeholder="08xxx...">
                                        <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'no_telepon')"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- CARD 3: BARANG BUKTI --}}
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
                                                <option value="Narkotika">Narkotika</option>
                                                <option value="Non-Narkotika">Non-Narkotika</option>
                                            </select>
                                        </td>
                                        <td class="align-top">
                                            <div x-show="bb.kategori === 'Narkotika'" class="w-100">
                                                <div wire:ignore :class="{'border border-danger rounded': hasError('barang_bukti', i, 'narkotika_id')}">
                                                    <select :id="'select_bb_' + bb.temp_id" :name="`barang_bukti[${i}][narkotika_id]`" x-init="initTS($el, bb)"></select>
                                                </div>
                                                <div class="text-danger small mt-1" x-show="hasError('barang_bukti', i, 'narkotika_id')" x-text="getErrorMessage('barang_bukti', i, 'narkotika_id')"></div>
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
                                                    <select :name="`barang_bukti[${i}][satuan_narkotika]`" x-model="bb.satuan_narkotika" class="form-select py-2" :class="{'is-invalid': hasError('barang_bukti', i, 'satuan_narkotika')}">
                                                        <option value="Gram">Gram</option>
                                                        <option value="Kg">Kg</option>
                                                        <option value="Ton">Ton</option>
                                                    </select>
                                                    <div class="invalid-feedback d-block" x-show="hasError('barang_bukti', i, 'satuan_narkotika')" x-text="getErrorMessage('barang_bukti', i, 'satuan_narkotika')"></div>
                                                </div>
                                            </template>
                                            
                                            <template x-if="bb.kategori === 'Non-Narkotika'">
                                                <div class="w-100">
                                                    <input type="text" :name="`barang_bukti[${i}][satuan_non_narkotika]`" x-model="bb.satuan_non_narkotika" 
                                                           class="form-control py-2" :class="{'is-invalid': hasError('barang_bukti', i, 'satuan_non_narkotika')}" placeholder="Masukkan satuan">
                                                    <div class="invalid-feedback d-block" x-show="hasError('barang_bukti', i, 'satuan_non_narkotika')" x-text="getErrorMessage('barang_bukti', i, 'satuan_non_narkotika')"></div>
                                                </div>
                                            </template>
                                        </td>
                                        <td class="text-center align-top">
                                            <button type="button" class="btn btn-sm text-danger" @click="removeBB(i)"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- CARD 4: DETAIL KASUS --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4 text-dark border-bottom pb-2">Detail Kasus & Asesmen</h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Pasal Disangkakan</label>
                            <textarea name="pasal_disangkakan" class="form-control py-2 auto-resize" rows="2" 
                                      x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' } }" 
                                      x-init="resize()" @input="resize()"
                                      placeholder="Masukkan pasal...">{{ old('pasal_disangkakan') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Instansi Pengirim</label>
                            <input type="text" name="instansi_pengirim" value="{{ old('instansi_pengirim') }}" 
                                   class="form-control py-2" placeholder="Nama instansi...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Tgl Penangkapan</label>
                            <input type="date" name="tanggal_penangkapan" value="{{ old('tanggal_penangkapan') }}" class="form-control py-2">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Tgl Permohonan</label>
                            <input type="date" name="tanggal_permohonan" value="{{ old('tanggal_permohonan') }}" class="form-control py-2">
                        </div>
                        
                        {{-- TIM HUKUM DINAMIS --}}
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold small text-secondary mb-0">Tim Hukum</label>
                                <button type="button" class="btn btn-xs btn-outline-primary" @click="addTimHukum"><i class="bi bi-plus"></i> Tambah</button>
                            </div>
                            <template x-for="(th, idx) in timHukumList" :key="idx">
                                <div class="input-group mb-2">
                                    <input type="text" :name="`tim_hukum[${idx}][nama]`" x-model="th.nama" 
                                           class="form-control form-control-sm" placeholder="Nama..." required>
                                    <select :name="`tim_hukum[${idx}][instansi]`" x-model="th.instansi" 
                                            class="form-select form-select-sm" style="max-width: 130px;" required>
                                        <option value="Polda">Polda</option>
                                        <option value="BNN">BNN</option>
                                        <option value="Kejaksaan">Kejaksaan</option>
                                        <option value="Kemenkumham">Kemenkumham</option>
                                        <option value="Masyarakat">Masyarakat</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-danger btn-sm" @click="removeTimHukum(idx)"><i class="bi bi-x"></i></button>
                                </div>
                            </template>
                            @error('tim_hukum.*.nama') <div class="text-danger small">Nama anggota tim hukum wajib diisi.</div> @enderror
                        </div>

                        {{-- TIM MEDIS DINAMIS --}}
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold small text-secondary mb-0">Tim Medis</label>
                                <button type="button" class="btn btn-xs btn-outline-primary" @click="addTimMedis"><i class="bi bi-plus"></i> Tambah</button>
                            </div>
                            <template x-for="(tm, idx) in timMedisList" :key="idx">
                                <div class="input-group mb-2">
                                    <input type="text" :name="`tim_medis[${idx}][nama]`" x-model="tm.nama" 
                                           class="form-control form-control-sm" placeholder="Nama Dokter/Medis..." required>
                                    <button type="button" class="btn btn-outline-danger btn-sm" @click="removeTimMedis(idx)"><i class="bi bi-x"></i></button>
                                </div>
                            </template>
                            @error('tim_medis.*.nama') <div class="text-danger small">Nama tim medis wajib diisi.</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Lembaga Rehab</label>
                            <input type="text" name="lembaga_rehab" value="{{ old('lembaga_rehab') }}" class="form-control py-2" placeholder="Nama lembaga...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Rekomendasi</label>
                            <select name="tindak_lanjut_rekomendasi" class="form-select py-2">
                                <option value="dilaksanakan">Dilaksanakan</option>
                                <option value="tidak dilaksanakan">Tidak Dilaksanakan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Biaya (Rp)</label>
                            <input type="number" name="biaya" value="{{ old('biaya', 0) }}" class="form-control py-2">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Proses Hukum Lanjut</label>
                            <textarea name="proses_hukum_lanjut" class="form-control py-2 auto-resize" rows="2" 
                                      x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' } }" 
                                      x-init="resize()" @input="resize()"
                                      placeholder="Keterangan proses...">{{ old('proses_hukum_lanjut') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD 5: LAMPIRAN --}}
            <div class="card shadow-sm border-0 mb-5">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="card-title mb-0 fw-bold text-primary">
                        <i class="bi bi-paperclip me-2"></i>Dokumentasi & Lampiran
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="bg-body-tertiary p-4 rounded-3 border border-dashed">
                        
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label fw-bold h6 mb-3 text-dark d-block border-bottom pb-2">
                                    <i class="bi bi-cloud-arrow-up me-2"></i>Upload File & Link (Opsional)
                                </label>
                                
                                <div class="row g-3">
                                    {{-- KIRI: DOKUMENTASI --}}
                                    <div class="col-12 col-md-6">
                                        <div class="bg-white p-3 rounded border h-100 d-flex flex-column shadow-sm">
                                            <label class="form-label fw-bold small text-primary mb-1">
                                                <i class="bi bi-folder2-open me-2"></i>Dokumentasi
                                            </label>
                                            
                                            <div class="mb-3">
                                                <p class="text-muted small mb-2" style="font-size: 0.75rem">Upload dokumentasi. Maksimal 10MB.</p>
                                                <input type="file" id="fp-dokumentasi" name="dokumentasi[]" multiple>
                                                @error('dokumentasi') <div class="text-danger small">{{ $message }}</div> @enderror
                                            </div>

                                            <hr class="border-secondary-subtle my-3">

                                            <div x-data="linkManager( {{ \Illuminate\Support\Js::from(array_values(old('dokumentasi_links', []))) }} )">
                                                <label class="form-label fw-bold small text-primary mb-2">
                                                    <i class="bi bi-link-45deg me-1"></i>Atau Tautkan Link
                                                </label>
                                                
                                                <template x-for="(link, index) in links" :key="index">
                                                    <div class="input-group mb-2 input-group-sm">
                                                        <input type="text" class="form-control" :name="`dokumentasi_links[${index}][nama]`" placeholder="Nama Tautan" x-model="link.nama" required>
                                                        <input type="url" class="form-control" :name="`dokumentasi_links[${index}][url]`" placeholder="https://" x-model="link.url" required>
                                                        <button type="button" class="btn btn-outline-danger" @click="removeLink(index)"><i class="bi bi-x"></i></button>
                                                    </div>
                                                </template>
                                                
                                                @error('dokumentasi_links.*') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                                                <button type="button" class="btn btn-xs btn-outline-primary dashed-border w-100 mt-1" @click="addLink()">
                                                    <i class="bi bi-plus-circle me-1"></i> Tambah Link
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- KANAN: LAMPIRAN --}}
                                    <div class="col-12 col-md-6">
                                        <div class="bg-white p-3 rounded border h-100 d-flex flex-column shadow-sm">
                                            <label class="form-label fw-bold small text-danger mb-1">
                                                <i class="bi bi-paperclip me-2"></i>Lampiran Pendukung
                                            </label>
                                            
                                            <div class="mb-3">
                                                <p class="text-muted small mb-2" style="font-size: 0.75rem">Upload file pendukung. Maksimal 10MB.</p>
                                                <input type="file" id="fp-lampiran" name="lampiran[]" multiple>
                                                @error('lampiran') <div class="text-danger small">{{ $message }}</div> @enderror
                                            </div>

                                            <hr class="border-secondary-subtle my-3">   

                                            <div x-data="linkManager( {{ \Illuminate\Support\Js::from(array_values(old('lampiran_links', []))) }} )">
                                                <label class="form-label fw-bold small text-danger mb-2">
                                                    <i class="bi bi-link-45deg me-1"></i>Atau Tautkan Link
                                                </label>
                                                
                                                <template x-for="(link, index) in links" :key="index">
                                                    <div class="input-group mb-2 input-group-sm">
                                                        <input type="text" class="form-control" :name="`lampiran_links[${index}][nama]`" placeholder="Nama Tautan" x-model="link.nama" required>
                                                        <input type="url" class="form-control" :name="`lampiran_links[${index}][url]`" placeholder="https://" x-model="link.url" required>
                                                        <button type="button" class="btn btn-outline-danger" @click="removeLink(index)"><i class="bi bi-x"></i></button>
                                                    </div>
                                                </template>

                                                @error('lampiran_links.*') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                                                <button type="button" class="btn btn-xs btn-outline-danger dashed-border w-100 mt-1" @click="addLink()">
                                                    <i class="bi bi-plus-circle me-1"></i> Tambah Link
                                                </button>
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
                <button type="button" onclick="window.location.reload()" class="btn btn-light border px-4 py-2">Reset Form</button>
                <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-2 fw-bold">Simpan Data</button>
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
        .ts-dropdown { z-index: 9999 !important; }
        textarea.auto-resize { resize: none; overflow-y: hidden; min-height: 80px; }
        .filepond--panel-root { background-color: #ffffff; border: 1px solid #dee2e6; }
        .border-dashed { border-style: dashed !important; border-width: 2px !important; }
    </style>
@endpush

@push('scripts')
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
                ...commonConfig, maxSize: '10MB', existingFiles: @json(old('dokumentasi', [])),
            });
            window.FilePondManager.create('#fp-lampiran', {
                ...commonConfig, maxSize: '10MB', existingFiles: @json(old('lampiran', [])),
            });
            window.FilePondManager.attachFormSubmit('form-tat', 'btn-submit');
        } else {
            console.error("FilePondManager belum dimuat.");
        }
    });

    document.addEventListener('alpine:init', () => {
        Alpine.data('tatForm', () => ({
            tersangkaList: [], bbList: [], timHukumList: [], timMedisList: [],
            tsInstances: {}, 
            errors: @json($errors->toArray()), 
            masterNarkotika: @json($masterNarkotika),
            pekerjaanList: @json(\App\Constants\Pekerjaan::ALL), // Daftar Pekerjaan

            init() { 
                const oldTersangka = @json(old('tersangka', []));
                const oldBB = @json(old('barang_bukti', []));
                const oldTH = @json(old('tim_hukum', []));
                const oldTM = @json(old('tim_medis', []));

                // Init Tersangka
                if (oldTersangka.length > 0) {
                    oldTersangka.forEach(t => {
                        const p_val = t.pekerjaan || '';
                        const p_select = this.pekerjaanList.includes(p_val) ? p_val : (p_val ? 'Lainnya' : '');

                        this.tersangkaList.push({
                            temp_id: 't_' + Math.random(),
                            nama: t.nama || '', nik: t.nik || '', jk: t.jk || 'Laki-laki', 
                            usia: t.usia || '', pendidikan: t.pendidikan || '', 
                            pekerjaan: p_val, pekerjaan_select: p_select, 
                            no_telepon: t.no_telepon || ''
                        });
                    });
                } else { this.addTersangka(); }

                // Init BB
                if (oldBB.length > 0) {
                    oldBB.forEach(b => {
                        this.bbList.push({
                            temp_id: 'bb_' + Math.random(),
                            kategori: b.kategori || 'Narkotika',
                            narkotika_id: b.narkotika_id || '',
                            nama_barang_bukti: b.nama_barang_bukti || '',
                            jumlah: b.jumlah || '',
                            satuan_narkotika: b.satuan_narkotika || 'Gram',
                            satuan_non_narkotika: b.satuan_non_narkotika || ''
                        });
                    });
                } else { this.addBB(); }

                // Init Tim
                if (oldTH.length > 0) {
                    oldTH.forEach(t => this.timHukumList.push({ nama: t.nama, instansi: t.instansi }));
                } 
                if (oldTM.length > 0) {
                    oldTM.forEach(t => this.timMedisList.push({ nama: t.nama }));
                } 
                
                this.$nextTick(() => {
                    document.querySelectorAll('textarea.auto-resize').forEach(el => this.autoResize(el));
                });
            },

            addTersangka() { 
                this.tersangkaList.push({ 
                    temp_id: 't_'+Date.now(), nama: '', nik: '', jk: 'Laki-laki', 
                    usia: '', pendidikan: '', pekerjaan: '', pekerjaan_select: '', no_telepon: '' 
                }); 
            },
            removeTersangka(i) { if(this.tersangkaList.length > 1) this.tersangkaList.splice(i, 1); },

            addBB() { 
                this.bbList.push({ 
                    temp_id: 'bb_'+Date.now(), kategori: 'Narkotika', narkotika_id: '', 
                    nama_barang_bukti: '', jumlah: '', satuan_narkotika: 'Gram', satuan_non_narkotika: '' 
                }); 
            },
            removeBB(i) { const id = this.bbList[i].temp_id; if(this.tsInstances[id]) this.tsInstances[id].destroy(); this.bbList.splice(i, 1); },
            
            resetBB(bb) {
                if(this.tsInstances[bb.temp_id]) this.tsInstances[bb.temp_id].destroy();
                bb.narkotika_id = ''; bb.nama_barang_bukti = ''; bb.satuan_narkotika = 'Gram'; bb.satuan_non_narkotika = '';
                this.$nextTick(() => this.initTS(document.getElementById('select_bb_'+bb.temp_id), bb));
            },

            addTimHukum() { this.timHukumList.push({ nama: '', instansi: 'Polda' }); },
            removeTimHukum(i) { this.timHukumList.splice(i, 1); },
            addTimMedis() { this.timMedisList.push({ nama: '' }); },
            removeTimMedis(i) { this.timMedisList.splice(i, 1); },

            getQuantityLabel() {
                if (this.bbList.length === 0) return 'Berat / Jumlah';
                return this.bbList.every(bb => bb.kategori === 'Narkotika') ? 'Berat' : 'Berat / Jumlah';
            },

            hasError(field, index, key) { const errorKey = `${field}.${index}.${key}`; return this.errors && this.errors[errorKey]; },
            getErrorMessage(field, index, key) { const errorKey = `${field}.${index}.${key}`; return this.errors[errorKey] ? this.errors[errorKey][0] : ''; },
            autoResize(el) { el.style.height = 'auto'; el.style.height = el.scrollHeight + 'px'; },

            initTS(el, bb) {
               if(!el || bb.kategori !== 'Narkotika') return; 
               const ts = new TomSelect(el, {
                   plugins: ['remove_button'], create: false, valueField: 'id', labelField: 'text', searchField: 'text',
                   options: this.masterNarkotika.map(n => ({id: n.id, text: n.nama_narkotika})), placeholder: 'Pilih Narkotika...',
                   dropdownParent: 'body', maxItems: 1
               });
               if (bb.narkotika_id) ts.setValue(bb.narkotika_id);
               ts.on('change', (val) => { bb.narkotika_id = val; });
               this.tsInstances[bb.temp_id] = ts;
            },
        }));

        Alpine.data('linkManager', (initialData = []) => ({
            links: Array.isArray(initialData) ? initialData : [], 
            addLink() { this.links.push({ nama: '', url: '' }); },
            removeLink(index) { this.links.splice(index, 1); }
        }));
    });
</script>
@endpush