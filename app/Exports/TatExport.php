<?php

namespace App\Exports;

use App\Models\BerantasTatTersangka;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TatExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $query;
    protected $filters;

    public function __construct($query)
    {
        $this->query = $query;
        $kategori = request('kategori_bb', []); 
        $this->filters = is_array($kategori) ? array_filter($kategori) : [$kategori];
    }

    public function collection()
    {
        $tatIds = $this->query->pluck('id')->toArray();

        if (empty($tatIds)) {
            return collect([]);
        }

        $idsString = implode(',', $tatIds);
        
        return BerantasTatTersangka::query()
            ->with(['tat.satuanKerja', 'tat.barangBukti.narkotika'])
            ->whereIn('berantas_tat_id', $tatIds)
            ->orderByRaw("FIELD(berantas_tat_id, $idsString)")
            ->get();
    }

    public function headings(): array
    {
        return [
            'NOMOR REGISTER',           // A
            'TANGGAL PELAKSANAAN',      // B
            'NAMA TSK',                 // C
            'JENIS KELAMIN',            // D
            'USIA',                     // E
            'PENDIDIKAN',               // F
            'PEKERJAAN',                // G
            'PASAL YG DISANGKAKAN',     // H
            'TGL PENANGKAPAN',          // I
            'BARANG BUKTI',             // J
            'JUMLAH / BERAT',           // K
            'INSTANSI PENGIRIM',        // L
            'TGL PERMOHONAN',           // M
            'TIM HUKUM',                // N
            'TIM MEDIS',                // O
            'LEMBAGA REHAB',            // P
            'PROSES HUKUM LANJUT',      // Q
            'TINDAK LANJUT REKOMENDASI',// R
            'BIAYA YG DIKELUARKAN',     // S
            'NIK',                      // T
            'NO TELEPON'                // U
        ];
    }

    public function map($row): array
    {
        $listNamaBB = [];
        $listJumlahBB = [];

        // --- 1. LOGIK BARANG BUKTI ---
        $isStrictNonNarkotika = (count($this->filters) === 1 && in_array('Non-Narkotika', $this->filters));

        if ($row->tat && $row->tat->barangBukti) {
            foreach ($row->tat->barangBukti as $bb) {
                
                $showNarkotika = empty($this->filters) || in_array('Narkotika', $this->filters);
                $showNonNarkotika = empty($this->filters) || in_array('Non-Narkotika', $this->filters);

                if ($bb->kategori === 'Narkotika' && $showNarkotika) {
                    $nama = $bb->narkotika ? $bb->narkotika->nama_narkotika : 'Narkotika Lain';
                    
                    $qty = (float)$bb->kuantitas;
                    $satuanAsli = strtoupper(trim($bb->satuan));

                    if ($satuanAsli === 'KG' || $satuanAsli === 'KILOGRAM') {
                        $qty *= 1000;
                    } elseif ($satuanAsli === 'TON') {
                        $qty *= 1000000;
                    }
                    
                    $listNamaBB[] = $nama; 
                    $listJumlahBB[] = $qty . ' Gram'; 

                } elseif ($bb->kategori === 'Non-Narkotika' && $showNonNarkotika) {
                    if ($isStrictNonNarkotika) {
                        $namaBarang = $bb->nama_barang_non_narkotika;
                    } else {
                        $namaBarang = $bb->nama_barang_non_narkotika . ' (Non-Narkotika)';
                    }

                    $listNamaBB[] = $namaBarang;
                    $listJumlahBB[] = (float)$bb->kuantitas . ' ' . $bb->satuan;
                }
            }
        }

        if (empty($listNamaBB)) {
            $listNamaBB[] = '-';
            $listJumlahBB[] = '-';
        }

        // --- 2. LOGIK TIM HUKUM (JSON to String) ---
        $timHukumString = '-';
        if (!empty($row->tat->tim_hukum) && is_array($row->tat->tim_hukum)) {
            // Mapping: "Nama (Instansi)"
            $formattedHukum = array_map(function($t) {
                return ($t['nama'] ?? '') . (isset($t['instansi']) ? ' (' . $t['instansi'] . ')' : '');
            }, $row->tat->tim_hukum);
            $timHukumString = implode("\n", $formattedHukum);
        }

        // --- 3. LOGIK TIM MEDIS (JSON to String) ---
        $timMedisString = '-';
        if (!empty($row->tat->tim_medis) && is_array($row->tat->tim_medis)) {
            // Mapping: "Nama"
            $formattedMedis = array_map(function($t) {
                return $t['nama'] ?? '';
            }, $row->tat->tim_medis);
            $timMedisString = implode("\n", $formattedMedis);
        }

        $formatDate = function($date) {
            return $date ? \Carbon\Carbon::parse($date)->locale('id')->translatedFormat('d F Y') : '-';
        };

        return [
            $row->tat->no_register ?? '-',
            $formatDate($row->tat->tanggal_pelaksanaan),
            $row->nama_tersangka,
            $row->jenis_kelamin,
            $row->usia . ' Tahun',
            $row->pendidikan,
            $row->pekerjaan,
            $row->tat->pasal_disangkakan ?? '-',
            $formatDate($row->tat->tanggal_penangkapan),
            implode("\n", $listNamaBB),
            implode("\n", $listJumlahBB),
            $row->tat->instansi_pengirim ?? '-',
            $formatDate($row->tat->tanggal_permohonan),
            
            // Kolom Hasil JSON
            $timHukumString, // N
            $timMedisString, // O
            
            $row->tat->lembaga_rehab ?? '-',
            $row->tat->proses_hukum_lanjut ?? '-',
            strtoupper($row->tat->tindak_lanjut_rekomendasi ?? '-'),
            'Rp ' . number_format($row->tat->biaya ?? 0, 0, ',', '.'),
            "'" . $row->nik,
            $row->no_telepon
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle('A1:U1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC000']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $sheet->getStyle('A2:U' . $lastRow)->applyFromArray([
            'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();
                
                // Kolom untuk Merge
                $columnsToMerge = ['A', 'B', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S'];
                $startRow = 2; 

                for ($row = 2; $row <= $highestRow; $row++) {
                    $currentReg = $sheet->getCell('A' . $row)->getValue();
                    $nextReg = ($row < $highestRow) ? $sheet->getCell('A' . ($row + 1))->getValue() : null;

                    if ($currentReg != $nextReg) {
                        // 1. Merge Cells
                        if ($startRow < $row) {
                            foreach ($columnsToMerge as $col) {
                                $sheet->mergeCells($col . $startRow . ':' . $col . $row);
                            }
                        }

                        // 2. Hitung Tinggi Baris Otomatis (Smart Row Height)
                        // Kita cek kolom mana yang kontennya paling banyak barisnya
                        $bbNames = $sheet->getCell('J' . $startRow)->getValue();
                        $bbAmounts = $sheet->getCell('K' . $startRow)->getValue();
                        $timHukum = $sheet->getCell('N' . $startRow)->getValue();
                        $timMedis = $sheet->getCell('O' . $startRow)->getValue();
                        
                        $linesName = substr_count((string)$bbNames, "\n") + 1;
                        $linesAmount = substr_count((string)$bbAmounts, "\n") + 1;
                        $linesHukum = substr_count((string)$timHukum, "\n") + 1;
                        $linesMedis = substr_count((string)$timMedis, "\n") + 1;

                        // Ambil jumlah baris terbanyak
                        $maxTextLines = max($linesName, $linesAmount, $linesHukum, $linesMedis);

                        $rowCount = $row - $startRow + 1;

                        if ($maxTextLines > $rowCount) {
                            $sheet->getRowDimension($startRow)->setRowHeight($maxTextLines * 18);
                        } else {
                            $sheet->getRowDimension($startRow)->setRowHeight(-1);
                        }

                        $startRow = $row + 1;
                    }
                }
            },
        ];
    }
}