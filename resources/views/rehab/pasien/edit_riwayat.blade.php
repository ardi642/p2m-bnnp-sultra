@extends('admin')

@section('content')
@php
    $ref = request('ref', 'show'); 
    $backUrl = $ref === 'index' ? route('rehab.pasien.index') : route('rehab.pasien.show', $riwayat->rehab_pasien_id);
    
    // Logic deteksi pekerjaan custom
    $oldKerja = old('pekerjaan', $riwayat->pekerjaan);
    $isManual = !empty($oldKerja) && !in_array($oldKerja, \App\Constants\Pekerjaan::ALL);
@endphp

<main class="admin-main">
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-1">Edit Riwayat Rehab</h4>
                <p class="text-secondary small mb-0">Pasien: <strong class="text-primary">{{ $riwayat->pasien->id_pasien }}</strong></p>
            </div>
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>

        <form action="{{ route('rehab.pasien.riwayat.update', ['id' => $riwayat->id, 'ref' => $ref]) }}" method="POST" id="form-edit-riwayat">
            @csrf @method('PUT')
            <div class="row g-4">
                {{-- IDENTITAS PASIEN (TERKUNCI) --}}
                <div class="col-12">
                    <div class="card shadow-sm border-0 bg-light opacity-75">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-secondary border-bottom pb-2 mb-4"><i class="bi bi-lock-fill me-2"></i>Identitas Dasar (Terkunci)</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">ID Pasien</label>
                                    <input type="text" class="form-control fw-bold bg-white" value="{{ $riwayat->pasien->id_pasien }}" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Nama Pasien</label>
                                    <input type="text" class="form-control bg-white" value="{{ $riwayat->pasien->nama_pasien }}" disabled>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Jenis Kelamin</label>
                                    <input type="text" class="form-control bg-white" value="{{ $riwayat->pasien->jenis_kelamin }}" disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- DATA KEDATANGAN EDIT --}}
                <div class="col-12">
                    <div class="card shadow-sm border-0 border-primary-subtle">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-primary border-bottom pb-2 mb-4"><i class="bi bi-pencil-square me-2"></i>Edit Data Kedatangan Ini</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Tanggal Masuk Rehab <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_rehab" value="{{ old('tanggal_rehab', $riwayat->tanggal_rehab->format('Y-m-d')) }}" class="form-control py-2 @error('tanggal_rehab') is-invalid @enderror">
                                    @error('tanggal_rehab') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Sumber Pasien <span class="text-danger">*</span></label>
                                    <select name="sumber_pasien" class="form-select py-2 @error('sumber_pasien') is-invalid @enderror">
                                        @foreach(\App\Constants\SumberPasien::ALL as $s)
                                            <option value="{{ $s }}" {{ old('sumber_pasien', $riwayat->sumber_pasien) == $s ? 'selected' : '' }}>{{ $s }}</option>
                                        @endforeach
                                    </select>
                                    @error('sumber_pasien') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Pendidikan <span class="text-danger">*</span></label>
                                    <select name="pendidikan" class="form-select py-2 @error('pendidikan') is-invalid @enderror">
                                        @foreach(\App\Constants\Pendidikan::ALL as $p) 
                                            <option value="{{ $p }}" {{ old('pendidikan', $riwayat->pendidikan) == $p ? 'selected' : '' }}>{{ $p }}</option> 
                                        @endforeach
                                    </select>
                                    @error('pendidikan') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                {{-- Pekerjaan (Alpine JS) --}}
                                <div class="col-md-6" x-data="{ manual: {{ $isManual ? 'true' : 'false' }} }">
                                    <label class="form-label small fw-semibold">Pekerjaan <span class="text-danger">*</span></label>
                                    
                                    <select x-bind:name="manual ? '' : 'pekerjaan'" 
                                            @change="if($event.target.value === 'Lainnya') manual = true" 
                                            class="form-select py-2 @error('pekerjaan') is-invalid @enderror">
                                        @foreach(\App\Constants\Pekerjaan::ALL as $p) 
                                            <option value="{{ $p }}" {{ $oldKerja == $p ? 'selected' : '' }}>{{ $p }}</option> 
                                        @endforeach
                                        <option value="Lainnya" {{ $isManual ? 'selected' : '' }}>Lainnya (Isi Manual)</option>
                                    </select>
                                    
                                    <div x-show="manual" x-cloak class="mt-2 input-group" x-transition>
                                        <input type="text" x-bind:name="manual ? 'pekerjaan' : ''" 
                                               class="form-control border-primary" 
                                               placeholder="Ketik pekerjaan manual..." 
                                               value="{{ $isManual ? $oldKerja : '' }}">
                                        <button type="button" class="btn btn-outline-secondary" @click="manual = false; $el.previousElementSibling.value = '';">
                                            <i class="bi bi-x"></i> Batal
                                        </button>
                                    </div>
                                    @error('pekerjaan') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-md-6" wire:ignore>
                                    <label class="form-label small fw-semibold">Jenis Narkotika yang Digunakan <span class="text-danger">*</span></label>
                                    <select id="select-narko" name="narkotika_ids[]" multiple placeholder="Pilih satu atau lebih..." class="@error('narkotika_ids') is-invalid @enderror">
                                        @php $selectedNarko = $riwayat->narkotika->pluck('id')->toArray(); @endphp
                                        @foreach($masterNarkotika as $n) 
                                            <option value="{{ $n->id }}" {{ in_array($n->id, old('narkotika_ids', $selectedNarko)) ? 'selected' : '' }}>{{ $n->nama_narkotika }}</option> 
                                        @endforeach
                                    </select>
                                    @error('narkotika_ids') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- UPLOAD FILE DOKUMEN EDIT --}}
                <div class="col-12">
                    <div class="bg-light p-4 rounded-3 border border-dashed">
                        <label class="form-label fw-bold h6 mb-3 text-dark d-block border-bottom pb-2">
                            <i class="bi bi-cloud-arrow-up me-2"></i>Kelola File & Link
                        </label>
                        <div class="row g-3">
                            
                            {{-- KOLOM KIRI: DOKUMENTASI --}}
                            <div class="col-12 col-md-6">
                                {{-- FOTO TERSIMPAN --}}
                                @php $oldFotos = $riwayat->dokumen->where('kategori', 'dokumentasi'); @endphp
                                @if($oldFotos->count() > 0)
                                    <div class="card bg-white border border-dashed mb-3 shadow-sm">
                                        <div class="card-body">
                                            <h6 class="fw-bold text-primary mb-3"><i class="bi bi-images me-2"></i>Dokumentasi Tersimpan</h6>
                                            <div class="row g-2">
                                                @foreach($oldFotos as $doc)
                                                    @php 
                                                        $isMarkedDeleted = old('delete_files') && in_array($doc->id, old('delete_files')); 
                                                        $isImage = !$doc->is_link && in_array(strtolower(pathinfo($doc->path_file, PATHINFO_EXTENSION)), ['jpg','jpeg','png','webp','gif']);
                                                    @endphp
                                                    <div class="col-6 file-item" id="file-card-{{ $doc->id }}">
                                                        <div class="card h-100 border-secondary-subtle file-card-inner overflow-hidden {{ $isMarkedDeleted ? 'border-danger border-2' : '' }}">
                                                            
                                                            {{-- Preview Area (Gambar / Ikon) --}}
                                                            <div class="position-relative border-bottom bg-light d-flex justify-content-center align-items-center" style="height: 120px;">
                                                                {{-- Overlay Hapus - Hanya menutupi area gambar --}}
                                                                <div class="delete-overlay position-absolute w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" style="background-color: rgba(255,255,255,0.85); z-index:5;">
                                                                    <i class="bi bi-trash3-fill text-danger" style="font-size: 2.5rem;"></i>
                                                                </div>

                                                                @if($isImage)
                                                                    <img src="{{ Storage::disk($doc->disk ?? 'public')->url($doc->path_file) }}" class="w-100 h-100" style="object-fit: cover;" alt="Preview">
                                                                @else
                                                                    <i class="bi {{ $doc->is_link ? 'bi-link-45deg text-primary' : 'bi-file-earmark-text text-secondary' }}" style="font-size: 3rem;"></i>
                                                                @endif
                                                            </div>

                                                            {{-- Card Body (Aman dari overlay transparan) --}}
                                                            <div class="card-body p-2 text-center file-card-body {{ $isMarkedDeleted ? 'bg-danger bg-opacity-10' : 'bg-white' }}" style="z-index: 10;">
                                                                <div class="small text-truncate fw-bold mb-2" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</div>
                                                                <button type="button" id="btn-delete-{{ $doc->id }}" class="btn btn-sm w-100 {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" onclick="markForDeletion({{ $doc->id }})">
                                                                    @if($isMarkedDeleted) Batal Hapus @else Hapus @endif
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- TAMBAH FOTO BARU (TIDAK MEMANJANG) --}}
                                <div class="bg-white p-3 rounded border shadow-sm">
                                    <label class="form-label fw-bold small text-primary mb-1">Tambah Dokumentasi Baru</label>
                                    <div class="mb-3"><input type="file" id="fp-dokumentasi" name="dokumentasi[]" multiple></div>
                                    <hr class="border-secondary-subtle my-3">
                                    <div x-data="linkManager( {{ \Illuminate\Support\Js::from(array_values(old('dokumentasi_links', []))) }} )">
                                        <label class="form-label fw-bold small text-primary mb-2">Tautkan Link Baru</label>
                                        <template x-for="(link, index) in links" :key="index">
                                            <div class="input-group mb-2 input-group-sm">
                                                <input type="text" class="form-control" :name="`dokumentasi_links[${index}][nama]`" placeholder="Nama" x-model="link.nama">
                                                <input type="url" class="form-control" :name="`dokumentasi_links[${index}][url]`" placeholder="https://" x-model="link.url">
                                                <button type="button" class="btn btn-outline-danger" @click="removeLink(index)"><i class="bi bi-x"></i></button>
                                            </div>
                                        </template>
                                        <button type="button" class="btn btn-xs btn-outline-primary w-100 mt-1" @click="addLink()">Tambah Link</button>
                                    </div>
                                </div>
                            </div>

                            {{-- KOLOM KANAN: LAMPIRAN --}}
                            <div class="col-12 col-md-6">
                                {{-- LAMPIRAN TERSIMPAN --}}
                                @php $oldLampirans = $riwayat->dokumen->where('kategori', 'lampiran'); @endphp
                                @if($oldLampirans->count() > 0)
                                    <div class="card bg-white border border-dashed mb-3 shadow-sm">
                                        <div class="card-body">
                                            <h6 class="fw-bold text-danger mb-3"><i class="bi bi-paperclip me-2"></i>Lampiran Tersimpan</h6>
                                            <div class="row g-2">
                                                @foreach($oldLampirans as $doc)
                                                    @php 
                                                        $isMarkedDeleted = old('delete_files') && in_array($doc->id, old('delete_files')); 
                                                        $isImage = !$doc->is_link && in_array(strtolower(pathinfo($doc->path_file, PATHINFO_EXTENSION)), ['jpg','jpeg','png','webp','gif']);
                                                    @endphp
                                                    <div class="col-6 file-item" id="file-card-{{ $doc->id }}">
                                                        <div class="card h-100 border-secondary-subtle file-card-inner overflow-hidden {{ $isMarkedDeleted ? 'border-danger border-2' : '' }}">
                                                            
                                                            {{-- Preview Area (Gambar / Ikon) --}}
                                                            <div class="position-relative border-bottom bg-light d-flex justify-content-center align-items-center" style="height: 120px;">
                                                                {{-- Overlay Hapus - Hanya menutupi area gambar --}}
                                                                <div class="delete-overlay position-absolute w-100 h-100 {{ $isMarkedDeleted ? 'd-flex' : 'd-none' }} flex-column justify-content-center align-items-center text-center" style="background-color: rgba(255,255,255,0.85); z-index:5;">
                                                                    <i class="bi bi-trash3-fill text-danger" style="font-size: 2.5rem;"></i>
                                                                </div>

                                                                @if($isImage)
                                                                    <img src="{{ Storage::disk($doc->disk ?? 'public')->url($doc->path_file) }}" class="w-100 h-100" style="object-fit: cover;" alt="Preview">
                                                                @else
                                                                    <i class="bi {{ $doc->is_link ? 'bi-link-45deg text-primary' : (Str::contains($doc->tipe_file, 'pdf') ? 'bi-file-pdf text-danger' : 'bi-file-earmark-text text-secondary') }}" style="font-size: 3rem;"></i>
                                                                @endif
                                                            </div>

                                                            {{-- Card Body (Aman dari overlay transparan) --}}
                                                            <div class="card-body p-2 text-center file-card-body {{ $isMarkedDeleted ? 'bg-danger bg-opacity-10' : 'bg-white' }}" style="z-index: 10;">
                                                                <div class="small text-truncate fw-bold mb-2" title="{{ $doc->nama_file_asli }}">{{ $doc->nama_file_asli }}</div>
                                                                <button type="button" id="btn-delete-{{ $doc->id }}" class="btn btn-sm w-100 {{ $isMarkedDeleted ? 'btn-secondary' : 'btn-outline-danger' }}" onclick="markForDeletion({{ $doc->id }})">
                                                                    @if($isMarkedDeleted) Batal Hapus @else Hapus @endif
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- TAMBAH LAMPIRAN BARU (TIDAK MEMANJANG) --}}
                                <div class="bg-white p-3 rounded border shadow-sm">
                                    <label class="form-label fw-bold small text-danger mb-1">Tambah Lampiran Baru</label>
                                    <div class="mb-3"><input type="file" id="fp-lampiran" name="lampiran[]" multiple></div>
                                    <hr class="border-secondary-subtle my-3">
                                    <div x-data="linkManager( {{ \Illuminate\Support\Js::from(array_values(old('lampiran_links', []))) }} )">
                                        <label class="form-label fw-bold small text-danger mb-2">Tautkan Link Baru</label>
                                        <template x-for="(link, index) in links" :key="index">
                                            <div class="input-group mb-2 input-group-sm">
                                                <input type="text" class="form-control" :name="`lampiran_links[${index}][nama]`" placeholder="Nama" x-model="link.nama">
                                                <input type="url" class="form-control" :name="`lampiran_links[${index}][url]`" placeholder="https://" x-model="link.url">
                                                <button type="button" class="btn btn-outline-danger" @click="removeLink(index)"><i class="bi bi-x"></i></button>
                                            </div>
                                        </template>
                                        <button type="button" class="btn btn-xs btn-outline-danger w-100 mt-1" @click="addLink()">Tambah Link</button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    
                    {{-- Input hidden untuk file yang dihapus --}}
                    <div id="delete-inputs-container">
                        @if(old('delete_files'))
                            @foreach(old('delete_files') as $deletedId)
                                <input type="hidden" name="delete_files[]" value="{{ $deletedId }}" id="input-delete-{{ $deletedId }}">
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>

            {{-- TOMBOL SUBMIT DIBUNGKUS KOTAK PUTIH UTUH AGAR TIDAK BLEEDING --}}
            <div class="bg-white p-3 rounded-3 border d-flex justify-content-end gap-2 mt-4 shadow-sm">
                <button type="button" onclick="window.location.reload();" class="btn btn-light border px-4 py-2 shadow-sm">Reset</button>
                <button type="submit" id="btn-submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</main>
