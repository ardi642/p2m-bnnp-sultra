<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RegisterBarangBuktiExport implements FromCollection, WithHeadings, WithMapping, WithEvents, WithStyles
{
    protected $query;
    private $totals = [];
    private $flattenedData = [];

    public function __construct($query) {
        $this->query = $query;
        $this->prepareData();
    }

    private function prepareData()
    {
        $registers = $this->query->get();
        $no = 1;

        foreach ($registers as $reg) {
            // Menggabungkan Alamat dengan Latitude & Longitude (Teks Lengkap)
            $lokasiGabung = $reg->lokasi_perolehan ?? '-';
            if (!empty($reg->latitude) && !empty($reg->longitude)) {
                $lokasiGabung .= "\nLatitude: " . $reg->latitude . "\nLongitude: " . $reg->longitude;
            }

            if ($reg->items->isEmpty()) {
                $this->flattenedData[] = [
                    'no' => $no++,
                    'satker' => $reg->satuanKerja->satuan_kerja ?? '-',
                    'tanggal' => $reg->tanggal_perolehan->format('d/m/Y'),
                    'lokasi' => $lokasiGabung,
                    'sumber' => '-',
                    'nama_barang' => '-',
                    'modus' => '-',
                    'jumlah' => '-',
                    'is_first_row' => true,
                    'row_span' => 1
                ];
                continue;
            }

            $first = true;
            $countItems = $reg->items->count();

            foreach ($reg->items as $item) {
                // Rekap Total Narkotika untuk Footer
                if ($item->kategori === 'Narkotika') {
                    $qty = (float)$item->kuantitas;
                    $satuan = $item->satuan_narkotika;
                    $gram = ($satuan === 'Kg') ? $qty * 1000 : (($satuan === 'Ton') ? $qty * 1000000 : $qty);
                    
                    $nama = ($item->narkotika->nama_narkotika ?? 'Narkotika');
                    $key = strtoupper($nama);
                    if (!isset($this->totals[$key])) $this->totals[$key] = 0;
                    $this->totals[$key] += $gram;
                }

                $this->flattenedData[] = [
                    'no' => $first ? $no++ : null,
                    'satker' => $first ? ($reg->satuanKerja->satuan_kerja ?? '-') : null,
                    'tanggal' => $first ? $reg->tanggal_perolehan->format('d/m/Y') : null,
                    'lokasi' => $first ? $lokasiGabung : null,
                    
                    'sumber' => $item->sumber_perolehan,
                    'nama_barang' => ($item->kategori === 'Narkotika') ? ($item->narkotika->nama_narkotika ?? '-') : $item->nama_barang_non_narkotika,
                    'modus' => $item->modus_pengiriman ?? '-',
                    'jumlah' => (float)$item->kuantitas . ' ' . ($item->kategori === 'Narkotika' ? $item->satuan_narkotika : $item->satuan_non_narkotika),
                    
                    'is_first_row' => $first,
                    'row_span' => $first ? $countItems : 0
                ];
                $first = false;
            }
        }
    }

    public function collection() {
        return collect($this->flattenedData);
    }

    public function headings(): array {
        return [
            'NO', 
            'SATUAN KERJA', 
            'TANGGAL PEROLEHAN', 
            'LOKASI & KOORDINAT', // Nama Kolom sesuai permintaan
            'SUMBER', 
            'NAMA BARANG BUKTI', 
            'MODUS PENGIRIMAN', 
            'JUMLAH / BERAT'
        ];
    }

    public function map($row): array {
        return [
            $row['no'],
            $row['satker'],
            $row['tanggal'],
            $row['lokasi'],
            $row['sumber'],
            $row['nama_barang'],
            $row['modus'],
            $row['jumlah']
        ];
    }

    public function styles(Worksheet $sheet) {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']], 
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER, 
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],
        ];
    }

    public function registerEvents(): array {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();
                
                $currentRow = 2; 
                foreach ($this->flattenedData as $data) {
                    if ($data['is_first_row'] && $data['row_span'] > 1) {
                        $endRow = $currentRow + $data['row_span'] - 1;
                        $sheet->mergeCells("A{$currentRow}:A{$endRow}");
                        $sheet->mergeCells("B{$currentRow}:B{$endRow}");
                        $sheet->mergeCells("C{$currentRow}:C{$endRow}");
                        $sheet->mergeCells("D{$currentRow}:D{$endRow}");
                    }
                    $currentRow++;
                }

                $sheet->getStyle('A1:H' . $highestRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_TOP, 
                        'wrapText' => true // Agar baris baru (\n) berfungsi di dalam sel
                    ]
                ]);
                
                // Alignment Tengah untuk kolom NO dan TANGGAL
                $sheet->getStyle("A2:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C2:C{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Lebar Kolom disesuaikan
                $sheet->getColumnDimension('A')->setWidth(5);  
                $sheet->getColumnDimension('B')->setWidth(25); 
                $sheet->getColumnDimension('C')->setWidth(18); 
                $sheet->getColumnDimension('D')->setWidth(45); // Diperlebar untuk Alamat + Latitude + Longitude
                $sheet->getColumnDimension('E')->setWidth(18); 
                $sheet->getColumnDimension('F')->setWidth(30); 
                $sheet->getColumnDimension('G')->setWidth(25); 
                $sheet->getColumnDimension('H')->setWidth(15); 

                // Footer Total
                $footerRow = $highestRow + 2;
                $sheet->setCellValue('A' . $footerRow, 'TOTAL REKAPITULASI NARKOTIKA (GRAM)');
                $sheet->mergeCells("A{$footerRow}:F{$footerRow}");
                
                $textTotal = [];
                ksort($this->totals);
                foreach ($this->totals as $jenis => $total) {
                    $textTotal[] = "$jenis: " . number_format($total, 2, ',', '.') . " Gram";
                }
                
                $sheet->setCellValue('G' . $footerRow, implode("\n", $textTotal ?: ['-']));
                $sheet->mergeCells("G{$footerRow}:H{$footerRow}");
                
                $sheet->getStyle("A{$footerRow}:H{$footerRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E9ECEF']],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER, 
                        'wrapText' => true
                    ],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);
            }
        ];
    }
}