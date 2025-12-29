<?php

namespace App\Exports;

use App\Models\BerantasUngkapTersangka;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class UngkapKasusExport implements FromCollection, WithHeadings, WithMapping, WithEvents, ShouldAutoSize, WithStyles
{
    protected $kasusQuery;
    private $rowNumber = 0;
    private $lastLkn = null;
    private $caseCounter = 0;
    private $results = null;

    public function __construct($kasusQuery)
    {
        $this->kasusQuery = $kasusQuery;
    }

    public function collection()
    {
        if ($this->results) return $this->results;

        $kasusIds = (clone $this->kasusQuery)->pluck('berantas_ungkap_kasus.id')->toArray();

        $this->results = BerantasUngkapTersangka::query()
            ->with(['kasus.satuanKerja', 'kasus.barangBuktiBersama', 'barangBukti'])
            ->join('berantas_ungkap_kasus', 'berantas_ungkap_tersangka.berantas_ungkap_kasus_id', '=', 'berantas_ungkap_kasus.id')
            ->whereIn('berantas_ungkap_kasus.id', $kasusIds)
            ->select('berantas_ungkap_tersangka.*')
            // SORTING: LKN -> Jenis BB -> Urutan TSK
            ->orderBy('berantas_ungkap_kasus.tanggal_kejadian', 'desc')
            ->orderBy('berantas_ungkap_kasus.nomor_lkn', 'asc')
            ->orderBy('berantas_ungkap_tersangka.urutan', 'asc')
            ->get();

        return $this->results;
    }

    public function headings(): array
    {
        // KITA PISAH KOLOMNYA
        return [
            'No', 
            'Nomor LKN', 
            'Tanggal Kejadian', 
            'Satuan Kerja', 
            'TKP', 
            'Nama Tersangka', 
            'JK', 
            'Pekerjaan', 
            'Jenis BB',        // Kolom I (Akan di-merge jika jenis sama)
            'Berat (Bruto)',   // Kolom J (Hanya merge jika BB Bersama)
            'Satuan',          // Kolom K
            'Status', 
            'Foto'
        ];
    }

    public function map($row): array
    {
        $currentLkn = $row->kasus->nomor_lkn;
        if ($currentLkn !== $this->lastLkn) {
            $this->caseCounter++;
            $this->lastLkn = $currentLkn;
        }

        // --- LOGIKA PINTAR PEMISAHAN BB ---
        $arrJenis = [];
        $arrBerat = [];
        $arrSatuan = [];

        // 1. BB BERSAMA (Prioritas)
        if ($row->kasus && $row->kasus->barangBuktiBersama->isNotEmpty()) {
            foreach($row->kasus->barangBuktiBersama as $bb) {
                $arrJenis[]  = $bb->jenis_barang_bukti . " (Bersama)"; // Penanda
                $arrBerat[]  = $bb->jumlah_barang_bukti * 1; // *1 untuk hapus .00 berlebih
                $arrSatuan[] = $bb->satuan_barang_bukti;
            }
        }

        // 2. BB PERSONAL
        if ($row->barangBukti->isNotEmpty()) {
            foreach($row->barangBukti as $bb) {
                $arrJenis[]  = $bb->jenis_barang_bukti;
                $arrBerat[]  = $bb->jumlah_barang_bukti * 1;
                $arrSatuan[] = $bb->satuan_barang_bukti;
            }
        }

        // Jika kosong
        if (empty($arrJenis)) {
            $arrJenis[] = '-'; $arrBerat[] = '-'; $arrSatuan[] = '-';
        }

        return [
            $this->caseCounter,
            $row->kasus->nomor_lkn,
            $row->kasus->tanggal_kejadian ? $row->kasus->tanggal_kejadian->format('d-m-Y') : '-',
            $row->kasus->satuanKerja->satuan_kerja ?? 'BNNP',
            $row->kasus->alamat_tkp,
            $row->nama_tersangka,
            $row->jenis_kelamin,
            $row->pekerjaan ?? '-',
            
            implode("\n", $arrJenis),  // Kolom I
            implode("\n", $arrBerat),  // Kolom J
            implode("\n", $arrSatuan), // Kolom K
            
            $row->status_tahap,
            '' 
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4E73DF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $data = $this->collection();
                $rowCount = $data->count();
                $startRow = 2;

                // WRAP TEXT
                $sheet->getStyle('E')->getAlignment()->setWrapText(true); // TKP
                $sheet->getStyle('I')->getAlignment()->setWrapText(true); // Jenis
                
                // ALIGNMENT
                $sheet->getStyle("A1:M" . ($rowCount + 1))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A2:D" . ($rowCount + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("G2:G" . ($rowCount + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // JK
                $sheet->getStyle("J2:K" . ($rowCount + 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Berat & Satuan

                // --- LOGIKA MERGE PINTAR ---
                $mergeStart = $startRow;

                for ($i = 0; $i < $rowCount; $i++) {
                    $currentRow = $startRow + $i;
                    
                    // 1. FOTO
                    $fotoPath = $data[$i]->foto_tersangka;
                    if ($fotoPath && file_exists(storage_path('app/public/' . $fotoPath))) {
                        $drawing = new Drawing();
                        $drawing->setPath(storage_path('app/public/' . $fotoPath));
                        $drawing->setHeight(80);
                        $drawing->setCoordinates('M' . $currentRow);
                        $drawing->setOffsetX(5); $drawing->setOffsetY(5);
                        $drawing->setWorksheet($sheet);
                        $sheet->getRowDimension($currentRow)->setRowHeight(90);
                    } else {
                        $sheet->setCellValue('M' . $currentRow, '-');
                        $sheet->getStyle('M' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getRowDimension($currentRow)->setRowHeight(40);
                    }

                    // 2. CEK APAKAH BARIS SELANJUTNYA MASIH KASUS (LKN) YANG SAMA?
                    $isLastRow = ($i === $rowCount - 1);
                    $isNextDifferentLKN = !$isLastRow && ($data[$i]->kasus->nomor_lkn !== $data[$i+1]->kasus->nomor_lkn);

                    if ($isLastRow || $isNextDifferentLKN) {
                        $endRow = $currentRow;

                        // JIKA ADA > 1 TERSANGKA DALAM 1 KASUS -> LAKUKAN MERGE
                        if ($mergeStart < $endRow) {
                            // A. Merge Kolom Metadata Kasus (Pasti Sama)
                            $sheet->mergeCells("A{$mergeStart}:A{$endRow}"); // No
                            $sheet->mergeCells("B{$mergeStart}:B{$endRow}"); // LKN
                            $sheet->mergeCells("C{$mergeStart}:C{$endRow}"); // Tgl
                            $sheet->mergeCells("D{$mergeStart}:D{$endRow}"); // Satker
                            $sheet->mergeCells("E{$mergeStart}:E{$endRow}"); // TKP

                            // B. MERGE KOLOM JENIS BB (I)
                            // Logika: Jika Row 1 "Shabu" dan Row 2 "Shabu", maka Merge.
                            // Jika Row 1 "Shabu" dan Row 2 "Ganja", jangan Merge.
                            $this->smartMergeColumn($sheet, 'I', $mergeStart, $endRow);

                            // C. MERGE KOLOM BERAT (J) & SATUAN (K)
                            // Logika: Jika Row 1 "100" dan Row 2 "100" (BB Bersama), Merge.
                            // Jika Row 1 "10" dan Row 2 "20" (BB Personal), JANGAN Merge.
                            $this->smartMergeColumn($sheet, 'J', $mergeStart, $endRow);
                            $this->smartMergeColumn($sheet, 'K', $mergeStart, $endRow);
                        }
                        
                        $mergeStart = $currentRow + 1;
                    }
                }

                // Border
                $lastRow = $startRow + $rowCount - 1;
                $sheet->getStyle("A1:M{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }

    /**
     * Helper: Merge kolom hanya jika isinya identik dari baris awal sampai akhir range
     */
    private function smartMergeColumn($sheet, $col, $start, $end)
    {
        $firstVal = $sheet->getCell("{$col}{$start}")->getValue();
        $allSame = true;

        for ($r = $start + 1; $r <= $end; $r++) {
            if ($sheet->getCell("{$col}{$r}")->getValue() !== $firstVal) {
                $allSame = false;
                break;
            }
        }

        if ($allSame) {
            $sheet->mergeCells("{$col}{$start}:{$col}{$end}");
        }
    }
}