@extends('admin') @section('content')
    <!-- Main Content -->
    <main class="admin-main">
        <div class="container-fluid p-lg-5"> <!-- Page Header -->
            <div class="row">
                <div class="md-9 grid-margin stretch-card">
                    <div class="card shadow-lg">
                        <div class="card-body">
                            <h3 class="text-primary-emphasis fw-bolder mb-3 mt-3">Dashboard</h4>
                                <h2 class="fw-light card-title mb-5">Selamat Datang di Sistem Rekapitulasi Pencegahan dan
                                    Pemberdayaan
                                    Masyarakat
                                    BNNP
                                    Sulawesi Tenggara</h2>
                                
                                <div id="sales-legend" class="chartjs-legend mt-2 mb-3"></div>

                                <form method="GET" class="row g-2 mb-4">
                                    <div class="col-auto text-left">
                                        <h3 class="fw-semibold mt-5">Total Kegiatan P2M </h3>
                                    </div>
                                    <div class="col-auto ms-auto">
                                        <div class="input-group input-group-md">
                                            {{-- Filter Tahun --}}
                                            <select name="year" class="form-select">
                                                <option value="">Tahun</option>
                                                @for ($y = $minYear; $y <= $maxYear; $y++)
                                                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>
                                                    {{ $y }}
                                                </option>
                                            @endfor

                                            </select>

                                            {{-- Filter Bulan --}}
                                            <select name="month" class="form-select">
                                                <option value="">Bulan</option>
                                                @for ($m = 1; $m <= 12; $m++)
                                                    <option value="{{ $m }}"
                                                        {{ $m == $month ? 'selected' : '' }}>
                                                        {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                                                    </option>
                                                @endfor
                                            </select>

                                            {{-- Tombol Filter --}}
                                            <button class="btn btn-primary" type="submit"><i class="bi bi-filter"></i>
                                                Filter</button>
                                        </div>
                                    </div>
                                </form>

                                <div class="chart-wrapper" style="height: 500px;"> <canvas id="p2mChart"></canvas> </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gambar dan Statistik -->
            <div class="md-3 grid-margin stretch-card py-1 mt-5">
                <div class="row g-3"> <!-- Card 1 -->
                    <div class="col-md-4">
                        <div class="card shadow-lg p-3 align-items-center">
                            <div class="d-flex align-items-center mb-2"> <i class="bi bi-ui-checks-grid fs-2 me-5"></i>
                                <span class="fw-normal text-uppercase fs-4">Total Jenis Kegiatan</span>
                            </div>
                            <h2 style="font-weight: 450">{{ $totalJenis }}</h2>
                        </div>
                    </div> <!-- Card 2 -->
                    <div class="col-md-4">
                        <div class="card shadow-lg p-3 align-items-center">
                            <div class="d-flex align-items-center mb-2"> <i class="bi bi-calendar3 fs-2 me-5"></i> <span
                                    class="fw-normal text-uppercase fs-4">Total Jumlah Kegiatan</span> </div>
                            <h2 style="font-weight: 450">{{ number_format($totalKegiatan, 0, ',', '.') }}</h2>
                        </div>
                    </div> <!-- Card 3 -->
                    <div class="col-md-4">
                        <div class="card shadow-lg p-3 align-items-center">
                            <div class="d-flex align-items-center mb-2"> <i class="bi bi-people fs-2 me-5"></i> <span
                                    class="fw-normal text-uppercase fs-4">Total Jumlah Peserta</span> </div>
                            <h2 style="font-weight: 450">{{ number_format($totalPeserta, 0, ',', '.') }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Ambil data dari controller
        const chartData = @json($chartData);

        // Konversi ke array label dan nilai
        const labels = chartData.map(item => item.kegiatan);
        const dataValues = chartData.map(item => item.nilai);

        const ctx = document.getElementById('p2mChart').getContext('2d');

        const p2mChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Kegiatan',
                    data: dataValues,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: Math.max(...dataValues) + 1, // otomatis menyesuaikan nilai tertinggi
                        // ticks: {
                        //     stepSize: 10 // ⬅️ jarak Y selalu 1
                        // },
                        title: {
                            display: true,
                            text: 'Total Kegiatan'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Jenis Kegiatan'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true
                    },
                    tooltip: {
                        callbacks: {
                            label: ctx => `Nilai: ${ctx.parsed.y}`
                        }
                    }
                }
            }
        });
    </script>
@endsection
