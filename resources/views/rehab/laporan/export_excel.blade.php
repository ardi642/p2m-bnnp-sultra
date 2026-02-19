<table>
    <thead>
        <tr>
            <th colspan="{{ count($years) + 1 }}" style="font-weight: bold; font-size: 14px; text-align: center; height: 30px;">
                LAPORAN REKAPITULASI {{ $title }}
            </th>
        </tr>
        <tr>
            <th colspan="{{ count($years) + 1 }}" style="font-weight: bold; font-size: 12px; text-align: center;">
                PERIODE TAHUN: {{ implode(', ', $years) }}
            </th>
        </tr>
        
        <tr>
            <th rowspan="2" style="width: 250px; text-align: center; vertical-align: middle; background-color: #92D050; color: white; border: 1px solid white;">
                SATUAN KERJA
            </th>
            @foreach($years as $year)
                <th colspan="3" style="text-align: center; background-color: #92D050; color: white; border: 1px solid white;">
                    TAHUN {{ $year }}
                </th>
            @endforeach
        </tr>
        <tr>
            @foreach($years as $year)
                <th style="width: 80px; text-align: center; background-color: #EBF1DE; border: 1px solid white;">Target</th>
                <th style="width: 80px; text-align: center; background-color: #EBF1DE; border: 1px solid white;">Realisasi</th>
                <th style="width: 80px; text-align: center; background-color: #EBF1DE; border: 1px solid white;">%</th>
            @endforeach
        </tr>
    </thead>

    <tbody>
        @foreach($data as $row)
        <tr>
            <td style="vertical-align: middle;">{{ $row['satker_nama'] }}</td>
            
            @foreach($years as $year)
                @php 
                    $curr = $row['years'][$year] ?? ['target' => 0, 'realisasi' => 0, 'persen' => 0];
                @endphp
                <td style="text-align: center;">{{ $curr['target'] }}</td>
                <td style="text-align: center;">{{ $curr['realisasi'] }}</td>
                <td style="text-align: center;">{{ number_format($curr['persen'], 1) }}%</td>
            @endforeach
        </tr>
        @endforeach
    </tbody>

    <tfoot>
        <tr>
            <td style="font-weight: bold; background-color: #92D050; color: white; text-align: center;">TOTAL NASIONAL</td>
            @foreach($years as $year)
                @php
                    $sumTarget = collect($data)->sum(fn($d) => $d['years'][$year]['target'] ?? 0);
                    $sumReal = collect($data)->sum(fn($d) => $d['years'][$year]['realisasi'] ?? 0);
                    $sumPersen = $sumTarget > 0 ? ($sumReal / $sumTarget) * 100 : 0;
                @endphp
                <td style="font-weight: bold; background-color: #92D050; color: white; text-align: center;">{{ $sumTarget }}</td>
                <td style="font-weight: bold; background-color: #92D050; color: white; text-align: center;">{{ $sumReal }}</td>
                <td style="font-weight: bold; background-color: #92D050; color: white; text-align: center;">{{ number_format($sumPersen, 1) }}%</td>
            @endforeach
        </tr>
    </tfoot>
</table>