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

class PeranSertaMasyarakatExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithChunkReading
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
            'Anggaran',
            'Kategori Kegiatan',
            'Nama Kegiatan',
            'Tanggal Pelaksanaan',
            'Tempat',
            'Pegawai',
            'Jumlah Peserta',
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
            $row->anggaran_pelaksanaan,
            ucfirst(str_replace('_', ' ', $row->kategori_kegiatan)),
            $row->nama_kegiatan,
            $row->tanggal_pelaksanaan->locale('id')->translatedFormat('d F Y'),
            $row->tempat_kegiatan,
            $pegawaiString,
            $row->jumlah_peserta,
            $row->created_at->locale('id')->translatedFormat('d F Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('H')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A:J')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
