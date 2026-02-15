<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithChunkReading; // <--- WAJIB UNTUK DATA BESAR
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class RehabPasienExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithChunkReading
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

    // --- OPTIMASI MEMORI (CHUNKING) ---
    // Excel akan memproses data per 1.000 baris, lalu membersihkan memori.
    // Ini membuat export 1 juta baris pun tetap ringan.
    public function chunkSize(): int
    {
        return 1000;
    }

    public function headings(): array
    {
        return [
            'Satuan Kerja',
            'Nama Pasien',
            'Jenis Kelamin',
            'Usia',
            'Pekerjaan',
            'Pendidikan',
            'Nama Narkotika',
            'Sumber Pasien',
            'Dibuat Pada'
        ];
    }

    public function map($row): array
    {
        return [
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->nama_pasien,
            $row->jenis_kelamin,
            $row->usia,
            $row->pekerjaan,
            $row->pendidikan,
            $row->narkotika->nama_narkotika ?? '-',
            $row->sumber_pasien,
            $row->created_at->locale('id')->translatedFormat('d F Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Format agar kolom Pegawai bisa Multi-line (Enter terbaca)
        $sheet->getStyle('G')->getAlignment()->setWrapText(true);
        
        // Format agar semua teks rata atas (rapi)
        $sheet->getStyle('A:I')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        return [
            1 => ['font' => ['bold' => true]], // Header Bold
        ];
    }
}