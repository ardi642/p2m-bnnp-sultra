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
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UngkapKasusExport implements FromCollection, WithHeadings, WithMapping, WithEvents, ShouldAutoSize, WithStyles
{
    protected $kasusQuery;
    
    // State Variables
    private $caseCounter = 0;
    private $lastLkn = null;
    private $totals = []; 
    private $processedBBIds = []; 

    // Map Suffix (1), (2)
    private $bbSuffixes = [];
    private $cachedData = null;

    public function __construct($kasusQuery)
    {
        $this->kasusQuery = $kasusQuery;
    }

    public function collection()
    {
        $kasusIds = (clone $this->kasusQuery)->pluck('berantas_ungkap_kasus.id')->toArray();

        // Ambil data dengan Sorting BB yang konsisten
        $data = BerantasUngkapTersangka::query()
            ->with(['kasus.satuanKerja', 'barangBukti' => function($q) {
                $q->orderBy('jenis_barang_bukti', 'asc')
                  ->orderBy('jumlah_barang_bukti', 'desc');
            }, 'barangBukti.tersangka']) 
            ->join('berantas_ungkap_kasus', 'berantas_ungkap_tersangka.berantas_ungkap_kasus_id', '=', 'berantas_ungkap_kasus.id')
            ->whereIn('berantas_ungkap_kasus.id', $kasusIds)
            ->select('berantas_ungkap_tersangka.*')
            ->orderBy('berantas_ungkap_kasus.tanggal_kejadian', 'asc')
            ->orderBy('berantas_ungkap_kasus.nomor_lkn', 'asc')
            ->orderBy('berantas_ungkap_tersangka.urutan', 'asc')
            ->get();

        $this->cachedData = $data;
        
        // JALANKAN LOGIKA CERDAS SEBELUM EXPORT
        $this->calculateSmartSuffixes($data);

        return $data;
    }

    /**
     * LOGIKA "INVENTORY CHECK":
     * Menentukan kapan (1), (2) muncul.
     * Aturan: Munculkan nomor HANYA JIKA merge vertikal tidak mungkin dilakukan.
     */
    private function calculateSmartSuffixes($data)
    {
        // 1. Grouping per LKN
        $groupedByLkn = $data->groupBy(fn($item) => $item->kasus->nomor_lkn);

        foreach ($groupedByLkn as $lkn => $tersangkas) {
            
            // A. Buat "Tanda Tangan Inventaris" per Tersangka
            // Kita harus tahu apa saja isi kantong setiap tersangka
            $suspectInventorySignature = [];
            foreach ($tersangkas as $t) {
                // Signature = String gabungan semua ID BB milik dia (urutan sorted)
                $bbIds = $t->barangBukti->pluck('id')->sort()->implode('-');
                $suspectInventorySignature[$t->id] = $bbIds;
            }

            // B. Cek Setiap Barang Bukti
            $groupCounter = 1;
            $processedForSuffix = []; // Agar counter tidak naik berkali-kali untuk BB yg sama

            foreach ($tersangkas as $t) {
                foreach ($t->barangBukti as $bb) {
                    if (in_array($bb->id, $processedForSuffix)) continue;
                    
                    $owners = $bb->tersangka;
                    
                    // Cek 1: Apakah Milik Bersama?
                    if ($owners->count() > 1) {
                        
                        // Cek 2: Apakah Inventaris Semua Pemilik SAMA PERSIS?
                        // Jika Inventaris sama -> Isi Sel Excel Sama -> Bisa Merge Vertikal -> TIDAK PERLU NOMOR
                        // Jika Inventaris beda -> Isi Sel Excel Beda -> Tidak Merge -> BUTUH NOMOR (Fallback)
                        
                        $firstSignature = $suspectInventorySignature[$owners->first()->id];
                        $allIdentical = true;

                        foreach ($owners as $owner) {
                            if (($suspectInventorySignature[$owner->id] ?? '') !== $firstSignature) {
                                $allIdentical = false;
                                break;
                            }
                        }

                        if ($allIdentical) {
                            // Perfect Match: Clean Merge (Tanpa Suffix)
                            $this->bbSuffixes[$bb->id] = ""; 
                        } else {
                            // Imperfect: Ada yg punya barang lain -> Kasih Nomor (1)
                            $this->bbSuffixes[$bb->id] = " ($groupCounter)";
                            $groupCounter++;
                        }
                    } else {
                        // Milik Sendiri -> Tidak perlu suffix
                        $this->bbSuffixes[$bb->id] = "";
                    }

                    $processedForSuffix[] = $bb->id;
                }
            }
        }
    }

    public function headings(): array
    {
        return [
            'NO', 'NOMOR LKN/ TGL', 'NAMA TERSANGKA', 'JENIS KELAMIN', 'PEKERJAAN', 
            'ALAMAT TKP', 'JENIS BARANG BUKTI', 'JUMLAH BB (BRUTO)', 'FOTO TERSANGKA', 'TAHAP'
        ];
    }

    public function map($row): array
    {
        // Penomoran LKN
        $currentLkn = $row->kasus->nomor_lkn;
        $no = '';
        if ($currentLkn !== $this->lastLkn) {
            $this->caseCounter++;
            $no = $this->caseCounter;
            $this->lastLkn = $currentLkn;
        }

        // Format LKN
        $tgl = $row->kasus->tanggal_kejadian ? $row->kasus->tanggal_kejadian->format('d M Y') : '-';
        $lknStr = $row->kasus->nomor_lkn . "\n" . ($row->kasus->satuanKerja->satuan_kerja ?? '') . ", Tgl " . $tgl;

        // Mapping BB
        $arrJenis = [];
        $arrBerat = []; 

        foreach ($row->barangBukti as $bb) {
            // A. VISUAL: Ambil Suffix Pintar dari Logic di atas
            $suffix = $this->bbSuffixes[$bb->id] ?? "";

            $arrJenis[] = $bb->jenis_barang_bukti . $suffix;
            $arrBerat[] = ($bb->jumlah_barang_bukti * 1) . ' ' . $bb->satuan_barang_bukti . $suffix; 

            // B. TOTAL: Mencegah Double Count
            if (!in_array($bb->id, $this->processedBBIds)) {
                $jenisKey = strtoupper(trim($bb->jenis_barang_bukti));
                $satuan = $bb->satuan_barang_bukti;
                $jumlah = $bb->jumlah_barang_bukti;

                if (!isset($this->totals[$jenisKey])) $this->totals[$jenisKey] = 0;

                $gram = $jumlah;
                if ($satuan === 'Kg') $gram = $jumlah * 1000;
                if ($satuan === 'Ton') $gram = $jumlah * 1000000;
                
                $this->totals[$jenisKey] += $gram;
                $this->processedBBIds[] = $bb->id;
            }
        }

        if (empty($arrJenis)) {
            $arrJenis[] = '-'; $arrBerat[] = '-';
        }

        return [
            $no, 
            $lknStr, 
            $row->nama_tersangka, 
            $row->jenis_kelamin, 
            $row->pekerjaan ?? '-', 
            $row->kasus->alamat_tkp, 
            implode("\n", $arrJenis), 
            implode("\n", $arrBerat), 
            '', 
            $row->tahap 
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF0000']], 
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
                $data = $this->cachedData;
                $rowCount = $data->count();
                $startRow = 2;

                // Layouting
                $sheet->getColumnDimension('B')->setWidth(35);
                $sheet->getColumnDimension('F')->setWidth(40);
                $sheet->getColumnDimension('G')->setWidth(25);
                $sheet->getColumnDimension('H')->setWidth(20);
                $sheet->getColumnDimension('I')->setWidth(20);
                
                $sheet->getStyle("A1:J" . ($rowCount + 10))->getAlignment()->setWrapText(true);
                $sheet->getStyle("A1:J" . ($rowCount + 10))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A2:A" . ($rowCount + 10))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B2:B" . ($rowCount + 10))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D2:D" . ($rowCount + 10))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("H2:H" . ($rowCount + 10))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("J2:J" . ($rowCount + 10))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Merge Loop
                $mergeStart = $startRow;
                for ($i = 0; $i < $rowCount; $i++) {
                    $currentRow = $startRow + $i;
                    
                    // Foto
                    $fotoPath = $data[$i]->foto_tersangka;
                    if ($fotoPath && file_exists(storage_path('app/public/' . $fotoPath))) {
                        $drawing = new Drawing();
                        $drawing->setPath(storage_path('app/public/' . $fotoPath));
                        $drawing->setHeight(80);
                        $drawing->setCoordinates('I' . $currentRow);
                        $drawing->setOffsetX(10); $drawing->setOffsetY(10);
                        $drawing->setWorksheet($sheet);
                        $sheet->getRowDimension($currentRow)->setRowHeight(90); 
                    } else {
                        $sheet->getRowDimension($currentRow)->setRowHeight(40);
                    }

                    // Merge LKN Group
                    $isLastRow = ($i === $rowCount - 1);
                    $isNextDifferentLKN = !$isLastRow && ($data[$i]->kasus->nomor_lkn !== $data[$i+1]->kasus->nomor_lkn);

                    if ($isLastRow || $isNextDifferentLKN) {
                        $endRow = $currentRow;
                        
                        // Merge Kolom Umum
                        if ($mergeStart < $endRow) {
                            $sheet->mergeCells("A{$mergeStart}:A{$endRow}"); 
                            $sheet->mergeCells("B{$mergeStart}:B{$endRow}"); 
                            $sheet->mergeCells("F{$mergeStart}:F{$endRow}"); 
                        }

                        // SMART MERGE BB (Kolom G & H)
                        // Karena kita sudah mengatur Suffix di atas:
                        // - Jika Inventory Sama -> Teks Sama -> Fungsi ini akan otomatis Merge Vertikal.
                        // - Jika Inventory Beda -> Teks Beda (krn ada item lain / suffix) -> Tidak Merge.
                        $this->smartMergeInner($sheet, 'G', $mergeStart, $endRow);
                        $this->smartMergeInner($sheet, 'H', $mergeStart, $endRow);

                        $mergeStart = $currentRow + 1;
                    }
                }

                // Footer Total
                $footerRow = $startRow + $rowCount;
                $sheet->setCellValue('A' . $footerRow, 'JUMLAH');
                $sheet->mergeCells("A{$footerRow}:B{$footerRow}");
                $sheet->getStyle("A{$footerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$footerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A{$footerRow}:J{$footerRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');

                $sheet->setCellValue('C' . $footerRow, $rowCount . ' ORANG');
                $sheet->getStyle('C' . $footerRow)->getFont()->setBold(true);
                $sheet->getStyle('C' . $footerRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Total BB Display
                $totalString = [];
                foreach ($this->totals as $jenis => $totalGram) {
                    $display = '';
                    if ($totalGram >= 1000000) {
                        $display = round($totalGram / 1000000, 4) . " Ton";
                    } elseif ($totalGram >= 1000) {
                        $display = round($totalGram / 1000, 4) . " Kg";
                    } else {
                        $display = round($totalGram, 4) . " Gram";
                    }
                    $totalString[] = "$jenis : $display";
                }

                $sheet->setCellValue('H' . $footerRow, implode("\n", $totalString));
                $sheet->getStyle('H' . $footerRow)->getFont()->setBold(true);
                $sheet->getStyle('H' . $footerRow)->getAlignment()->setWrapText(true);
                
                $sheet->getStyle("A1:J{$footerRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }

    /**
     * Merge Vertikal Selektif: Hanya merge jika teks sama persis.
     */
    private function smartMergeInner($sheet, $col, $start, $end)
    {
        $mStart = $start;
        for ($r = $start; $r < $end; $r++) {
            $valCurrent = $sheet->getCell("$col$r")->getValue();
            $valNext = $sheet->getCell("$col" . ($r + 1))->getValue();

            if ($valCurrent !== $valNext) {
                if ($mStart < $r) {
                    $sheet->mergeCells("$col$mStart:$col$r");
                }
                $mStart = $r + 1;
            }
        }
        if ($mStart < $end) {
            $sheet->mergeCells("$col$mStart:$col$end");
        }
    }
}