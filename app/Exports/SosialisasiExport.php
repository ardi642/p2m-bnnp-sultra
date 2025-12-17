<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SosialisasiExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    // Header Excel
    public function headings(): array
    {
        return [
            'Satuan Kerja',       // A
            'Anggaran',           // B
            'Nama Kegiatan',      // C
            'Sasaran',            // D
            'Tanggal Pelaksanaan',// E
            'Tempat',             // F
            'Pegawai',            // G
            'Jumlah Peserta',     // H
            'Dibuat Pada'         // I
        ];
    }

    // Mapping Data per Baris
    public function map($row): array
    {
        // Gabungkan Nama dan NIP
        $pegawaiData = $row->pegawai->map(function($p) {
            return $p->nip ? "{$p->nama} ({$p->nip})" : $p->nama;
        })->implode("\n");

        return [
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->anggaran_pelaksanaan,
            $row->nama_kegiatan,
            $row->sasaran_kegiatan,
            
            // PERBAIKAN DI SINI: Tambahkan ->locale('id')
            $row->tanggal_pelaksanaan->locale('id')->translatedFormat('d F Y'),
            
            $row->tempat_kegiatan,
            $pegawaiData,
            $row->jumlah_peserta,
            
            // PERBAIKAN DI SINI: Tambahkan ->locale('id')
            $row->created_at->locale('id')->translatedFormat('d F Y H:i'),
        ];
    }

    // Styling
    public function styles(Worksheet $sheet)
    {
        // Wrap text untuk kolom Pegawai (G)
        $sheet->getStyle('G')->getAlignment()->setWrapText(true);
        $sheet->getStyle('G')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}