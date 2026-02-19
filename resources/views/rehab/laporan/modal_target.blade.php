{{-- Parent (rehabPage) mengontrol visibility via targetModalOpen --}}
<div x-show="targetModalOpen" 
     style="display: none;" 
     class="modal fade show" 
     tabindex="-1" 
     role="dialog"
     style="display: block; background-color: rgba(0,0,0,0.5);" 
     x-transition.opacity>

    {{-- Child (targetManager) mengontrol logic form --}}
    <div class="modal-dialog modal-lg modal-dialog-centered" 
         role="document" 
         @click.outside="targetModalOpen = false"
         x-data="targetManager"> 
        
        <div class="modal-content border-0 shadow-lg">
            
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-bullseye me-2"></i>Kelola Target Tahunan</h5>
                {{-- Tombol Close men-set variable milik Parent --}}
                <button type="button" class="btn-close btn-close-white btn-focus-none" @click="targetModalOpen = false"></button>
            </div>

            <div class="modal-body p-4 bg-light">
                
                {{-- Form Input --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                            <h6 class="fw-bold text-secondary m-0">
                                <i class="bi" :class="isEdit ? 'bi-pencil-square' : 'bi-plus-circle'"></i> 
                                <span x-text="isEdit ? 'Update Target' : 'Input Target Baru'"></span>
                            </h6>
                            <button type="button" class="btn btn-xs btn-light text-muted border btn-focus-none" x-show="isEdit" @click="resetForm()">
                                <i class="bi bi-x me-1"></i>Batal Edit
                            </button>
                        </div>
                        
                        <form action="{{ route('rehab.target.store') }}" method="POST">
                            @csrf
                            {{-- Logic Form menggunakan variable milik Child (targetManager) --}}
                            <div class="row g-3">
                                @if(auth()->user()->isAdmin())
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Satuan Kerja</label>
                                    <select name="satuan_kerja_id" class="form-select form-select-sm" required x-model="form.satker_id" :class="{'bg-light': isEdit}" :style="isEdit ? 'pointer-events: none;' : ''">
                                        <option value="">-- Pilih Satker --</option>
                                        @foreach($satuanKerjas as $sk) <option value="{{ $sk->id }}">{{ $sk->satuan_kerja }}</option> @endforeach
                                    </select>
                                    <input type="hidden" name="satuan_kerja_id" :value="form.satker_id" x-if="isEdit">
                                </div>
                                @endif
                                <div class="col-md-{{ auth()->user()->isAdmin() ? '6' : '12' }}">
                                    <label class="form-label small fw-bold text-muted">Tahun Target</label>
                                    <select name="tahun" class="form-select form-select-sm" required x-model="form.tahun" :class="{'bg-light': isEdit}" :style="isEdit ? 'pointer-events: none;' : ''">
                                        @foreach(range(date('Y'), date('Y')-2) as $y) <option value="{{ $y }}">{{ $y }}</option> @endforeach
                                        <option value="{{ date('Y')+1 }}">{{ date('Y')+1 }} (Tahun Depan)</option>
                                    </select>
                                    <input type="hidden" name="tahun" :value="form.tahun" x-if="isEdit">
                                </div>
                                <div class="col-md-4"><label class="form-label small fw-bold text-info">Target RJ</label><input type="number" name="target_rawat_jalan" class="form-control fw-bold text-info" min="0" required x-model="form.rj"></div>
                                <div class="col-md-4"><label class="form-label small fw-bold text-success">Target Pasca</label><input type="number" name="target_pasca_rehab" class="form-control fw-bold text-success" min="0" required x-model="form.pasca"></div>
                                <div class="col-md-4"><label class="form-label small fw-bold text-warning-emphasis">Target SKHPN</label><input type="number" name="target_skhpn" class="form-control fw-bold text-warning-emphasis" min="0" required x-model="form.skhpn"></div>
                                <div class="col-12 text-end pt-2">
                                    <button type="submit" class="btn btn-sm px-4 shadow-sm" :class="isEdit ? 'btn-warning text-dark' : 'btn-primary'">
                                        <i class="bi" :class="isEdit ? 'bi-pencil-square' : 'bi-save'"></i> <span x-text="isEdit ? 'Update Target' : 'Simpan Target'"></span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Tabel Daftar --}}
                <h6 class="fw-bold small text-muted mb-2">Daftar Target Tersimpan</h6>
                <div class="table-responsive bg-white border rounded shadow-sm" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-striped table-hover text-center mb-0 small align-middle">
                        <thead class="bg-dark text-white sticky-top">
                            <tr><th>Tahun</th>@if(auth()->user()->isAdmin())<th class="text-start">Satker</th>@endif<th>RJ</th><th>Pasca</th><th>SKHPN</th><th width="15%">Aksi</th></tr>
                        </thead>
                        <tbody>
                            @forelse($allTargets as $target)
                            <tr :class="{'table-warning': form.id === {{ $target->id }}}">
                                <td class="fw-bold">{{ $target->tahun }}</td>
                                @if(auth()->user()->isAdmin()) <td class="text-start text-truncate" style="max-width: 150px;">{{ $target->satuanKerja->satuan_kerja ?? '-' }}</td> @endif
                                <td class="text-info fw-bold">{{ number_format($target->target_rawat_jalan) }}</td>
                                <td class="text-success fw-bold">{{ number_format($target->target_pasca_rehab) }}</td>
                                <td class="text-warning-emphasis fw-bold">{{ number_format($target->target_skhpn) }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-light text-primary border-0" @click="edit({{ json_encode($target) }})" title="Edit"><i class="bi bi-pencil-square"></i></button>
                                        @if(!$target->has_laporan)
                                            <form action="{{ route('rehab.target.destroy', $target->id) }}" method="POST" onsubmit="return confirm('Yakin hapus?');" class="d-inline">@csrf @method('DELETE')<button class="btn btn-light text-danger border-0" title="Hapus"><i class="bi bi-trash"></i></button></form>
                                        @else
                                            <button type="button" class="btn btn-light text-muted border-0 cursor-not-allowed" title="Terkunci"><i class="bi bi-lock-fill"></i></button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="{{ auth()->user()->isAdmin() ? 6 : 5 }}" class="text-muted fst-italic py-4">Belum ada target.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-sm btn-secondary" @click="targetModalOpen = false">Tutup</button>
            </div>
        </div>
    </div>
</div>