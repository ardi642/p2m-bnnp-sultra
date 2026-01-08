<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TatExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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

    public function headings(): array
    {
        return [
            'No Register', 'Tgl Pelaksanaan', 'Satuan Kerja', 'Nama TSK', 'NIK', 'JK', 
            'Usia/Pendidikan', 'Pekerjaan', 'No Telepon', 'Pasal', 'Tgl Penangkapan', 
            'Jenis Narkoba', 'Jumlah Satuan (Gram)', 'Instansi Pengirim', 'Tgl Permohonan', 
            'Tim Hukum', 'Tim Medis', 'Lembaga Rehab', 'Proses Hukum', 'Tindak Lanjut', 'Biaya'
        ];
    }

    public function map($row): array
    {
        $usia = $row->usia ? $row->usia . ' Thn' : '-';
        $pendidikan = $row->pendidikan ?? '-';
        $usiaPendidikan = $usia . '/' . $pendidikan;

        return [
            $row->no_register,
            $row->tanggal_pelaksanaan->format('d-m-Y'),
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->nama_tersangka,
            $row->nik,
            $row->jenis_kelamin,
            $usiaPendidikan,
            $row->pekerjaan,
            $row->no_telepon,
            $row->pasal_disangkakan,
            $row->tanggal_penangkapan ? $row->tanggal_penangkapan->format('d-m-Y') : '-',
            $row->jenis_narkoba,
            $row->jumlah_satuan,
            $row->instansi_pengirim,
            $row->tanggal_permohonan ? $row->tanggal_permohonan->format('d-m-Y') : '-',
            $row->tim_hukum,
            $row->tim_medis,
            $row->lembaga_rehab,
            $row->proses_hukum_lanjut,
            $row->tindak_lanjut_rekomendasi,
            $row->biaya
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A:U')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('A:U')->getAlignment()->setWrapText(true);
        
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
            ],
        ];
    }
}