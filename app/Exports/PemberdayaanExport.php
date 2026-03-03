<?php

namespace App\Exports;

use App\Constants\KategoriPemberdayaan;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PemberdayaanExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithChunkReading
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
            'Sub Kegiatan',
            'Detail Kegiatan',
            'Nama Kegiatan',
            'Anggaran',
            'Sasaran',
            'Tanggal Pelaksanaan',
            'Tempat',
            'Pegawai',           // Berada di Kolom I
            'Jumlah Peserta',
            'Dibuat Pada'
        ];
    }

    public function map($row): array
    {
        $listPegawai = [];
        
        // Mengurutkan data pegawai berdasarkan kolom 'nama' (A-Z)
        $pegawaiBerurut = $row->pegawai->sortBy('nama');

        foreach ($pegawaiBerurut as $pegawai) {
            // Langsung masukkan nama pegawai tanpa nomor urut
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

        // Menggabungkan array dengan chr(10) agar Excel membacanya sebagai baris baru (Alt+Enter)
        $pegawaiString = implode(chr(10), $listPegawai);

        // Ambil label yang rapi dari file Constant
        $subLabel = KategoriPemberdayaan::SUB_KEGIATAN[$row->sub_kegiatan] ?? $row->sub_kegiatan;
        
        $allDetails = KategoriPemberdayaan::getAllDetailLabels();
        $detailLabel = $allDetails[$row->detail_kegiatan] ?? $row->detail_kegiatan;

        return [
            $row->satuanKerja->satuan_kerja ?? '-',
            $subLabel,       
            $detailLabel,    
            $row->nama_kegiatan,
            $row->anggaran_pelaksanaan,
            ucwords($row->sasaran_kegiatan),
            $row->tanggal_pelaksanaan->locale('id')->translatedFormat('d F Y'),
            $row->tempat_kegiatan,
            $pegawaiString,  
            $row->jumlah_peserta,
            $row->created_at->locale('id')->translatedFormat('d F Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Ambil batas baris terakhir data
        $highestRow = $sheet->getHighestRow();

        // Aktifkan Wrap Text secara spesifik di Kolom I (Pegawai)
        $sheet->getStyle('I1:I' . $highestRow)->getAlignment()->setWrapText(true);

        // Format Rata Atas (Vertical Align Top) untuk semua kolom (A sampai K)
        $sheet->getStyle('A1:K' . $highestRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        return [
            // Headings dibuat Bold
            1 => ['font' => ['bold' => true]], 
        ];
    }
}