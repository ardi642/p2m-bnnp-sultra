<table>
    <thead>
        {{-- HEADER 1: JUDUL TAHUN --}}
        <tr>
            <th rowspan="2" style="vertical-align: middle; width: 30px; text-align: center;">Instansi Pemerintah</th>
            {{-- Loop Tahun untuk Header Utama (colspan 3 untuk Target, Realisasi, %) --}}
            @foreach($years as $year)
                <th colspan="3" style="text-align: center;">{{ $year }}</th>
            @endforeach
        </tr>
        
        {{-- HEADER 2: SUB-HEADER --}}
        <tr>
            @foreach($years as $year)
                <th style="text-align: center;">Target</th>
                <th style="text-align: center;">Realisasi</th>
                <th style="text-align: center;">%</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        {{-- ISI DATA PER SATKER --}}
        @foreach($data as $row)
            <tr>
                <td>{{ $row['satker_nama'] }}</td>
                
                @foreach($years as $year)
                    @php
                        $stats = $row['years'][$year] ?? ['target' => 0, 'realisasi' => 0, 'persen' => 0];
                    @endphp
                    <td>{{ $stats['target'] }}</td>
                    <td>{{ $stats['realisasi'] }}</td>
                    <td>{{ number_format($stats['persen'], 2) }}</td>
                @endforeach
            </tr>
        @endforeach

        {{-- BARIS TOTAL --}}
        <tr>
            <td>Total</td>
            @foreach($years as $year)
                @php
                    // Hitung Total Vertikal per Tahun dari data yang ada
                    $totalTarget = collect($data)->sum(fn($item) => $item['years'][$year]['target'] ?? 0);
                    $totalRealisasi = collect($data)->sum(fn($item) => $item['years'][$year]['realisasi'] ?? 0);
                    $totalPersen = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;
                @endphp
                <td>{{ $totalTarget }}</td>
                <td>{{ $totalRealisasi }}</td>
                <td>{{ number_format($totalPersen, 2) }}</td>
            @endforeach
        </tr>
    </tbody>
</table>