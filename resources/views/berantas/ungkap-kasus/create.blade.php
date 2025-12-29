@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4 p-lg-5">
        
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10">
                
                {{-- HEADER --}}
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h3 mb-1 fw-bold text-dark">Tambah Ungkap Kasus</h1>
                        <p class="text-muted mb-0">Input Data Penindakan dan Ungkap Kasus Narkoba</p>
                    </div>
                    <a href="{{ route('berantas.ungkap-kasus.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold">Form Input Data</h5>
                    </div>

                    <div class="card-body p-4 p-lg-5">
                        {{-- ERROR GLOBAL DIHAPUS SESUAI PERMINTAAN --}}

                        <form action="{{ route('berantas.ungkap-kasus.store') }}" method="POST" enctype="multipart/form-data" id="form-create">
                            @csrf

                            {{-- DATA UTAMA --}}
                            <h6 class="text-uppercase text-secondary fw-bold small mb-3 border-bottom pb-2">Data Utama</h6>
                            <div class="row g-4 mb-5">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-secondary small">Nomor LKN <span class="text-danger">*</span></label>
                                    <input type="text" name="nomor_lkn" class="form-control @error('nomor_lkn') is-invalid @enderror" value="{{ old('nomor_lkn') }}" placeholder="Contoh: LKN/01/I/2025/BNN">
                                    @error('nomor_lkn') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold text-secondary small">Tanggal Kejadian <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_kejadian" class="form-control @error('tanggal_kejadian') is-invalid @enderror" value="{{ old('tanggal_kejadian') }}">
                                    @error('tanggal_kejadian') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-secondary small">Lokasi / TKP <span class="text-danger">*</span></label>
                                    <textarea name="alamat_tkp" class="form-control @error('alamat_tkp') is-invalid @enderror" rows="3" placeholder="Masukkan alamat lengkap TKP">{{ old('alamat_tkp') }}</textarea>
                                    @error('alamat_tkp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            {{-- TERSANGKA --}}
                            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                <h6 class="text-uppercase text-secondary fw-bold small mb-0">Daftar Tersangka</h6>
                                <button type="button" class="btn btn-sm btn-primary shadow-sm" id="btn-add-tersangka"><i class="bi bi-plus-lg"></i> Tambah Tersangka</button>
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
                                        @php $tersangkaList = old('tersangka', [['temp_id' => 'temp_0']]); @endphp
                                        @foreach($tersangkaList as $i => $tsk)
                                        <tr class="row-tersangka">
                                            <input type="hidden" name="tersangka[{{ $i }}][temp_id]" value="{{ $tsk['temp_id'] ?? 'temp_'.$i }}" class="input-temp-id">
                                            
                                            <td class="text-center">
                                                <label for="foto-{{ $i }}" style="cursor: pointer;">
                                                    <img src="{{ asset('assets/images/user-placeholder.png') }}" id="preview-{{ $i }}" class="img-thumbnail rounded-circle object-fit-cover" style="width: 60px; height: 60px;">
                                                </label>
                                                <input type="file" name="tersangka[{{ $i }}][foto]" id="foto-{{ $i }}" class="d-none" accept="image/*" onchange="previewImage(this, {{ $i }})">
                                                @error("tersangka.{$i}.foto") <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                            </td>

                                            <td>
                                                <input type="text" name="tersangka[{{ $i }}][nama]" 
                                                       class="form-control input-nama-tersangka @error("tersangka.{$i}.nama") is-invalid @enderror" 
                                                       value="{{ old("tersangka.{$i}.nama") }}" placeholder="Masukkan nama">
                                                @error("tersangka.{$i}.nama") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>

                                            <td>
                                                <select name="tersangka[{{ $i }}][jk]" class="form-select @error("tersangka.{$i}.jk") is-invalid @enderror">
                                                    <option value="Laki-Laki" @selected(old("tersangka.{$i}.jk") == 'Laki-Laki')>Laki-Laki</option>
                                                    <option value="Perempuan" @selected(old("tersangka.{$i}.jk") == 'Perempuan')>Perempuan</option>
                                                </select>
                                                @error("tersangka.{$i}.jk") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>

                                            <td>
                                                <input type="text" name="tersangka[{{ $i }}][tahap]" 
                                                       class="form-control @error("tersangka.{$i}.tahap") is-invalid @enderror" 
                                                       value="{{ old("tersangka.{$i}.tahap") }}" placeholder="Status hukum">
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
                                <button type="button" class="btn btn-sm btn-primary shadow-sm" id="btn-add-bb"><i class="bi bi-plus-lg"></i> Tambah BB</button>
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
                                        @php $bbList = old('barang_bukti', [['id' => null]]); @endphp
                                        @foreach($bbList as $x => $bb)
                                        <tr class="row-bb">
                                            <td>
                                                <select name="barang_bukti[{{ $x }}][pemilik_id]" class="form-select select-pemilik"></select>
                                                <input type="hidden" class="old-pemilik-val" value="{{ old("barang_bukti.{$x}.pemilik_id", 'kasus') }}">
                                            </td>
                                            
                                            <td>
                                                <input type="text" name="barang_bukti[{{ $x }}][jenis]" class="form-control @error("barang_bukti.{$x}.jenis") is-invalid @enderror" value="{{ old("barang_bukti.{$x}.jenis") }}" placeholder="Jenis barang">
                                                @error("barang_bukti.{$x}.jenis") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>
                                            
                                            <td>
                                                <input type="number" name="barang_bukti[{{ $x }}][jumlah]" class="form-control @error("barang_bukti.{$x}.jumlah") is-invalid @enderror" value="{{ old("barang_bukti.{$x}.jumlah") }}" placeholder="0">
                                                @error("barang_bukti.{$x}.jumlah") <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </td>
                                            
                                            <td>
                                                <input type="text" name="barang_bukti[{{ $x }}][satuan]" class="form-control @error("barang_bukti.{$x}.satuan") is-invalid @enderror" value="{{ old("barang_bukti.{$x}.satuan") }}" placeholder="Satuan">
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
                                <label class="form-label fw-bold h6 mb-1 text-dark"><i class="bi bi-cloud-arrow-up me-2"></i>Upload File</label>
                                <p class="text-muted small mb-3">Format: .jpg, .png, .pdf, .docx. Maks 10MB/file.</p>
                                <input type="file" class="filepond" name="dokumentasi[]" multiple data-allow-reorder="true" data-max-file-size="10MB" data-max-files="10">
                            </div>

                            {{-- FOOTER BUTTONS --}}
                            <div class="d-flex flex-column-reverse flex-lg-row justify-content-end gap-2 pt-3 border-top">
                                <button type="button" onclick="window.location.reload()" class="btn btn-light border text-secondary px-4">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                </button>
                                <button type="submit" id="btn-submit" class="btn btn-primary px-5 shadow-sm">
                                    <i class="bi bi-save me-1"></i> Simpan Data
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
    img[src=""] { display: none; }
</style>
@endpush

@push('scripts')
<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const inputElement = document.querySelector('input.filepond');
        const form = document.getElementById('form-create');
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
            files: [
                @if(old('dokumentasi'))
                    @foreach(old('dokumentasi') as $file) { source: '{{ $file }}', options: { type: 'local' } }, @endforeach
                @endif
            ],
            server: {
                process: { url: '{{ route('upload.temp') }}', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, onerror: () => setButtonState(false) },
                revert: { url: '{{ route('revert.temp') }}', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } },
                load: { url: '{{ route('load.temp') }}/?file=', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } },
            },
            onprocessstart: () => setButtonState(true, 'Mengupload...'),
            onprocessfiles: () => setButtonState(false)
        });

        form.addEventListener('submit', function(e) {
            const files = pond.getFiles();
            const isBusy = files.some(file => file.status !== 2 && file.status !== 5);
            if (isBusy) {
                e.preventDefault(); e.stopPropagation();
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'Upload Belum Selesai', 
                    text: 'Silakan tunggu proses upload selesai atau hapus file yang macet.', 
                    confirmButtonText: 'Mengerti' 
                });
            } else {
                setButtonState(true, 'Menyimpan...');
            }
        });

        let tIndex = {{ count(old('tersangka', [1])) + 1 }};
        let bbIndex = {{ count(old('barang_bukti', [1])) + 1 }};

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
            const html = `<tr class="row-bb"><td><select name="barang_bukti[${bbIndex}][pemilik_id]" class="form-select select-pemilik"></select><input type="hidden" class="old-pemilik-val" value="kasus"></td><td><input type="text" name="barang_bukti[${bbIndex}][jenis]" class="form-control" placeholder="Jenis barang"></td><td><input type="number" name="barang_bukti[${bbIndex}][jumlah]" class="form-control" placeholder="0"></td><td><input type="text" name="barang_bukti[${bbIndex}][satuan]" class="form-control" placeholder="Satuan"></td><td><button type="button" class="btn btn-danger btn-sm btn-remove-row"><i class="bi bi-trash"></i></button></td></tr>`;
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
</script>
@endpush