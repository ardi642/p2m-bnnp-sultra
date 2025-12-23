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

class DesaBersinarExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithChunkReading
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
            'Satuan Kerja', 'Anggaran', 'Kabupaten/Kota', 'Desa', 'Kelurahan', 'Tanggal Pencanangan',
            'Penanggung Jawab', 'No HP PJ', 'Jml Penggiat', 'Keberadaan IBM', 'Dibuat Pada'
        ];
    }

    public function map($row): array
    {
        $listPegawai = [];
        foreach ($row->pegawai as $pegawai) {
            $info = $pegawai->nama . " (" . $pegawai->nip . ")";
            if ($pegawai->satuan_kerja_id != $row->satuan_kerja_id) {
                $satkerBaru = $pegawai->satuanKerja->satuan_kerja ?? 'Luar Satker';
                $info .= " [Pindah ke: $satkerBaru]";
            }
            $listPegawai[] = $info;
        }

        return [
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->anggaran_pembentukan,
            $row->kabupatenKota->nama ?? '-',
            $row->nama_desa,
            $row->nama_kelurahan,
            $row->tanggal_pencanangan->locale('id')->translatedFormat('d F Y'),
            implode("\n", $listPegawai),
            $row->no_hp_penanggung_jawab ?? '-',
            $row->jumlah_penggiat,
            $row->keberadaan_ibm,
            $row->created_at->locale('id')->translatedFormat('d F Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('G')->getAlignment()->setWrapText(true); 
        $sheet->getStyle('A:K')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        return [ 1 => ['font' => ['bold' => true]] ];
    }
}