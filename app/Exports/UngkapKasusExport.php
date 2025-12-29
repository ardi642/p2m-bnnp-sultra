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
    public function collection()
    {
        return BerantasUngkapTersangka::with([
            'kasus.satuanKerja', 
            'kasus.barangBuktiBersama', 
            'barangBukti'
        ])
        ->join('berantas_ungkap_kasus', 'berantas_ungkap_tersangka.berantas_ungkap_kasus_id', '=', 'berantas_ungkap_kasus.id')
        ->orderBy('berantas_ungkap_kasus.tanggal_kejadian', 'desc')
        ->orderBy('berantas_ungkap_kasus.id')
        ->orderBy('berantas_ungkap_tersangka.id')
        ->select('berantas_ungkap_tersangka.*')
        ->get();
    }

    public function headings(): array
    {
        return ['Nomor LKN', 'Tanggal Kejadian', 'Satuan Kerja', 'TKP', 'Nama Tersangka', 'Jenis Kelamin', 'Pekerjaan', 'Barang Bukti', 'Status Tahap', 'Foto Tersangka'];
    }

    public function map($row): array
    {
        // Gabungkan BB Bersama & Personal dalam satu sel
        $listItems = [];
        foreach($row->kasus->barangBuktiBersama as $bb) {
            $listItems[] = "[BERSAMA] " . $bb->jenis_barang_bukti . ' (' . $bb->jumlah_barang_bukti . ' ' . $bb->satuan_barang_bukti . ')';
        }
        foreach($row->barangBukti as $bb) {
            $listItems[] = $bb->jenis_barang_bukti . ' (' . $bb->jumlah_barang_bukti . ' ' . $bb->satuan_barang_bukti . ')';
        }
        $stringBB = empty($listItems) ? '-' : implode("\n", $listItems);

        return [
            $row->kasus->nomor_lkn,
            $row->kasus->tanggal_kejadian->format('d-m-Y'),
            $row->kasus->satuanKerja->satuan_kerja ?? 'BNNP',
            $row->kasus->alamat_tkp,
            $row->nama_tersangka,
            $row->jenis_kelamin,
            $row->pekerjaan ?? '-',
            $stringBB,
            $row->status_tahap,
            '' 
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('D')->getAlignment()->setWrapText(true);
        $sheet->getStyle('H')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A:J')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A:J')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        return [ 1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4e73df']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]] ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $rows = $this->collection();
                $rowCount = $rows->count();
                
                $currentRow = 2;
                $startRowLkn = 2;
                $startRowBB = 2;
                $prevLkn = null;
                $prevBB = null;
                $prevLknForBB = null;

                foreach ($rows as $index => $tersangka) {
                    $sheet->getRowDimension($currentRow)->setRowHeight(80);
                    if ($tersangka->foto_tersangka && file_exists(storage_path('app/public/' . $tersangka->foto_tersangka))) {
                        $drawing = new Drawing();
                        $drawing->setPath(storage_path('app/public/' . $tersangka->foto_tersangka));
                        $drawing->setHeight(90);
                        $drawing->setCoordinates('J' . $currentRow);
                        $drawing->setOffsetX(10); $drawing->setOffsetY(10);
                        $drawing->setWorksheet($sheet);
                    } else {
                        $sheet->setCellValue('J' . $currentRow, 'Tidak Ada Foto');
                        $sheet->getStyle('J' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    $currLkn = $tersangka->kasus->nomor_lkn;
                    $currBB = $sheet->getCell("H{$currentRow}")->getValue();

                    // --- MERGE LKN ---
                    if (($currLkn !== $prevLkn && $index > 0) || $index === $rowCount - 1) {
                        $endRow = ($currLkn !== $prevLkn && $index > 0) ? $currentRow - 1 : $currentRow;
                        if ($startRowLkn < $endRow) {
                            $sheet->mergeCells("A{$startRowLkn}:A{$endRow}");
                            $sheet->mergeCells("B{$startRowLkn}:B{$endRow}");
                            $sheet->mergeCells("C{$startRowLkn}:C{$endRow}");
                            $sheet->mergeCells("D{$startRowLkn}:D{$endRow}");
                        }
                        $startRowLkn = $currentRow;
                    }

                    // --- MERGE BB SEQUENTIAL (Jika isi sama persis & LKN sama) ---
                    $isDifferentBB = ($currBB !== $prevBB);
                    $isDifferentLKN = ($currLkn !== $prevLknForBB);
                    $isLastRow = ($index === $rowCount - 1);

                    if (($isDifferentBB || $isDifferentLKN) && $index > 0) {
                        $endRowBB = $currentRow - 1;
                        if ($startRowBB < $endRowBB) $sheet->mergeCells("H{$startRowBB}:H{$endRowBB}");
                        $startRowBB = $currentRow;
                    }
                    if ($isLastRow) {
                        if (!$isDifferentBB && !$isDifferentLKN && $startRowBB < $currentRow) $sheet->mergeCells("H{$startRowBB}:H{$currentRow}");
                    }

                    $prevLkn = $currLkn;
                    $prevLknForBB = $currLkn;
                    $prevBB = $currBB;
                    $currentRow++;
                }
                $sheet->getStyle('A1:J' . ($currentRow - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}