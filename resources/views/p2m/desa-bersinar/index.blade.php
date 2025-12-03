@extends('admin')
@section('content')
    <!-- Main Content -->
    <main class="admin-main">
        <div class="container-fluid p-4 p-lg-5">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Kegiatan P2M</h1>
                    <p class="text-muted mb-0">Master Data P2M</p>
                </div>
            </div>
            @include('p2m.partials.select-p2m-index')

            <div class="row justify-content-center">
                <div class="col-12 col-lg-12">
                    <div class="card shadow-lg p-5">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="card-title mb-0">Data Desa Bersinar</h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr class="text-center">
                                            <th>No</th>
                                            <th>Satuan Kerja</th>
                                            <th>Nama Desa</th>
                                            <th>Nama Kecamatan</th>
                                            <th>Kota/Kabupaten</th>
                                            <th>Tanggal Pencanangan</th>
                                            <th>Jumlah Penggiat P4GM</th>
                                            <th>Keberadaan IBM</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($desabersinars as $desabersinar)
                                            <tr class="text-center">
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $desabersinar->satuanKerja->satuan_kerja }}</td>
                                                <td>{{ $desabersinar->nama_desa }}</td>
                                                <td>{{ $desabersinar->nama_kecamatan }}</td>
                                                <td>{{ $desabersinar->kabupaten_kota }}</td>
                                                <td>{{ \Carbon\Carbon::parse($desabersinar->tanggal_pencanangan)->format('d/m/Y') }}</td>
                                                <td>{{ $desabersinar->jumlah_penggiat_p4gn }}</td>
                                                <td>{{ $desabersinar->keberadaan_ibm }}</td>
                                                <td>
                                                    <div class="d-flex gap-4">
                                                        <a href="#" class="btn btn-success btn-small">Perbarui</a>
                                                        <a href="#" class="btn btn-danger">Hapus</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center p-4">
                                                    <div class="text-muted">
                                                        Belum ada data kegiatan desa bersinar
                                                    </div>
                                                <td>
                                            </tr>
                                        @endforelse
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
