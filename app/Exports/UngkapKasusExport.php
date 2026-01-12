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
    
    private $caseCounter = 0;
    private $lastLkn = null;
    private $totals = []; 
    private $processedBBIds = []; 
    private $bbSuffixes = [];
    private $cachedData = null;

    // Variabel untuk mendeteksi keberadaan Narkotika dalam data
    private $hasNarkotikaInData = false;

    public function __construct($kasusQuery)
    {
        // Query ini berasal dari model BerantasUngkapKasus yang sudah difilter di Controller
        $this->kasusQuery = $kasusQuery;
    }

    public function collection()
    {
        // 1. Ambil data Kasus berdasarkan query yang sudah difilter di Controller
        $kasusData = $this->kasusQuery->get();

        // 2. Ambil ID Kasus yang sudah difilter
        $kasusIds = $kasusData->pluck('id')->toArray();

        // 3. Ambil aturan filter (Eager Loads) dari query asal
        // Ini akan mengambil closure filter barangBukti yang Anda tulis di getFilteredQuery()
        $eagerLoads = $this->kasusQuery->getEagerLoads();
        $bbConstraint = $eagerLoads['barangBukti'] ?? function($q) { 
            $q->orderBy('kategori', 'asc')->orderBy('kuantitas', 'desc'); 
        };

        // 4. Jalankan query Tersangka dengan filter Barang Bukti yang sinkron
        $data = BerantasUngkapTersangka::query()
            ->with([
                'kasus.satuanKerja', 
                'barangBukti' => $bbConstraint, // Menggunakan filter yang sama dengan tampilan web
                'barangBukti.tersangka'
            ]) 
            ->join('berantas_ungkap_kasus', 'berantas_ungkap_tersangka.berantas_ungkap_kasus_id', '=', 'berantas_ungkap_kasus.id')
            ->whereIn('berantas_ungkap_kasus.id', $kasusIds)
            ->select('berantas_ungkap_tersangka.*')
            ->orderBy('berantas_ungkap_kasus.tanggal_kejadian', 'asc')
            ->orderBy('berantas_ungkap_kasus.nomor_lkn', 'asc')
            ->orderBy('berantas_ungkap_tersangka.urutan', 'asc')
            ->get();

        $this->cachedData = $data;

        // Cek apakah ada minimal 1 Narkotika untuk logika label "(non-narkotika)"
        foreach ($data as $t) {
            if ($t->barangBukti->contains('kategori', 'Narkotika')) {
                $this->hasNarkotikaInData = true;
                break;
            }
        }

        $this->calculateSmartSuffixes($data);
        return $data;
    }

    private function calculateSmartSuffixes($data)
    {
        $groupedByLkn = $data->groupBy(fn($item) => $item->kasus->nomor_lkn);

        foreach ($groupedByLkn as $lkn => $tersangkas) {
            $suspectInventorySignature = [];
            foreach ($tersangkas as $t) {
                $bbIds = $t->barangBukti->pluck('id')->sort()->implode('-');
                $suspectInventorySignature[$t->id] = $bbIds;
            }

            $groupCounter = 1;
            $processedForSuffix = []; 

            foreach ($tersangkas as $t) {
                foreach ($t->barangBukti as $bb) {
                    if (in_array($bb->id, $processedForSuffix)) continue;
                    
                    $owners = $bb->tersangka;
                    if ($owners->count() > 1) {
                        $firstSignature = $suspectInventorySignature[$owners->first()->id] ?? '';
                        $allIdentical = true;
                        foreach ($owners as $owner) {
                            if (($suspectInventorySignature[$owner->id] ?? '') !== $firstSignature) {
                                $allIdentical = false;
                                break;
                            }
                        }
                        if ($allIdentical) {
                            $this->bbSuffixes[$bb->id] = ""; 
                        } else {
                            $this->bbSuffixes[$bb->id] = " [b$groupCounter]";
                            $groupCounter++;
                        }
                    } else {
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
            'ALAMAT TKP', 'JENIS BARANG BUKTI', 'JUMLAH BB (GRAM)', 'FOTO TERSANGKA', 'TAHAP'
        ];
    }

    public function map($row): array
    {
        $currentLkn = $row->kasus->nomor_lkn;
        $no = '';
        if ($currentLkn !== $this->lastLkn) {
            $this->caseCounter++;
            $no = $this->caseCounter;
            $this->lastLkn = $currentLkn;
        }

        $tgl = $row->kasus->tanggal_kejadian ? $row->kasus->tanggal_kejadian->format('d M Y') : '-';
        $lknStr = $row->kasus->nomor_lkn . "\n" . ($row->kasus->satuanKerja->satuan_kerja ?? '') . ", Tgl " . $tgl;

        $arrJenis = [];
        $arrBerat = []; 

        foreach ($row->barangBukti as $bb) {
            $suffix = $this->bbSuffixes[$bb->id] ?? "";
            $namaBB = $bb->nama_barang; 

            if ($this->hasNarkotikaInData && $bb->kategori !== 'Narkotika') {
                $namaBB .= " (non-narkotika)";
            }

            $satuan = $bb->satuan;
            $kuantitas = (float)$bb->kuantitas;

            $gramValue = $kuantitas;
            if ($satuan === 'Kg') {
                $gramValue = $kuantitas * 1000;
            } elseif ($satuan === 'Ton') {
                $gramValue = $kuantitas * 1000000;
            }

            $arrJenis[] = $namaBB . $suffix;
            $arrBerat[] = $gramValue . ' Gram' . $suffix; 

            if ($bb->kategori === 'Narkotika' && !in_array($bb->id, $this->processedBBIds)) {
                $jenisKey = strtoupper(trim($bb->nama_barang));
                if (!isset($this->totals[$jenisKey])) {
                    $this->totals[$jenisKey] = 0;
                }
                $this->totals[$jenisKey] += $gramValue;
                $this->processedBBIds[] = $bb->id;
            }
        }

        if (empty($arrJenis)) {
            $arrJenis[] = '-'; $arrBerat[] = '-';
        }

        return [
            $no, $lknStr, $row->nama_tersangka, $row->jenis_kelamin, 
            $row->pekerjaan ?? '-', $row->kasus->alamat_tkp, 
            implode("\n", $arrJenis), implode("\n", $arrBerat), '', $row->tahap 
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

                $sheet->getColumnDimension('B')->setWidth(35);
                $sheet->getColumnDimension('F')->setWidth(40);
                $sheet->getColumnDimension('G')->setWidth(35);
                $sheet->getColumnDimension('H')->setWidth(25);
                $sheet->getColumnDimension('I')->setWidth(20);
                
                $sheet->getStyle("A1:J" . ($rowCount + 10))->getAlignment()->setWrapText(true);
                $sheet->getStyle("A1:J" . ($rowCount + 10))->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A2:A" . ($rowCount + 10))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("B2:B" . ($rowCount + 10))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D2:D" . ($rowCount + 10))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("H2:H" . ($rowCount + 10))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("J2:J" . ($rowCount + 10))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $mergeStart = $startRow;
                for ($i = 0; $i < $rowCount; $i++) {
                    $currentRow = $startRow + $i;
                    $fotoPath = $data[$i]->foto_tersangka;
                    
                    if ($fotoPath && file_exists(storage_path('app/public/' . $fotoPath))) {
                        $drawing = new Drawing();
                        $drawing->setPath(storage_path('app/public/' . $fotoPath));
                        $drawing->setHeight(80);
                        $drawing->setCoordinates('I' . $currentRow);
                        $drawing->setOffsetX(10)->setOffsetY(10)->setWorksheet($sheet);
                        $sheet->getRowDimension($currentRow)->setRowHeight(90); 
                    } else {
                        $sheet->getRowDimension($currentRow)->setRowHeight(40);
                    }

                    if (($i === $rowCount - 1) || ($data[$i]->kasus->nomor_lkn !== $data[$i+1]->kasus->nomor_lkn)) {
                        $endRow = $currentRow;
                        if ($mergeStart < $endRow) {
                            $sheet->mergeCells("A{$mergeStart}:A{$endRow}"); 
                            $sheet->mergeCells("B{$mergeStart}:B{$endRow}"); 
                            $sheet->mergeCells("F{$mergeStart}:F{$endRow}"); 
                        }
                        $this->smartMergeInner($sheet, 'G', $mergeStart, $endRow);
                        $this->smartMergeInner($sheet, 'H', $mergeStart, $endRow);
                        $mergeStart = $currentRow + 1;
                    }
                }

                $footerRow = $startRow + $rowCount;
                $sheet->setCellValue('A' . $footerRow, 'JUMLAH');
                $sheet->mergeCells("A{$footerRow}:B{$footerRow}");
                $sheet->setCellValue('C' . $footerRow, $rowCount . ' ORANG');

                $totalString = [];
                ksort($this->totals); 
                foreach ($this->totals as $jenis => $totalGram) {
                    $totalString[] = "$jenis : " . $totalGram . " Gram";
                }

                $sheet->setCellValue('H' . $footerRow, implode("\n", $totalString));
                $sheet->getStyle("A{$footerRow}:J{$footerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$footerRow}:J{$footerRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
                $sheet->getStyle("A1:J{$footerRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }

    private function smartMergeInner($sheet, $col, $start, $end)
    {
        $mStart = $start;
        for ($r = $start; $r < $end; $r++) {
            if ($sheet->getCell("$col$r")->getValue() !== $sheet->getCell("$col" . ($r + 1))->getValue()) {
                if ($mStart < $r) $sheet->mergeCells("$col$mStart:$col$r");
                $mStart = $r + 1;
            }
        }
        if ($mStart < $end) $sheet->mergeCells("$col$mStart:$col$end");
    }
}