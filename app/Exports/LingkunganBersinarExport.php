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

class LingkunganBersinarExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithChunkReading
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
            'Sasaran Kegiatan',
            'Nama Tempat/Wilayah',
            'Tanggal Pencanangan',
            'Jumlah Penggiat P4GN',
            'Penanggung Jawab Wilayah',
            'No HP Penanggung Jawab',
            'Dibuat Pada'
        ];
    }

    public function map($row): array
    {
        // Format list Penanggung Jawab
        $listPegawai = [];
        foreach ($row->pegawai as $pegawai) {
            $info = $pegawai->nama . " (" . $pegawai->nip . ")";
            // Cek status pindah satker
            if ($pegawai->satuan_kerja_id != $row->satuan_kerja_id) {
                $satkerBaru = $pegawai->satuanKerja->satuan_kerja ?? 'Luar Satker';
                $info .= " [Pindah ke: $satkerBaru]";
            }
            $listPegawai[] = $info;
        }

        return [
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->anggaran_pelaksanaan,
            $row->sasaran_kegiatan,
            $row->nama_tempat_wilayah,
            $row->tanggal_pencanangan->locale('id')->translatedFormat('d F Y'),
            $row->jumlah_penggiat_p4gn . ' Orang',
            implode("\n", $listPegawai),
            $row->no_hp_penanggung_jawab ?? '-',
            $row->created_at->locale('id')->translatedFormat('d F Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('G')->getAlignment()->setWrapText(true); // Kolom PJ Wrap Text
        $sheet->getStyle('A:I')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}