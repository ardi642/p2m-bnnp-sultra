@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4 p-lg-5">
        
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1 fw-bold text-dark">Edit Ungkap Kasus</h1>
                        <p class="text-muted mb-0">Perbarui Data Penindakan dan Ungkap Kasus</p>
                    </div>
                    <a href="{{ route('berantas.ungkap-kasus.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold">Form Edit Data</h5>
                    </div>

                    <div class="card-body p-4 p-lg-5">
                        <form action="{{ route('berantas.ungkap-kasus.update', $kasus->id) }}" method="POST" enctype="multipart/form-data" id="form-edit">
                            @csrf
                            @method('PUT')

                            {{-- DATA UTAMA --}}
                            <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">Data Utama</h6>
                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-secondary small">Nomor LKN <span class="text-danger">*</span></label>
                                    <input type="text" name="nomor_lkn" class="form-control @error('nomor_lkn') is-invalid @enderror" value="{{ old('nomor_lkn', $kasus->nomor_lkn) }}" placeholder="Contoh: LKN/01/I/2025/BNN">
                                    @error('nomor_lkn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-secondary small">Tanggal Kejadian <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_kejadian" class="form-control @error('tanggal_kejadian') is-invalid @enderror" value="{{ old('tanggal_kejadian', $kasus->tanggal_kejadian->format('Y-m-d')) }}">
                                    @error('tanggal_kejadian') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-secondary small">Lokasi / TKP <span class="text-danger">*</span></label>
                                    <textarea name="alamat_tkp" class="form-control @error('alamat_tkp') is-invalid @enderror" rows="3" placeholder="Masukkan alamat lengkap TKP">{{ old('alamat_tkp', $kasus->alamat_tkp) }}</textarea>
                                    @error('alamat_tkp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            {{-- TERSANGKA --}}
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h6 class="text-uppercase text-secondary fw-bold small mb-0">Daftar Tersangka</h6>
                                <button type="button" class="btn btn-sm btn-primary shadow-sm" id="btn-add-tersangka">+ Tambah</button>
                            </div>
                            <div class="table-responsive mb-5">
                                <table class="table table-bordered mb-0 align-middle" id="table-tersangka">
                                    <thead class="bg-light text-secondary small text-uppercase">
                                        <tr>
                                            <th style="width: 100px">Foto</th>
                                            <th>Nama Tersangka <span class="text-danger">*</span></th>
                                            <th>Jenis Kelamin</th>
                                            <th>Status Tahap <span class="text-danger">*</span></th>
                                            <th style="width: 50px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $tersangkaData = old('tersangka') ?? $kasus->tersangka; @endphp
                                        @foreach($tersangkaData as $i => $tsk)
                                            @php
                                                $id = is_array($tsk) ? ($tsk['id'] ?? null) : $tsk->id;
                                                $nama = is_array($tsk) ? ($tsk['nama'] ?? '') : $tsk->nama_tersangka;
                                                $jk = is_array($tsk) ? ($tsk['jk'] ?? '') : $tsk->jenis_kelamin;
                                                $tahap = is_array($tsk) ? ($tsk['tahap'] ?? '') : $tsk->status_tahap;
                                                $tempId = is_array($tsk) ? ($tsk['temp_id'] ?? 'temp_'.$i) : $tsk->id;
                                                $foto = !is_array($tsk) ? $tsk->foto_tersangka : null; 
                                            @endphp

                                        <tr class="row-tersangka">
                                            <input type="hidden" name="tersangka[{{ $i }}][id]" value="{{ $id }}">
                                            <input type="hidden" name="tersangka[{{ $i }}][temp_id]" value="{{ $tempId }}" class="input-temp-id">
                                            
                                            <td class="text-center">
                                                <label for="foto-{{ $i }}" style="cursor: pointer;">
                                                    <img src="{{ $foto ? Storage::url($foto) : asset('assets/images/user-placeholder.png') }}" 
                                                         id="preview-{{ $i }}" 
                                                         class="img-thumbnail rounded-circle object-fit-cover" 
                                                         style="width: 60px; height: 60px;">
                                                </label>
                                                <input type="file" name="tersangka[{{ $i }}][foto]" id="foto-{{ $i }}" class="d-none" accept="image/*" onchange="previewImage(this, {{ $i }})">
                                                @error("tersangka.{$i}.foto") <div class="text-danger small">{{ $message }}</div> @enderror
                                            </td>

                                            <td>
                                                <input type="text" name="tersangka[{{ $i }}][nama]" 
                                                       class="form-control input-nama-tersangka @error("tersangka.{$i}.nama") is-invalid @enderror" 
                                                       value="{{ $nama }}" placeholder="Masukkan nama">
                                                @error("tersangka.{$i}.nama") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>

                                            <td>
                                                <select name="tersangka[{{ $i }}][jk]" class="form-select @error("tersangka.{$i}.jk") is-invalid @enderror">
                                                    <option value="Laki-Laki" @selected($jk == 'Laki-Laki')>Laki-Laki</option>
                                                    <option value="Perempuan" @selected($jk == 'Perempuan')>Perempuan</option>
                                                </select>
                                                @error("tersangka.{$i}.jk") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>

                                            <td>
                                                <input type="text" name="tersangka[{{ $i }}][tahap]" 
                                                       class="form-control @error("tersangka.{$i}.tahap") is-invalid @enderror" 
                                                       value="{{ $tahap }}" placeholder="Status hukum">
                                                @error("tersangka.{$i}.tahap") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>

                                            <td><button type="button" class="btn btn-danger btn-sm btn-remove-row"><i class="bi bi-trash"></i></button></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- BARANG BUKTI --}}
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h6 class="text-uppercase text-secondary fw-bold small mb-0">Daftar Barang Bukti</h6>
                                <button type="button" class="btn btn-sm btn-primary shadow-sm" id="btn-add-bb">+ Tambah BB</button>
                            </div>
                            <div class="table-responsive mb-5">
                                <table class="table table-bordered mb-0 align-middle" id="table-bb">
                                    <thead class="bg-light text-secondary small text-uppercase">
                                        <tr>
                                            <th>Pemilik</th>
                                            <th>Jenis Barang <span class="text-danger">*</span></th>
                                            <th>Jumlah <span class="text-danger">*</span></th>
                                            <th>Satuan <span class="text-danger">*</span></th>
                                            <th style="width: 50px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $bbData = old('barang_bukti') ?? $kasus->barangBukti; @endphp
                                        @foreach($bbData as $x => $bb)
                                            @php
                                                $bbId = is_array($bb) ? ($bb['id'] ?? null) : $bb->id;
                                                $pemilikId = is_array($bb) ? ($bb['pemilik_id'] ?? 'kasus') : ($bb->berantas_ungkap_tersangka_id ?? 'kasus');
                                                $jenis = is_array($bb) ? ($bb['jenis'] ?? '') : $bb->jenis_barang_bukti;
                                                $jumlah = is_array($bb) ? ($bb['jumlah'] ?? '') : $bb->jumlah_barang_bukti;
                                                $satuan = is_array($bb) ? ($bb['satuan'] ?? '') : $bb->satuan_barang_bukti;
                                            @endphp

                                        <tr class="row-bb">
                                            <input type="hidden" name="barang_bukti[{{ $x }}][id]" value="{{ $bbId }}">
                                            <td>
                                                <select name="barang_bukti[{{ $x }}][pemilik_id]" class="form-select select-pemilik"></select>
                                                <input type="hidden" class="old-pemilik-val" value="{{ $pemilikId }}">
                                            </td>
                                            <td>
                                                <input type="text" name="barang_bukti[{{ $x }}][jenis]" 
                                                       class="form-control @error("barang_bukti.{$x}.jenis") is-invalid @enderror" 
                                                       value="{{ $jenis }}" placeholder="Jenis barang">
                                                @error("barang_bukti.{$x}.jenis") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>
                                            <td>
                                                <input type="number" name="barang_bukti[{{ $x }}][jumlah]" 
                                                       class="form-control @error("barang_bukti.{$x}.jumlah") is-invalid @enderror" 
                                                       value="{{ $jumlah }}" placeholder="0">
                                                @error("barang_bukti.{$x}.jumlah") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>
                                            <td>
                                                <input type="text" name="barang_bukti[{{ $x }}][satuan]" 
                                                       class="form-control @error("barang_bukti.{$x}.satuan") is-invalid @enderror" 
                                                       value="{{ $satuan }}" placeholder="Satuan">
                                                @error("barang_bukti.{$x}.satuan") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>
                                            <td><button type="button" class="btn btn-danger btn-sm btn-remove-row"><i class="bi bi-trash"></i></button></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- DOKUMENTASI --}}
                            <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">Dokumentasi</h6>
                            <div class="bg-light p-4 rounded-3 border border-dashed mb-4">
                                @if($kasus->dokumentasi->count() > 0)
                                    <p class="small fw-bold text-secondary mb-2">File Tersimpan:</p>
                                    <div class="row g-3 mb-4" id="existing-files-container">
                                        @foreach($kasus->dokumentasi as $doc)
                                            <div class="col-6 col-md-4 col-lg-3 file-item" id="file-card-{{ $doc->id }}">
                                                <div class="card h-100 shadow-sm border border-secondary-subtle position-relative overflow-hidden file-card-inner transition-all">
                                                    <div class="delete-overlay position-absolute top-0 start-0 w-100 h-100 d-none flex-column justify-content-center align-items-center text-center" style="background-color: rgba(255, 255, 255, 0.9); z-index: 5;">
                                                        <div class="text-danger mb-1"><i class="bi bi-trash3-fill fs-1"></i></div>
                                                        <span class="text-danger fw-bold small text-uppercase">Akan Dihapus</span>
                                                    </div>
                                                    <div class="ratio ratio-16x9 bg-secondary bg-opacity-10 border-bottom d-flex align-items-center justify-content-center">
                                                        @if(Str::contains($doc->tipe_file, 'image'))
                                                            <img src="{{ Storage::url($doc->path_file) }}" class="object-fit-cover w-100 h-100">
                                                        @else
                                                            <i class="bi bi-file-earmark-text-fill display-4 text-secondary"></i>
                                                        @endif
                                                    </div>
                                                    <div class="card-body p-2 text-center">
                                                        <div class="small text-truncate fw-bold">{{ $doc->nama_file_asli }}</div>
                                                        <button type="button" id="btn-delete-{{ $doc->id }}" class="btn btn-outline-danger btn-sm w-100 py-0 mt-2" onclick="markForDeletion({{ $doc->id }})" style="font-size: 0.75rem;">Hapus</button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div id="delete-inputs-container"></div>
                                @endif
                                <p class="small fw-bold text-secondary mb-1 mt-2">Upload File Baru (Opsional):</p>
                                <input type="file" class="filepond" name="dokumentasi[]" multiple data-allow-reorder="true" data-max-file-size="10MB">
                            </div>

                            {{-- FOOTER BUTTONS --}}
                            <div class="d-flex flex-column-reverse flex-lg-row justify-content-end gap-2 pt-3 border-top">
                                <button type="button" onclick="window.location.reload()" class="btn btn-light border text-secondary px-4">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                </button>
                                <button type="submit" id="btn-submit" class="btn btn-primary px-5 shadow-sm">
                                    Simpan Perubahan
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
    .filepond--panel-root { background-color: #ffffff; border: 1px solid #dee2e6; }
    .border-dashed { border-style: dashed !important; border-width: 2px !important; }
    .transition-all { transition: all 0.3s ease; }
    .border-danger-subtle-thick { border-color: #dc3545 !important; border-width: 2px !important; }
</style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const inputElement = document.querySelector('input.filepond');
        const form = document.getElementById('form-edit');
        const submitBtn = document.getElementById('btn-submit');
        const originalBtnText = submitBtn.innerHTML;

        const setButtonState = (isLoading, text = null) => {
            if (isLoading) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + (text || 'Memproses...');
                submitBtn.classList.add('btn-secondary');
                submitBtn.classList.remove('btn-primary');
            } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                submitBtn.classList.add('btn-primary');
                submitBtn.classList.remove('btn-secondary');
            }
        };

        const pond = FilePond.create(inputElement, {
            acceptedFileTypes: ['image/jpeg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            allowMultiple: true,
            server: {
                process: { url: '{{ route('upload.temp') }}', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, onerror: () => setButtonState(false) },
                revert: { url: '{{ route('revert.temp') }}', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }
            },
            onprocessstart: () => setButtonState(true, 'Mengupload...'),
            onprocessfiles: () => setButtonState(false)
        });

        // CEGAT SUBMIT
        form.addEventListener('submit', function(e) {
            const files = pond.getFiles();
            const isBusy = files.some(file => file.status !== 2 && file.status !== 5);
            if (isBusy) {
                e.preventDefault(); e.stopPropagation();
                Swal.fire({ icon: 'warning', title: 'Upload Belum Selesai', text: 'Tunggu upload selesai.', confirmButtonText: 'Mengerti' });
            } else {
                setButtonState(true, 'Menyimpan...');
            }
        });

        // Dynamic Rows
        let tIndex = {{ count(old('tersangka') ?? $kasus->tersangka) + 50 }};
        let bbIndex = {{ count(old('barang_bukti') ?? $kasus->barangBukti) + 50 }};
        
        window.previewImage = function(input, index) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) { document.getElementById('preview-' + index).src = e.target.result; }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function updatePemilikDropdown() {
            const rows = document.querySelectorAll('.row-tersangka');
            const options = [{val: 'kasus', text: '-- Milik Kasus (Tanpa Tersangka) --'}];
            rows.forEach(row => {
                const nameInput = row.querySelector('.input-nama-tersangka');
                const tempIdInput = row.querySelector('.input-temp-id');
                const name = nameInput.value || 'Tersangka Baru';
                const id = tempIdInput.value;
                options.push({val: id, text: name});
            });
            document.querySelectorAll('.select-pemilik').forEach(select => {
                const hiddenVal = select.nextElementSibling; 
                const currentVal = select.value || (hiddenVal ? hiddenVal.value : 'kasus');
                select.innerHTML = '';
                options.forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt.val;
                    option.textContent = opt.text;
                    if(opt.val == currentVal) option.selected = true;
                    select.appendChild(option);
                });
            });
        }
        updatePemilikDropdown();

        document.getElementById('btn-add-tersangka').addEventListener('click', function() {
            const tempId = 'temp_' + Date.now();
            const html = `<tr class="row-tersangka"><input type="hidden" name="tersangka[${tIndex}][temp_id]" value="${tempId}" class="input-temp-id"><td class="text-center"><label for="foto-${tIndex}" style="cursor: pointer;"><img src="{{ asset('assets/images/user-placeholder.png') }}" id="preview-${tIndex}" class="img-thumbnail rounded-circle object-fit-cover" style="width: 60px; height: 60px;"></label><input type="file" name="tersangka[${tIndex}][foto]" id="foto-${tIndex}" class="d-none" accept="image/*" onchange="previewImage(this, ${tIndex})"></td><td><input type="text" name="tersangka[${tIndex}][nama]" class="form-control input-nama-tersangka" placeholder="Masukkan nama"></td><td><select name="tersangka[${tIndex}][jk]" class="form-select"><option value="Laki-Laki">Laki-Laki</option><option value="Perempuan">Perempuan</option></select></td><td><input type="text" name="tersangka[${tIndex}][tahap]" class="form-control" placeholder="Status hukum"></td><td><button type="button" class="btn btn-danger btn-sm btn-remove-row"><i class="bi bi-trash"></i></button></td></tr>`;
            document.querySelector('#table-tersangka tbody').insertAdjacentHTML('beforeend', html);
            tIndex++; updatePemilikDropdown();
        });

        document.getElementById('btn-add-bb').addEventListener('click', function() {
            const html = `<tr class="row-bb"><td><select name="barang_bukti[${bbIndex}][pemilik_id]" class="form-select select-pemilik"></select></td><td><input type="text" name="barang_bukti[${bbIndex}][jenis]" class="form-control" placeholder="Jenis barang"></td><td><input type="number" name="barang_bukti[${bbIndex}][jumlah]" class="form-control" placeholder="0"></td><td><input type="text" name="barang_bukti[${bbIndex}][satuan]" class="form-control" placeholder="Satuan"></td><td><button type="button" class="btn btn-danger btn-sm btn-remove-row"><i class="bi bi-trash"></i></button></td></tr>`;
            document.querySelector('#table-bb tbody').insertAdjacentHTML('beforeend', html);
            bbIndex++; updatePemilikDropdown();
        });

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-remove-row');
            if (!btn) return;
            const row = btn.closest('tr');
            if (row.closest('#table-tersangka')) {
                const suspectIdInput = row.querySelector('.input-temp-id');
                const suspectName = row.querySelector('.input-nama-tersangka').value || 'Tersangka ini';
                const suspectId = suspectIdInput ? suspectIdInput.value : null;
                if (suspectId) {
                    let isUsed = false;
                    document.querySelectorAll('.select-pemilik').forEach(select => {
                        if (select.value == suspectId) isUsed = true;
                    });
                    if (isUsed) {
                        Swal.fire({ icon: 'error', title: 'Gagal Hapus', text: `${suspectName} masih terikat dengan Barang Bukti.`, confirmButtonText: 'Mengerti' });
                        return;
                    }
                }
            }
            row.remove();
            if (row.closest('#table-tersangka')) updatePemilikDropdown();
        });

        document.addEventListener('input', function(e) { if(e.target.classList.contains('input-nama-tersangka')) updatePemilikDropdown(); });
    });

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
@endpush