<?php

namespace App\Exports;

use App\Models\P2mLingkungan;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LingkunganExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $query;

    // Kita terima Query Builder yang sudah difilter dari Controller
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
            'Satuan Kerja',
            'Sasaran',
            'Nama Tempat',
            'Tanggal Pelaksanaan',
            'Jumlah Penggiat',
            'Pegawai Penanggung Jawab',
            'Nomor HP Penanggung Jawab',
            'Link Dokumentasi',
            'Dibuat Pada'
        ];
    }

    // Mapping Data per Baris
    public function map($row): array
    {
        // Ambil nama pegawai dan gabungkan dengan koma
        $pegawaiNames = $row->pegawai->pluck('nama')->implode(', ');

        return [
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->sasaran,
            $row->nama_tempat,
            $row->tanggal_pelaksanaan->translatedFormat('d F Y'), // Format tanggal Indo
            $row->jumlah_penggiat,
            $pegawaiNames, // List pegawai
            $row->nomor_hp,
            $row->link_kelengkapan_dokumentasi,
            $row->created_at->translatedFormat('d F Y H:i'),
        ];
    }

    // Styling Header (Bold)
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}