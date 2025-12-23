<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SafariReligiExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithChunkReading
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

    public function chunkSize(): int
    {
        return 1000;
    }

    public function headings(): array
    {
        return [
            'Satuan Kerja',
            'Tanggal Pelaksanaan',
            'Tempat Kegiatan',
            'Pegawai',
            'Jumlah Masyarakat',
            'Dibuat Pada'
        ];
    }

    public function map($row): array
    {
        $listPegawai = [];
        foreach ($row->pegawai as $pegawai) {
            $info = $pegawai->nama;
            if ($pegawai->nip) {
                $info .= " ({$pegawai->nip})";
            }
            if ($pegawai->satuan_kerja_id != $row->satuan_kerja_id) {
                $satkerBaru = $pegawai->satuanKerja->satuan_kerja ?? 'Luar Satker';
                $info .= " [Pindah ke: $satkerBaru]";
            }
            $listPegawai[] = $info;
        }
        $pegawaiString = implode("\n", $listPegawai);

        return [
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->tanggal_pelaksanaan->locale('id')->translatedFormat('d F Y'),
            $row->tempat_kegiatan,
            $pegawaiString,
            $row->jumlah_masyarakat,
            $row->created_at->locale('id')->translatedFormat('d F Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('D')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A:F')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        return [
            1 => ['font' => ['bold' => true]], 
        ];
    }
}