@endsection

@push('styles')
    @vite(['resources/css/filepond.css', 'resources/js/filepond.js'])
    <style>
        .ts-control { border: 1px solid #ced4da; border-radius: 0.375rem; padding: 0.5rem 0.75rem; }
        .ts-wrapper.focus .ts-control { border-color: #6c757d; box-shadow: 0 0 0 0.25rem rgba(108, 117, 125, 0.15); }
        .border-dashed { border: 1px dashed #ced4da !important; }
        .border-danger-thick { border-color: #dc3545 !important; border-width: 2px !important; }
    </style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() { 
        new TomSelect('#select-narko', { plugins: ['remove_button', 'clear_button'], create: false }); 

        if (window.FilePondManager) {
            const commonConfig = { 
                uploadRoute: '{{ route('upload.temp') }}', 
                revertRoute: '{{ route('revert.temp') }}', 
                loadRoute: '{{ route('load.temp') }}', 
                csrfToken: '{{ csrf_token() }}', 
                submitBtnId: 'btn-submit' 
            };
            window.FilePondManager.create('#fp-dokumentasi', { 
                ...commonConfig, 
                maxSize: '10MB', 
                existingFiles: (@json(old('dokumentasi')) || []).filter(Boolean) 
            });
            window.FilePondManager.create('#fp-lampiran', { 
                ...commonConfig, 
                maxSize: '10MB', 
                existingFiles: (@json(old('lampiran')) || []).filter(Boolean) 
            });
            window.FilePondManager.attachFormSubmit('form-edit-riwayat', 'btn-submit');
        }
    });

    document.addEventListener('alpine:init', () => {
        Alpine.data('linkManager', (initialData = []) => ({
            links: Array.isArray(initialData) ? initialData : [], 
            addLink() { this.links.push({ nama: '', url: '' }); },
            removeLink(index) { this.links.splice(index, 1); }
        }));
    });

    window.markForDeletion = function(id) {
        const cardInner = document.querySelector('#file-card-' + id + ' .file-card-inner');
        const overlay = cardInner.querySelector('.delete-overlay');
        const cardBody = cardInner.querySelector('.file-card-body');
        const btnDelete = document.getElementById('btn-delete-' + id);
        const containerInputs = document.getElementById('delete-inputs-container');
        
        if (!overlay.classList.contains('d-none')) {
            // Proses Batalkan Hapus (Kembali Normal)
            overlay.classList.add('d-none'); 
            overlay.classList.remove('d-flex');
            cardInner.classList.remove('border-danger', 'border-2');
            cardBody.classList.remove('bg-danger', 'bg-opacity-10');
            cardBody.classList.add('bg-white');
            btnDelete.classList.remove('btn-secondary'); 
            btnDelete.classList.add('btn-outline-danger');
            btnDelete.innerHTML = 'Hapus';
            const input = document.getElementById('input-delete-' + id);
            if(input) input.remove();
        } else {
            // Proses Tandai Hapus (Jadi Merah)
            overlay.classList.remove('d-none'); 
            overlay.classList.add('d-flex');
            cardInner.classList.add('border-danger', 'border-2');
            cardBody.classList.remove('bg-white');
            cardBody.classList.add('bg-danger', 'bg-opacity-10');
            btnDelete.classList.remove('btn-outline-danger'); 
            btnDelete.classList.add('btn-secondary');
            btnDelete.innerHTML = 'Batal Hapus';
            const input = document.createElement('input');
            input.type = 'hidden'; 
            input.name = 'delete_files[]'; 
            input.value = id; 
            input.id = 'input-delete-' + id;
            containerInputs.appendChild(input);
        }
    };
</script>
@endpush