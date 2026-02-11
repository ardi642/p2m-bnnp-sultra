@extends('admin')

@section('content')
<main class="admin-main" x-data="tatForm">
    <div class="container-fluid p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Edit Data TAT</h4>
                <p class="text-secondary small mb-0">Update Data Tim Asesmen Terpadu</p>
            </div>
            <a href="{{ route('berantas.tat.index') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><strong>Periksa Kembali Inputan!</strong> File yang diupload telah disimpan sementara.</div>
                </div>
            </div>
        @endif

        <form action="{{ route('berantas.tat.update', $tat->id) }}" method="POST" enctype="multipart/form-data" id="form-tat">
            @csrf @method('PUT')
            
            {{-- CARD 1: DATA UTAMA --}}
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
                                               class="form-control py-2" :class="{'is-invalid': hasError('tersangka', index, 'usia')}" placeholder="Usia...">
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

                                    {{-- PEKERJAAN DARI CONSTANT --}}
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold text-secondary">Pekerjaan <span class="text-danger">*</span></label>
                                        <select :name="`tersangka[${index}][pekerjaan]`" x-model="t.pekerjaan" 
                                                class="form-select py-2" :class="{'is-invalid': hasError('tersangka', index, 'pekerjaan')}">
                                            <option value="" selected disabled>Pilih Pekerjaan...</option>
                                            @foreach(\App\Constants\Pekerjaan::ALL as $pj)
                                                <option value="{{ $pj }}">{{ $pj }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback" x-text="getErrorMessage('tersangka', index, 'pekerjaan')"></div>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold text-secondary">No Telepon</label>
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
                                                <input type="text" :name="`barang_bukti[${i}][nama_barang_bukti]`" x-model="bb.nama_barang_bukti" class="form-control py-2" :class="{'is-invalid': hasError('barang_bukti', i, 'nama_barang_bukti')}" placeholder="Ketik nama barang...">
                                                <div class="invalid-feedback" x-text="getErrorMessage('barang_bukti', i, 'nama_barang_bukti')"></div>
                                            </div>
                                        </td>
                                        <td class="align-top">
                                            <input type="number" step="0.0001" :name="`barang_bukti[${i}][jumlah]`" x-model="bb.jumlah" class="form-control py-2" :class="{'is-invalid': hasError('barang_bukti', i, 'jumlah')}" placeholder="0.00">
                                            <div class="invalid-feedback" x-text="getErrorMessage('barang_bukti', i, 'jumlah')"></div>
                                        </td>
                                        <td class="align-top">
                                            {{-- SATUAN NARKOTIKA (ENUM) --}}
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
                                            
                                            {{-- SATUAN NON-NARKOTIKA (STRING) --}}
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

            {{-- CARD 4: DETAIL KASUS (TIM HUKUM & MEDIS DINAMIS) --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold mb-4 text-dark border-bottom pb-2">Detail Kasus & Asesmen</h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Pasal Disangkakan</label>
                            <textarea name="pasal_disangkakan" class="form-control py-2 auto-resize" rows="2" 
                                      x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' } }" 
                                      x-init="resize()" @input="resize()"
                                      placeholder="Masukkan pasal yang disangkakan...">{{ old('pasal_disangkakan', $tat->pasal_disangkakan) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Instansi Pengirim</label>
                            <input type="text" name="instansi_pengirim" value="{{ old('instansi_pengirim', $tat->instansi_pengirim) }}" 
                                   class="form-control py-2" placeholder="Nama instansi pengirim...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Tgl Penangkapan</label>
                            <input type="date" name="tanggal_penangkapan" value="{{ old('tanggal_penangkapan', $tat->tanggal_penangkapan?->format('Y-m-d')) }}" 
                                   class="form-control py-2">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small text-secondary">Tgl Permohonan</label>
                            <input type="date" name="tanggal_permohonan" value="{{ old('tanggal_permohonan', $tat->tanggal_permohonan?->format('Y-m-d')) }}" 
                                   class="form-control py-2">
                        </div>

                        {{-- TIM HUKUM DINAMIS --}}
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold small text-secondary mb-0">Tim Hukum</label>
                                <button type="button" class="btn btn-xs btn-outline-primary" @click="addTimHukum">
                                    <i class="bi bi-plus"></i> Tambah
                                </button>
                            </div>
                            <template x-for="(th, idx) in timHukumList" :key="idx">
                                <div class="input-group mb-2">
                                    <input type="text" :name="`tim_hukum[${idx}][nama]`" x-model="th.nama" 
                                           class="form-control form-control-sm" placeholder="Nama Anggota Tim..." required>
                                    <select :name="`tim_hukum[${idx}][instansi]`" x-model="th.instansi" 
                                            class="form-select form-select-sm" style="max-width: 130px;" required>
                                        <option value="Polda">Polda</option>
                                        <option value="BNN">BNN</option>
                                        <option value="Kejaksaan">Kejaksaan</option>
                                        <option value="Kemenkumham">Kemenkumham</option>
                                        <option value="Masyarakat">Masyarakat</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-danger btn-sm" @click="removeTimHukum(idx)">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </template>
                            @error('tim_hukum.*.nama') <div class="text-danger small">Nama anggota tim hukum wajib diisi.</div> @enderror
                        </div>

                        {{-- TIM MEDIS DINAMIS --}}
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-semibold small text-secondary mb-0">Tim Medis</label>
                                <button type="button" class="btn btn-xs btn-outline-primary" @click="addTimMedis">
                                    <i class="bi bi-plus"></i> Tambah
                                </button>
                            </div>
                            <template x-for="(tm, idx) in timMedisList" :key="idx">
                                <div class="input-group mb-2">
                                    <input type="text" :name="`tim_medis[${idx}][nama]`" x-model="tm.nama" 
                                           class="form-control form-control-sm" placeholder="Nama Dokter/Tim Medis..." required>
                                    <button type="button" class="btn btn-outline-danger btn-sm" @click="removeTimMedis(idx)">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </template>
                            @error('tim_medis.*.nama') <div class="text-danger small">Nama tim medis wajib diisi.</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Lembaga Rehab</label>
                            <input type="text" name="lembaga_rehab" value="{{ old('lembaga_rehab', $tat->lembaga_rehab) }}" 
                                   class="form-control py-2" placeholder="Nama lembaga rehabilitasi...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Rekomendasi</label>
                            <select name="tindak_lanjut_rekomendasi" class="form-select py-2">
                                <option value="dilaksanakan" @selected(old('tindak_lanjut_rekomendasi', $tat->tindak_lanjut_rekomendasi)=='dilaksanakan')>Dilaksanakan</option>
                                <option value="tidak dilaksanakan" @selected(old('tindak_lanjut_rekomendasi', $tat->tindak_lanjut_rekomendasi)=='tidak dilaksanakan')>Tidak Dilaksanakan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-secondary">Biaya (Rp)</label>
                            <input type="number" name="biaya" value="{{ old('biaya', $tat->biaya) }}" 
                                   class="form-control py-2" placeholder="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small text-secondary">Proses Hukum Lanjut</label>
                            <textarea name="proses_hukum_lanjut" class="form-control py-2 auto-resize" rows="2" 
                                      x-data="{ resize() { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' } }" 
                                      x-init="resize()" @input="resize()"
                                      placeholder="Keterangan proses hukum selanjutnya...">{{ old('proses_hukum_lanjut', $tat->proses_hukum_lanjut) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- CARD 5: LAMPIRAN & DOKUMENTASI --}}
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
                                    <i class="bi bi-cloud-arrow-up me-2"></i>Upload File & Link Baru (Opsional)
                                </label>
                                
                                <div class="row g-3">
                                    <div class="col-12 mt-5">
                                        {{-- 1. KOTAK KHUSUS DOKUMENTASI LAMA --}}
                                        @php $oldFotos = $tat->dokumen->where('kategori', 'dokumentasi'); @endphp
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

                                        {{-- 2. KOTAK KHUSUS LAMPIRAN LAMA --}}
                                        @php $oldLampirans = $tat->dokumen->where('kategori', 'lampiran'); @endphp
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
                                                        <input type="text" class="form-control" :name="`dokumentasi_links[${index}][nama]`" placeholder="Nama Tautan / File" x-model="link.nama" required>
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
                                                        <input type="text" class="form-control" :name="`lampiran_links[${index}][nama]`" placeholder="Nama Tautan / File" x-model="link.nama" required>
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
                <button type="button" onclick="window.location.reload()" class="btn btn-light border px-4 py-2">Reset Form</button>
                <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-2 fw-bold">Simpan Perubahan</button>
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
        .border-danger-thick { border-color: #dc3545 !important; border-width: 2px !important; }
        .delete-overlay { display: flex; flex-direction: column; justify-content: center; align-items: center; }
    </style>
@endpush

@push('scripts')
<script type="module">

    document.addEventListener("DOMContentLoaded", function() {

        // 2. FILEPOND MANAGER
        const commonConfig = {
            uploadRoute: '{{ route('upload.temp') }}',
            revertRoute: '{{ route('revert.temp') }}',
            loadRoute:   '{{ route('load.temp') }}',
            csrfToken:   '{{ csrf_token() }}',
            submitBtnId: 'btn-submit'
        };

        if (window.FilePondManager) {
            
            // A. Init Dokumentasi Baru
            window.FilePondManager.create('#fp-dokumentasi', {
                ...commonConfig,
                maxSize: '10MB',
                // Existing Files disini hanya untuk file BARU yang gagal validasi saat submit, bukan file lama dari DB
                existingFiles: @json(old('dokumentasi', [])), 
            });

            // B. Init Lampiran Baru
            window.FilePondManager.create('#fp-lampiran', {
                ...commonConfig,
                maxSize: '10MB',
                existingFiles: @json(old('lampiran', [])), 
            });

            // C. Validasi Submit
            // PERBAIKAN: Pastikan ID ini cocok dengan <form id="form-tat">
            window.FilePondManager.attachFormSubmit('form-tat', 'btn-submit');

        } else {
            console.error("FilePondManager belum dimuat. Pastikan 'npm run build' atau 'npm run dev' berjalan.");
        }

    });

    document.addEventListener('alpine:init', () => {
        Alpine.data('tatForm', () => ({
            tersangkaList: [], bbList: [], timHukumList: [], timMedisList: [],
            tsInstances: {}, 
            errors: @json($errors->toArray()), 
            masterNarkotika: @json($masterNarkotika),

            init() { 
                const oldTersangka = @json(old('tersangka', []));
                const oldBB = @json(old('barang_bukti', []));
                const oldTH = @json(old('tim_hukum', []));
                const oldTM = @json(old('tim_medis', []));
                const dbTersangka = @json($tat->tersangka);
                const dbBB = @json($tat->barangBukti);
                const dbTH = @json($tat->tim_hukum);
                const dbTM = @json($tat->tim_medis);

                // Init Tersangka
                if (oldTersangka.length > 0) {
                    oldTersangka.forEach(t => {
                        this.tersangkaList.push({
                            temp_id: 't_' + Math.random(),
                            nama: t.nama || '', nik: t.nik || '', jk: t.jk || 'Laki-laki', usia: t.usia || '', pendidikan: t.pendidikan || '', pekerjaan: t.pekerjaan || '', no_telepon: t.no_telepon || ''
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
                } else if (dbBB.length > 0) {
                    dbBB.forEach(b => {
                        this.bbList.push({
                            temp_id: 'bb_' + b.id,
                            kategori: b.kategori,
                            narkotika_id: b.narkotika_id || '',
                            nama_barang_bukti: b.nama_barang_non_narkotika,
                            jumlah: parseFloat(b.kuantitas),
                            satuan_narkotika: b.satuan_narkotika || 'Gram',
                            satuan_non_narkotika: b.satuan_non_narkotika || ''
                        });
                    });
                } else { this.addBB(); }

                // Init Tim Hukum
                if (oldTH.length > 0) {
                    oldTH.forEach(t => this.timHukumList.push({ nama: t.nama, instansi: t.instansi }));
                } else if (Array.isArray(dbTH) && dbTH.length > 0) {
                    dbTH.forEach(t => this.timHukumList.push(t));
                }

                // Init Tim Medis
                if (oldTM.length > 0) {
                    oldTM.forEach(t => this.timMedisList.push({ nama: t.nama }));
                } else if (Array.isArray(dbTM) && dbTM.length > 0) {
                    dbTM.forEach(t => this.timMedisList.push(t));
                } 
                
                this.$nextTick(() => {
                    document.querySelectorAll('textarea.auto-resize').forEach(el => {
                        this.autoResize(el);
                    });
                });
            },
            
            autoResize(el) {
                el.style.height = 'auto';
                el.style.height = el.scrollHeight + 'px';
            },

            // --- FUNGSI HELPER TIM ---
            addTimHukum() { this.timHukumList.push({ nama: '', instansi: 'Polda' }); },
            removeTimHukum(i) { if(this.timHukumList.length > 0) this.timHukumList.splice(i, 1); },

            addTimMedis() { this.timMedisList.push({ nama: '' }); },
            removeTimMedis(i) { if(this.timMedisList.length > 0) this.timMedisList.splice(i, 1); },

            getQuantityLabel() {
                if (this.bbList.length === 0) return 'Berat / Jumlah';
                const allNarkotika = this.bbList.every(bb => bb.kategori === 'Narkotika');
                return allNarkotika ? 'Berat' : 'Berat / Jumlah';
            },

            hasError(field, index, key) { const errorKey = `${field}.${index}.${key}`; return this.errors && this.errors[errorKey]; },
            getErrorMessage(field, index, key) { const errorKey = `${field}.${index}.${key}`; return this.errors[errorKey] ? this.errors[errorKey][0] : ''; },

            addTersangka() { this.tersangkaList.push({ temp_id: 't_'+Date.now(), nama: '', nik: '', jk: 'Laki-laki', usia: '', pendidikan: '', pekerjaan: '', no_telepon: '' }); },
            removeTersangka(i) { if(this.tersangkaList.length > 1) this.tersangkaList.splice(i, 1); },
            
            addBB() { 
                this.bbList.push({ 
                    temp_id: 'bb_'+Date.now(), kategori: 'Narkotika', narkotika_id: '', 
                    nama_barang_bukti: '', jumlah: '', 
                    satuan_narkotika: 'Gram', satuan_non_narkotika: '' 
                }); 
            },
            removeBB(i) { const id = this.bbList[i].temp_id; if(this.tsInstances[id]) this.tsInstances[id].destroy(); this.bbList.splice(i, 1); },
            
            resetBB(bb) {
                if(this.tsInstances[bb.temp_id]) this.tsInstances[bb.temp_id].destroy();
                bb.narkotika_id = ''; 
                bb.nama_barang_bukti = '';
                bb.satuan_narkotika = 'Gram';
                bb.satuan_non_narkotika = '';
                this.$nextTick(() => this.initTS(document.getElementById('select_bb_'+bb.temp_id), bb));
            },

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
            addLink() {
                this.links.push({ nama: '', url: '' });
            },
            removeLink(index) {
                this.links.splice(index, 1);
            }
        }));
    });

    window.markForDeletion = function(id) {
        const cardInner = document.querySelector('#file-card-' + id + ' .file-card-inner');
        const overlay = cardInner.querySelector('.delete-overlay');
        const btnDelete = document.getElementById('btn-delete-' + id);
        const containerInputs = document.getElementById('delete-inputs-container');
        let input = document.getElementById('input-delete-' + id);
        
        if (input) { // Batal Hapus
            input.remove();
            overlay.classList.add('d-none');
            overlay.classList.remove('d-flex');
            cardInner.classList.remove('border-danger-thick');
            btnDelete.classList.remove('btn-secondary');
            btnDelete.classList.add('btn-outline-danger');
            btnDelete.innerHTML = 'Hapus';
        } else { // Tandai Hapus
            input = document.createElement('input');
            input.type = 'hidden'; 
            input.name = 'delete_files[]'; 
            input.value = id; 
            input.id = 'input-delete-' + id;
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