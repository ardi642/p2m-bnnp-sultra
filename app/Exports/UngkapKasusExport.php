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
    
    // Array untuk menyimpan info suffix per ID Barang Bukti
    private $bbSuffixInfo = []; 
    
    private $cachedData = null;
    private $hasNarkotikaInData = false;

    public function __construct($kasusQuery)
    {
        $this->kasusQuery = $kasusQuery;
    }

    public function collection()
    {
        $kasusData = $this->kasusQuery->get();
        $kasusIds = $kasusData->pluck('id')->toArray();

        $eagerLoads = $this->kasusQuery->getEagerLoads();
        $bbConstraint = $eagerLoads['barangBukti'] ?? function($q) { 
            $q->orderBy('kategori', 'asc')->orderBy('kuantitas', 'desc'); 
        };

        $data = BerantasUngkapTersangka::query()
            ->with([
                'kasus.satuanKerja', 
                'barangBukti' => $bbConstraint, 
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

        foreach ($data as $t) {
            foreach ($t->barangBukti as $bb) {
                if ($bb->kategori === 'Narkotika') {
                    $this->hasNarkotikaInData = true;
                    break 2;
                }
            }
        }

        $this->calculateSmartSuffixes($data);
        return $data;
    }

    /**
     * LOGIKA CERDAS (SIMETRIS VS ASIMETRIS)
     */
    private function calculateSmartSuffixes($data)
    {
        $groupedByLkn = $data->groupBy(fn($item) => $item->kasus->nomor_lkn);

        foreach ($groupedByLkn as $lkn => $tersangkas) {
            
            // 1. Petakan Inventory Lengkap Setiap Tersangka di Kasus Ini
            // Array [ID_Tersangka => "10-11-12"] (String ID Barang Bukti)
            $suspectInventories = [];
            foreach ($tersangkas as $t) {
                $suspectInventories[$t->id] = $t->barangBukti->pluck('id')->sort()->values()->implode('-');
            }

            $ownerGroupMap = []; 
            $groupCounter = 1;
            
            // Ambil semua BB unik di kasus ini
            $allEvidenceInCase = $tersangkas->flatMap->barangBukti->unique('id');

            foreach ($allEvidenceInCase as $bb) {
                $owners = $bb->tersangka;

                // Jika Sharing (Pemilik > 1)
                if ($owners->count() > 1) {
                    
                    // Signature Group (misal: "1-2")
                    $signature = $owners->pluck('id')->sort()->values()->implode('-');

                    if (!isset($ownerGroupMap[$signature])) {
                        $ownerGroupMap[$signature] = $groupCounter++;
                    }
                    $groupNum = $ownerGroupMap[$signature];

                    // --- CEK KESIMETRISAN ---
                    // Apakah SEMUA pemilik barang ini punya daftar barang yang SAMA PERSIS?
                    $isSymmetric = true;
                    $firstOwnerInv = $suspectInventories[$owners->first()->id] ?? '';
                    
                    foreach ($owners as $owner) {
                        $currentInv = $suspectInventories[$owner->id] ?? '';
                        if ($currentInv !== $firstOwnerInv) {
                            $isSymmetric = false; // Beda isi tas (Asimetris)
                            break;
                        }
                    }

                    // Simpan Info Suffix
                    $this->bbSuffixInfo[$bb->id] = [
                        'sort_key' => "_{$groupNum}",      // Key untuk grouping/sorting
                        'label' => " [b{$groupNum}]",       // Teks Label
                        'hide_label' => $isSymmetric        // Hapus label JIKA simetris
                    ];

                } else {
                    // Barang Pribadi (Milik Sendiri)
                    $this->bbSuffixInfo[$bb->id] = [
                        'sort_key' => "_999", 
                        'label' => "", 
                        'hide_label' => true
                    ];
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

        $items = [];
        
        foreach ($row->barangBukti as $bb) {
            // Ambil Info Suffix
            $info = $this->bbSuffixInfo[$bb->id] ?? ['sort_key' => '_999', 'label' => '', 'hide_label' => true];
            
            // LOGIKA TAMPILAN:
            // Jika Simetris (hide_label=true) -> Label Kosong "" -> Nanti Auto Merge
            // Jika Asimetris (hide_label=false) -> Label Muncul "[b1]" -> Tidak Merge (Duplikat Tampil)
            $suffixStr = $info['hide_label'] ? "" : $info['label'];

            $namaBB = $bb->nama_barang; 
            if ($this->hasNarkotikaInData && $bb->kategori !== 'Narkotika') {
                $namaBB .= " (non-narkotika)";
            }

            $satuan = $bb->satuan;
            $kuantitas = (float)$bb->kuantitas;
            $gramValue = $kuantitas;
            if ($satuan === 'Kg') $gramValue = $kuantitas * 1000;
            elseif ($satuan === 'Ton') $gramValue = $kuantitas * 1000000;

            $items[] = [
                'tampilan_jenis' => $namaBB . $suffixStr,
                'tampilan_berat' => $gramValue . ' Gram' . $suffixStr,
                'sort_key' => $info['sort_key'],
                'raw_name' => $namaBB
            ];

            if ($bb->kategori === 'Narkotika' && !in_array($bb->id, $this->processedBBIds)) {
                $jenisKey = strtoupper(trim($bb->nama_barang));
                if (!isset($this->totals[$jenisKey])) $this->totals[$jenisKey] = 0;
                $this->totals[$jenisKey] += $gramValue;
                $this->processedBBIds[] = $bb->id;
            }
        }

        // --- SORTING ---
        usort($items, function($a, $b) {
            // 1. Grouping [b1] vs [b2] vs Pribadi
            if ($a['sort_key'] !== $b['sort_key']) {
                return strcmp($a['sort_key'], $b['sort_key']);
            }
            // 2. Nama Barang
            return strcmp($a['raw_name'], $b['raw_name']);
        });

        $strJenis = [];
        $strBerat = [];
        
        if (empty($items)) {
            $strJenis[] = '-'; $strBerat[] = '-';
        } else {
            foreach ($items as $i) {
                $strJenis[] = $i['tampilan_jenis'];
                $strBerat[] = $i['tampilan_berat'];
            }
        }

        return [
            $no, $lknStr, $row->nama_tersangka, $row->jenis_kelamin, 
            $row->pekerjaan ?? '-', $row->kasus->alamat_tkp, 
            implode("\n", $strJenis), implode("\n", $strBerat), '', $row->tahap 
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

                // --- SETTING LEBAR KOLOM ---
                $sheet->getColumnDimension('B')->setWidth(35);
                $sheet->getColumnDimension('F')->setWidth(40);
                $sheet->getColumnDimension('G')->setWidth(35);
                $sheet->getColumnDimension('H')->setWidth(25);
                // Lebar kolom Foto (I) 22 agar pas di tengah
                $sheet->getColumnDimension('I')->setWidth(22);
                
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
                    
                    // --- HITUNG TINGGI BARIS (TEKS) ---
                    $contentG = $sheet->getCell('G' . $currentRow)->getValue();
                    $contentH = $sheet->getCell('H' . $currentRow)->getValue();
                    $linesG = substr_count((string)$contentG, "\n") + 1;
                    $linesH = substr_count((string)$contentH, "\n") + 1;
                    $maxLines = max($linesG, $linesH);
                    $textHeight = ($maxLines * 18) + 10; 

                    // --- LOGIKA FOTO ---
                    $fotoPath = $data[$i]->foto_tersangka;
                    $hasPhoto = ($fotoPath && file_exists(storage_path('app/public/' . $fotoPath)));

                    if ($hasPhoto) {
                        $drawing = new Drawing();
                        $drawing->setPath(storage_path('app/public/' . $fotoPath));
                        
                        // [FIX] Tinggi 50px (Proporsional)
                        $drawing->setHeight(50); 
                        $drawing->setResizeProportional(true);
                        $drawing->setCoordinates('I' . $currentRow);
                        
                        // [FIX] Center Offset
                        $drawing->setOffsetX(10); 
                        $drawing->setOffsetY(5); 
                        
                        $drawing->setWorksheet($sheet);
                        $finalHeight = max(60, $textHeight);
                    } else {
                        $finalHeight = max(40, $textHeight);
                    }
                    $sheet->getRowDimension($currentRow)->setRowHeight($finalHeight);

                    // --- LOGIKA MERGE ---
                    if (($i === $rowCount - 1) || ($data[$i]->kasus->nomor_lkn !== $data[$i+1]->kasus->nomor_lkn)) {
                        $endRow = $currentRow;
                        if ($mergeStart < $endRow) {
                            $sheet->mergeCells("A{$mergeStart}:A{$endRow}"); 
                            $sheet->mergeCells("B{$mergeStart}:B{$endRow}"); 
                            $sheet->mergeCells("F{$mergeStart}:F{$endRow}"); 
                        }
                        
                        // Auto Merge hanya jika isinya SAMA PERSIS (Kasus Simetris)
                        // Kasus Asimetris (Ada barang lain) teksnya beda, jadi tidak akan di-merge.
                        $this->smartMergeInner($sheet, 'G', $mergeStart, $endRow);
                        $this->smartMergeInner($sheet, 'H', $mergeStart, $endRow);
                        $this->smartMergeInner($sheet, 'E', $mergeStart, $endRow);

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
                $footerLines = count($totalString);
                $sheet->getRowDimension($footerRow)->setRowHeight(max(30, ($footerLines * 18) + 10));

                $sheet->getStyle("A{$footerRow}:J{$footerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$footerRow}:J{$footerRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
                $sheet->getStyle("A1:J{$footerRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("H{$footerRow}")->getAlignment()->setWrapText(true);
            },
        ];
    }

    private function smartMergeInner($sheet, $col, $start, $end)
    {
        $mStart = $start;
        for ($r = $start; $r < $end; $r++) {
            $currVal = $sheet->getCell("$col$r")->getValue();
            $nextVal = $sheet->getCell("$col" . ($r + 1))->getValue();

            // Hanya merge jika Value-nya SAMA PERSIS
            if ($currVal !== $nextVal) {
                if ($mStart < $r) $sheet->mergeCells("$col$mStart:$col$r");
                $mStart = $r + 1;
            }
        }
        if ($mStart < $end) $sheet->mergeCells("$col$mStart:$col$end");
    }
}