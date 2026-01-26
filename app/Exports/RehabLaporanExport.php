<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RehabLaporanExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $data;
    protected $years;
    protected $category; 

    public function __construct($data, $years, $category)
    {
        $this->data = $data;
        $this->years = $years;
        $this->category = $category;
    }

    public function view(): View
    {
        return view('rehab.laporan.export_excel', [
            'data' => $this->data,
            'years' => $this->years,
            'category' => $this->category,
            'title' => $this->getTitle()
        ]);
    }

    private function getTitle()
    {
        switch ($this->category) {
            case 'rawat_jalan': return 'RAWAT JALAN';
            case 'pasca_rehab': return 'PASCA REHABILITASI';
            case 'skhpn': return 'SKHPN';
            default: return 'LAPORAN REHAB';
        }
    }

    public function styles(Worksheet $sheet)
    {
        // 1. FIX LEBAR KOLOM PERTAMA (MANUAL)
        // AutoSize sering gagal pada kolom merged, jadi kita set manual
        $sheet->getColumnDimension('A')->setWidth(35); 

        // 2. Styling Header (Baris 1 & 2)
        $sheet->getStyle('A1:Z2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92D050']], 
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER, 
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true // Agar teks panjang turun ke bawah
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);

        $lastRow = $sheet->getHighestRow();

        // 3. Styling Kolom Kiri (Instansi Pemerintah)
        $sheet->getStyle('A3:A' . $lastRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92D050']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_LEFT], // Rata Kiri lebih rapi untuk nama satker
        ]);

        // 4. Styling Baris Total (Paling Bawah)
        $sheet->getStyle('A' . $lastRow . ':Z' . $lastRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '92D050']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // 5. Styling Data Tengah
        $sheet->getStyle('B3:Z' . ($lastRow - 1))->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF1DE']], 
        ]);

        return [];
    }
}