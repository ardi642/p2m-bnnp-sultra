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

    public function __construct($query) {
        $this->query = $query;
    }

    public function collection() {
        return $this->query->get();
    }

    public function headings(): array {
        return [
            'NO', 'SATUAN KERJA', 'TANGGAL PEROLEHAN', 'SUMBER PEROLEHAN', 
            'LOKASI PEROLEHAN', 'DAFTAR BARANG BUKTI', 'JUMLAH & SATUAN'
        ];
    }

    public function map($row): array {
        $this->counter++;
        $listNama = [];
        $listJumlah = [];

        foreach ($row->items as $item) {
            $nama = $item->nama_barang;
            $qty = (float)$item->kuantitas;
            
            // Ambil dari helper accessor model
            $satuan = $item->satuan; 

            // Hitung Total Gram (Hanya Narkotika)
            if ($item->kategori === 'Narkotika') {
                $gram = $qty;
                if ($satuan === 'Kg') $gram = $qty * 1000;
                if ($satuan === 'Ton') $gram = $qty * 1000000;
                
                $key = strtoupper($nama);
                if (!isset($this->totals[$key])) $this->totals[$key] = 0;
                $this->totals[$key] += $gram;
            } else {
                $nama .= " (Non-Narkotika)";
            }

            $listNama[] = "- " . $nama;
            $listJumlah[] = $qty . " " . ucfirst($satuan);
        }

        return [
            $this->counter,
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->tanggal_perolehan->format('d/m/Y'),
            $row->sumber_perolehan,
            $row->lokasi_perolehan ?? '-',
            implode("\n", $listNama),
            implode("\n", $listJumlah)
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
                
                // Styling Data Table
                $sheet->getStyle('A2:G' . $highestRow)->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true]
                ]);

                // --- PERBAIKAN LEBAR KOLOM DISINI ---
                $sheet->getColumnDimension('A')->setWidth(5);  // No
                $sheet->getColumnDimension('B')->setWidth(30); // Satker
                $sheet->getColumnDimension('C')->setWidth(25); // Tanggal (Diperlebar)
                $sheet->getColumnDimension('D')->setWidth(25); // Sumber (Diperlebar)
                $sheet->getColumnDimension('E')->setWidth(30); // Lokasi
                $sheet->getColumnDimension('F')->setWidth(40); // Daftar BB
                $sheet->getColumnDimension('G')->setWidth(20); // Jumlah

                // Footer Total
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