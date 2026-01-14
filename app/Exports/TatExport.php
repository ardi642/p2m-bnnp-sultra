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
        // Ambil filter kategori yang sedang aktif
        $kategori = request('kategori_bb', []); 
        $this->filters = is_array($kategori) ? array_filter($kategori) : [$kategori];
    }

    public function collection()
    {
        // 1. Ambil ID Kasus
        $tatIds = $this->query->pluck('id')->toArray();

        if (empty($tatIds)) {
            return collect([]);
        }

        // 2. Ambil data Tersangka (Urut sesuai Index Tabel)
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
            'BARANG BUKTI',             // J (PERUBAHAN DISINI: Lebih Universal)
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

        // Deteksi apakah user sedang memfilter "Hanya Non-Narkotika"
        $isStrictNonNarkotika = (count($this->filters) === 1 && in_array('Non-Narkotika', $this->filters));

        if ($row->tat && $row->tat->barangBukti) {
            foreach ($row->tat->barangBukti as $bb) {
                
                // Cek Visibility berdasarkan Filter
                $showNarkotika = empty($this->filters) || in_array('Narkotika', $this->filters);
                $showNonNarkotika = empty($this->filters) || in_array('Non-Narkotika', $this->filters);

                // === KASUS 1: NARKOTIKA ===
                if ($bb->kategori === 'Narkotika' && $showNarkotika) {
                    $nama = $bb->narkotika ? $bb->narkotika->nama_narkotika : 'Narkotika Lain';
                    
                    // Konversi ke Gram
                    $qty = (float)$bb->kuantitas;
                    $satuanAsli = strtoupper(trim($bb->satuan));

                    if ($satuanAsli === 'KG' || $satuanAsli === 'KILOGRAM') {
                        $qty *= 1000;
                    } elseif ($satuanAsli === 'TON') {
                        $qty *= 1000000;
                    }
                    
                    $listNamaBB[] = $nama; 
                    $listJumlahBB[] = $qty . ' Gram'; 
                }

                // === KASUS 2: NON-NARKOTIKA ===
                elseif ($bb->kategori === 'Non-Narkotika' && $showNonNarkotika) {
                    
                    // LOGIKA PINTAR LABEL:
                    // Jika filter HANYA Non-Narkotika -> Tampilkan Nama Saja (Bersih)
                    // Jika filter CAMPUR (Kosong/Semua) -> Tambahkan label (Non-Narkotika)
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

        // Handle kosong
        if (empty($listNamaBB)) {
            $listNamaBB[] = '-';
            $listJumlahBB[] = '-';
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
            $row->tat->tim_hukum ?? '-',
            $row->tat->tim_medis ?? '-',
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

        // Header Style
        $sheet->getStyle('A1:U1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC000']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Body Style
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
                
                // Kolom untuk Merge (Semua data KASUS)
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
                        $bbNames = $sheet->getCell('J' . $startRow)->getValue();
                        $bbAmounts = $sheet->getCell('K' . $startRow)->getValue();
                        
                        $linesName = substr_count((string)$bbNames, "\n") + 1;
                        $linesAmount = substr_count((string)$bbAmounts, "\n") + 1;
                        $maxTextLines = max($linesName, $linesAmount);

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