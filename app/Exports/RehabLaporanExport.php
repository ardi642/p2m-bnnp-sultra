<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

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
        $sheet->getColumnDimension('A')->setWidth(35); 

        // 1. Hitung Baris dan Kolom Dinamis (Efisiensi Memory)
        $lastRow = $sheet->getHighestRow();
        
        // Kolom A = 1, ditambah (Jumlah Tahun * 3 Kolom)
        $totalCols = 1 + (count($this->years) * 3);
        $lastCol = Coordinate::stringFromColumnIndex($totalCols);

        // 2. Berikan Border & Alignment HANYA pada area tabel yang aktif
        // Ini menggantikan hardcode 'Z' yang membuat file lambat di-export
        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN, 
                    'color' => ['rgb' => '000000'] // Border hitam tipis standar
                ]
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ]
        ]);

        // 3. Posisikan text ke tengah mulai dari kolom B sampai kolom akhir
        $sheet->getStyle("B1:{$lastCol}{$lastRow}")->applyFromArray([
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ]
        ]);

        return [];
    }
}