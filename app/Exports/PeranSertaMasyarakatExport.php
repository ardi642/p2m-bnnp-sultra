<?php

namespace App\Exports;

use App\Constants\KategoriPeranSertaMasyarakat;
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
            'Satuan Kerja',         // Kolom A
            'Anggaran',             // Kolom B
            'Kategori Kegiatan',    // Kolom C
            'Nama Kegiatan',        // Kolom D
            'Tanggal Pelaksanaan',  // Kolom E
            'Tempat',               // Kolom F
            'Pegawai',              // Kolom G
            'Jumlah Peserta',       // Kolom H
            'Dibuat Pada'           // Kolom I
        ];
    }

    public function map($row): array
    {
        $listPegawai = [];

        // Mengurutkan data pegawai berdasarkan nama (Abjad A-Z)
        $pegawaiBerurut = $row->pegawai->sortBy('nama');

        foreach ($pegawaiBerurut as $pegawai) {
            // Langsung masukkan nama tanpa nomor urut
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

        // Menggabungkan array dengan chr(10) agar di Excel menjadi baris baru (Alt+Enter)
        $pegawaiString = implode(chr(10), $listPegawai);

        // Ambil label yang rapi dari file Constant
        $kategoriLabel = KategoriPeranSertaMasyarakat::KATEGORI[$row->kategori_kegiatan] 
                         ?? ucfirst(str_replace('_', ' ', $row->kategori_kegiatan));

        return [
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->anggaran_pelaksanaan,
            $kategoriLabel,
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
        // Ambil batas baris terakhir untuk efisiensi render style
        $highestRow = $sheet->getHighestRow();

        // Aktifkan Wrap Text secara spesifik di Kolom G (Pegawai) agar Enter berfungsi
        $sheet->getStyle('G1:G' . $highestRow)->getAlignment()->setWrapText(true);

        // Format Rata Atas (Vertical Align Top) untuk semua kolom (A sampai I)
        $sheet->getStyle('A1:I' . $highestRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        return [
            // Headings dibuat Bold
            1 => ['font' => ['bold' => true]],
        ];
    }
}