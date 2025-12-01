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
                                    <h5 class="card-title mb-0">Data Informasi Edukasi melalui Media Online</h5>
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
                                            <th>Jenis Anggaran</th>   
                                            <th>Media Yang Digunakan</th> 
                                            <th>Durasi Pelaksanaan (Hari)</th>
                                            <th>Tanggal Pelaksanaan</th>
                                            <th>Nama Media</th>
                                            <th>Link Kelengkapan atau Dokumentasi</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($onlines as $data)
                                            <tr class="text-center">
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $data->satuanKerja->satuan_kerja }}</td>
                                                <td>{{ $data->anggaran_pelaksanaan }}</td>
                                                <td>{{ $data->media }}</td>
                                                <td>{{ $data->durasi_pelaksanaan }}</td>
                                                <td>{{ $data->tanggal_pelaksanaan }}</td>
                                                <td>{{ $data->nama_media }}</td>
                                                <td>{{ $data->link_kelengkapan_dokumentasi }}</td>
                                                <td>
                                                    <div class="d-flex gap-4">
                                                        <a href="#" class="btn btn-success btn-small">perbarui</a>
                                                        <a href="#" class="btn btn-danger">hapus</a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center p-4">
                                                    <div class="text-muted">
                                                        Belum ada data kegiatan sosialisasi
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
