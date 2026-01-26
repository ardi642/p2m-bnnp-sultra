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
    private $counter = 0;
    private $totals = [];
    
    // Variabel untuk menampung data yang sudah di-flatten (dipecah per item)
    private $flattenedData = [];

    public function __construct($query) {
        $this->query = $query;
        $this->prepareData();
    }

    /**
     * Memecah Data Register (Parent) menjadi baris-baris Item (Child)
     * untuk memudahkan proses mapping dan merging.
     */
    private function prepareData()
    {
        $registers = $this->query->get();
        $no = 1;

        foreach ($registers as $reg) {
            // Jika register tidak punya item, tetap tampilkan 1 baris (kosong)
            if ($reg->items->isEmpty()) {
                $this->flattenedData[] = [
                    'no' => $no++,
                    'satker' => $reg->satuanKerja->satuan_kerja ?? '-',
                    'tanggal' => $reg->tanggal_perolehan->format('d/m/Y'),
                    'lokasi' => $reg->lokasi_perolehan ?? '-',
                    'sumber' => '-',
                    'nama_barang' => '-',
                    'jumlah' => '-',
                    'is_first_row' => true, // Penanda awal register (untuk merge)
                    'row_span' => 1
                ];
                continue;
            }

            // Jika ada item, looping item
            $first = true;
            $countItems = $reg->items->count();

            foreach ($reg->items as $item) {
                // Hitung Total Gram (Hanya Narkotika)
                if ($item->kategori === 'Narkotika') {
                    $qty = (float)$item->kuantitas;
                    $satuan = $item->satuan;
                    
                    $gram = $qty;
                    if ($satuan === 'Kg') $gram = $qty * 1000;
                    if ($satuan === 'Ton') $gram = $qty * 1000000;
                    
                    $key = strtoupper($item->nama_barang);
                    if (!isset($this->totals[$key])) $this->totals[$key] = 0;
                    $this->totals[$key] += $gram;
                }

                $this->flattenedData[] = [
                    'no' => $first ? $no++ : null,
                    'satker' => $first ? ($reg->satuanKerja->satuan_kerja ?? '-') : null,
                    'tanggal' => $first ? $reg->tanggal_perolehan->format('d/m/Y') : null,
                    'lokasi' => $first ? ($reg->lokasi_perolehan ?? '-') : null,
                    
                    // Kolom Item (Selalu Tampil)
                    'sumber' => $item->sumber_perolehan,
                    'nama_barang' => $item->nama_barang,
                    'jumlah' => (float)$item->kuantitas . ' ' . ucfirst($item->satuan),
                    
                    // Metadata Merging
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
            'NO', 'SATUAN KERJA', 'TANGGAL PEROLEHAN', 'LOKASI PEROLEHAN', 
            'SUMBER', 'NAMA BARANG BUKTI', 'JUMLAH'
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
            $row['jumlah']
        ];
    }

    public function styles(Worksheet $sheet) {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0000FF']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]],
        ];
    }

    public function registerEvents(): array {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();
                
                // 1. LOGIKA MERGE VERTIKAL (PINTAR)
                // Kita mulai dari baris ke-2 (karena baris 1 adalah Header)
                $currentRow = 2; 

                foreach ($this->flattenedData as $data) {
                    if ($data['is_first_row'] && $data['row_span'] > 1) {
                        $endRow = $currentRow + $data['row_span'] - 1;
                        
                        // Merge Kolom No
                        $sheet->mergeCells("A{$currentRow}:A{$endRow}");
                        // Merge Kolom Satker
                        $sheet->mergeCells("B{$currentRow}:B{$endRow}");
                        // Merge Kolom Tanggal
                        $sheet->mergeCells("C{$currentRow}:C{$endRow}");
                        // Merge Kolom Lokasi
                        $sheet->mergeCells("D{$currentRow}:D{$endRow}");
                    }
                    $currentRow++;
                }

                // 2. Styling Data Table
                $sheet->getStyle('A2:G' . $highestRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true] // Align Top agar rapi saat merge
                ]);
                
                // Center Alignment untuk kolom tertentu
                $sheet->getStyle("A2:A{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // No
                $sheet->getStyle("C2:C{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Tanggal
                $sheet->getStyle("E2:E{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Sumber
                
                // 3. Lebar Kolom
                $sheet->getColumnDimension('A')->setWidth(5);  
                $sheet->getColumnDimension('B')->setWidth(30); 
                $sheet->getColumnDimension('C')->setWidth(15); 
                $sheet->getColumnDimension('D')->setWidth(30); 
                $sheet->getColumnDimension('E')->setWidth(15); 
                $sheet->getColumnDimension('F')->setWidth(35); 
                $sheet->getColumnDimension('G')->setWidth(20); 

                // 4. Footer Total
                $footerRow = $highestRow + 2;
                $sheet->setCellValue('A' . $footerRow, 'TOTAL REKAPITULASI NARKOTIKA (GRAM)');
                $sheet->mergeCells("A{$footerRow}:E{$footerRow}");
                
                $textTotal = [];
                ksort($this->totals);
                foreach ($this->totals as $jenis => $total) {
                    $textTotal[] = "$jenis: " . number_format($total, 2, ',', '.') . " Gram";
                }
                if(empty($textTotal)) $textTotal[] = "-";

                $sheet->setCellValue('F' . $footerRow, implode("\n", $textTotal));
                $sheet->mergeCells("F{$footerRow}:G{$footerRow}");
                
                $sheet->getStyle("A{$footerRow}:G{$footerRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
                ]);
                $sheet->getRowDimension($footerRow)->setRowHeight(max(30, count($textTotal) * 15 + 20));
            }
        ];
    }
}