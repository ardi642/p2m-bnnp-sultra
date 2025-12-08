<?php
namespace App\Exports;

use App\Models\P2mDesaBersinar;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DesaBersinarExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
            'Satuan Kerja',
            'Anggaran Pembentukan',
            'Nama Lokasi (Desa – Kel – Kab/Kota)',
            'Tanggal Pencanangan',
            'Jumlah Penggiat P4GN',
            'IBM Terbentuk',
            'Penanggung Jawab',
            'No HP Penanggung Jawab',
            'Link Dokumentasi',
            'Dibuat Pada'
        ];
    }

    public function map($row): array
    {
        $pegawaiNames = $row->pegawai->pluck('nama')->implode(', ');
        $lokasi = "{$row->nama_desa} – {$row->nama_kelurahan} – {$row->kabupatenKota->nama}";

        return [
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->anggaran_pembentukan,
            $lokasi,
            $row->tanggal_pencanangan->translatedFormat('d F Y'),
            $row->jumlah_penggiat,
            $row->keberadaan_ibm === 'ada' ? 'Ya' : 'Belum',
            $pegawaiNames,
            $row->nomor_hp_penanggung_jawab,
            $row->link_kelengkapan_dokumentasi,
            $row->created_at->translatedFormat('d F Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}