@extends('admin')

@section('content')
<main class="admin-main">
    <div class="container-fluid p-4 p-lg-5">
        
        @if(session('success'))
            <div class="alert alert-success border-0 shadow-sm mb-4 py-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill text-success fs-3 me-3"></i>
                    <div>
                        <div class="fw-bold fs-5 text-success">{{ session('success') }}</div>
                        <div class="text-muted small mt-1">Sistem berhasil memproses data rekam medis pasien.</div>
                    </div>
                </div>
            </div>
        @endif

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 shadow-lg mb-4" style="background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white;">
                    <div class="card-body p-5 position-relative overflow-hidden">
                        <i class="bi bi-person-vcard position-absolute opacity-10" style="font-size: 15rem; right: -2rem; top: -3rem;"></i>
                        
                        <div class="d-flex justify-content-between align-items-start position-relative" style="z-index: 2;">
                            <div>
                                <div class="mb-2 text-uppercase fw-bold text-info" style="letter-spacing: 2px; font-size: 0.8rem;">Kartu Identitas Pasien Rehabilitasi</div>
                                <h1 class="display-4 fw-bolder mb-0 font-monospace text-warning">{{ $pasien->no_rekam_medis }}</h1>
                            </div>
                            @if (auth()->user()->hasRole(['operator_satker', 'operator_rehab', 'admin']))
                                <a href="{{ route('rehab.pasien.edit', ['pasien' => $pasien->id, 'ref' => 'show']) }}" class="btn btn-outline-light btn-sm"><i class="bi bi-pencil-square me-1"></i> Edit Identitas</a>
                            @endif
                        </div>
                        
                        <hr class="border-light opacity-25 my-4">
                        
                        <div class="row g-4 position-relative" style="z-index: 2;">
                            <div class="col-md-4">
                                <div class="small text-white-50 text-uppercase fw-bold mb-1">Nama Pasien / Inisial</div>
                                <div class="fs-5 fw-bold">{{ $pasien->nama_pasien }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-white-50 text-uppercase fw-bold mb-1">Jenis Kelamin</div>
                                <div class="fs-5"><i class="bi bi-gender-ambiguous me-2 text-info"></i>{{ $pasien->jenis_kelamin }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="small text-white-50 text-uppercase fw-bold mb-1">Satuan Kerja</div>
                                <div class="fs-5">{{ $pasien->satuanKerja->satuan_kerja ?? '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="card-title fw-bold text-dark m-0"><i class="bi bi-clock-history me-2"></i>Histori Perjalanan Rehabilitasi</h5>
                        <a href="{{ route('rehab.pasien.index') }}" class="btn btn-outline-secondary btn-sm px-4">Kembali</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 text-center text-nowrap">
                                <thead class="bg-light">
                                    <tr class="small text-uppercase text-secondary">
                                        <th class="py-3">No</th>
                                        <th class="py-3 text-start">Tgl Kedatangan</th>
                                        <th class="py-3">Usia Saat Itu</th>
                                        <th class="py-3 text-start">Pekerjaan</th>
                                        <th class="py-3 text-start">Pendidikan</th>
                                        <th class="py-3">Sumber Pasien</th>
                                        <th class="py-3 text-start">Narkotika</th>
                                        <th class="py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pasien->riwayat as $idx => $r)
                                    <tr>
                                        <td class="fw-bold text-muted">{{ $idx + 1 }}</td>
                                        <td class="fw-bold text-primary text-start">{{ $r->tanggal_rehab->format('d F Y') }}</td>
                                        <td>{{ $r->usia }} Tahun</td>
                                        <td class="text-start">{{ $r->pekerjaan }}</td>
                                        <td class="text-start">{{ $r->pendidikan }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $r->sumber_pasien }}</span></td>
                                        <td class="text-start">
                                            <div class="text-wrap" style="max-width: 200px;">
                                                @foreach($r->narkotika as $n) <span class="badge bg-secondary mb-1">{{ $n->nama_narkotika }}</span> @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if (auth()->user()->hasRole(['operator_satker', 'operator_rehab', 'admin']))
                                                <a href="{{ route('rehab.pasien.riwayat.edit', ['id' => $r->id, 'ref' => 'show']) }}" class="btn btn-outline-primary btn-sm py-0 px-2" title="Edit Riwayat Kedatangan"><i class="bi bi-pencil-square"></i> Edit</a>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>
@endsection