@php
    // Cek triwulan apa saja yang dirender (Berdasarkan data payload filter)
    $firstYear = collect($years)->first();
    $selectedTw = isset($data[0]) ? array_keys($data[0]['years'][$firstYear]['tw']) : [1, 2, 3, 4];
    
    $twCount = count($selectedTw);
    // Kolom per tahun = (Jumlah TW yang dipilih) + 3 Kolom tambahan (Realisasi, Target, % Capaian)
    $yearColspan = $twCount + 3; 
    
    // Total header span = 1 Kolom Satker + (Total tahun yang dipilih * jumlah span per tahun)
    $totalHeaderSpan = 1 + (count($years) * $yearColspan);
@endphp

<table>
    <thead>
        <tr>
            <th colspan="{{ $totalHeaderSpan }}" style="border: 1px solid #000000; font-weight: bold; font-size: 14px; text-align: center; height: 30px; background-color: #92D050; color: #FFFFFF;">
                LAPORAN REKAPITULASI {{ $title }}
            </th>
        </tr>
        <tr>
            <th colspan="{{ $totalHeaderSpan }}" style="border: 1px solid #000000; font-weight: bold; font-size: 12px; text-align: center; background-color: #92D050; color: #FFFFFF;">
                PERIODE TAHUN: {{ implode(', ', $years) }} 
                @if($twCount < 4) | (Hanya Triwulan: {{ implode(', ', $selectedTw) }}) @endif
            </th>
        </tr>
        
        <tr>
            <th rowspan="2" style="border: 1px solid #000000; width: 250px; text-align: center; vertical-align: middle; background-color: #92D050; color: #FFFFFF; font-weight: bold;">
                SATUAN KERJA
            </th>
            @foreach($years as $year)
                <th colspan="{{ $yearColspan }}" style="border: 1px solid #000000; text-align: center; background-color: #92D050; color: #FFFFFF; font-weight: bold;">
                    TAHUN {{ $year }}
                </th>
            @endforeach
        </tr>
        <tr>
            @foreach($years as $year)
                {{-- Kolom Triwulan Dinamis --}}
                @foreach($selectedTw as $tw)
                    <th style="border: 1px solid #000000; width: 80px; text-align: center; background-color: #EBF1DE; font-weight: bold; color: #000000;">
                        TW {{ $tw }}
                    </th>
                @endforeach
                
                {{-- Kolom Summary --}}
                <th style="border: 1px solid #000000; width: 100px; text-align: center; background-color: #EBF1DE; font-weight: bold; color: #000000;">Realisasi</th>
                <th style="border: 1px solid #000000; width: 100px; text-align: center; background-color: #EBF1DE; font-weight: bold; color: #000000;">Target</th>
                <th style="border: 1px solid #000000; width: 80px; text-align: center; background-color: #EBF1DE; font-weight: bold; color: #000000;">% Capaian</th>
            @endforeach
        </tr>
    </thead>

    <tbody>
        @foreach($data as $row)
        <tr>
            <td style="border: 1px solid #000000; vertical-align: middle;">{{ $row['satker_nama'] }}</td>
            
            @foreach($years as $year)
                @php 
                    $curr = $row['years'][$year] ?? [
                        'tw' => array_fill_keys($selectedTw, 0), 
                        'realisasi_total' => 0, 
                        'target' => 0, 
                        'persen' => 0
                    ];
                @endphp
                
                {{-- Data Angka Triwulan --}}
                @foreach($selectedTw as $tw)
                    <td style="border: 1px solid #000000; text-align: center;">{{ $curr['tw'][$tw] ?? 0 }}</td>
                @endforeach
                
                {{-- Data Angka Summary --}}
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold;">{{ $curr['realisasi_total'] }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $curr['target'] }}</td>
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold;">{{ number_format($curr['persen'], 1) }}%</td>
            @endforeach
        </tr>
        @endforeach
    </tbody>

    <tfoot>
        <tr>
            <td style="border: 1px solid #000000; font-weight: bold; background-color: #92D050; color: #FFFFFF; text-align: center;">TOTAL AKUMULASI</td>
            
            @foreach($years as $year)
                {{-- Total Per Triwulan Terpilih --}}
                @foreach($selectedTw as $tw)
                    @php
                        $sumTw = collect($data)->sum(fn($d) => $d['years'][$year]['tw'][$tw] ?? 0);
                    @endphp
                    <td style="border: 1px solid #000000; font-weight: bold; background-color: #92D050; color: #FFFFFF; text-align: center;">
                        {{ $sumTw }}
                    </td>
                @endforeach

                {{-- Total Keseluruhan (Realisasi & Target) --}}
                @php
                    $sumTarget = collect($data)->sum(fn($d) => $d['years'][$year]['target'] ?? 0);
                    $sumReal = collect($data)->sum(fn($d) => $d['years'][$year]['realisasi_total'] ?? 0);
                    $sumPersen = $sumTarget > 0 ? ($sumReal / $sumTarget) * 100 : 0;
                @endphp
                
                <td style="border: 1px solid #000000; font-weight: bold; background-color: #92D050; color: #FFFFFF; text-align: center;">{{ $sumReal }}</td>
                <td style="border: 1px solid #000000; font-weight: bold; background-color: #92D050; color: #FFFFFF; text-align: center;">{{ $sumTarget }}</td>
                <td style="border: 1px solid #000000; font-weight: bold; background-color: #92D050; color: #FFFFFF; text-align: center;">{{ number_format($sumPersen, 1) }}%</td>
            @endforeach
        </tr>
    </tfoot>
</table>