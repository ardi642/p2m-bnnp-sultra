<?php

namespace App\Exports;

use App\Models\P2mUpacara;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class UpacaraExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
            'Anggaran',
            'Nama Sekolah',
            'Tanggal Pelaksanaan',
            'Pegawai',
            'Jumlah Peserta',
            'Dibuat Pada'
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    // Mapping Data per Baris
    public function map($row): array
    {
        // --- LOGIKA PEGAWAI (Menampilkan Status Pindah) ---
        $listPegawai = [];
        
        foreach ($row->pegawai as $pegawai) {
            $info = $pegawai->nama;
            
            // Tambahkan NIP jika ada
            if ($pegawai->nip) {
                $info .= " ({$pegawai->nip})";
            }

            // CEK STATUS PINDAH (Sesuai Logika di View)
            // Jika Satker Pegawai Saat Ini != Satker Pemilik Kegiatan
            if ($pegawai->satuan_kerja_id != $row->satuan_kerja_id) {
                $satkerBaru = $pegawai->satuanKerja->satuan_kerja ?? 'Luar Satker';
                // Tambahkan keterangan pindah
                $info .= " [Pindah ke: $satkerBaru]";
            }

            $listPegawai[] = $info;
        }

        // Gabungkan semua nama pegawai dengan Enter (New Line)
        $pegawaiString = implode("\n", $listPegawai);

        return [
            $row->satuanKerja->satuan_kerja ?? '-',
            $row->anggaran_pelaksanaan,
            $row->nama_sekolah,
            $row->tanggal_pelaksanaan->locale('id')->translatedFormat('d F Y'), // Format tanggal Indo
            $pegawaiString,
            $row->jumlah_peserta_upacara,
            $row->created_at->locale('id')->translatedFormat('d F Y H:i'),
        ];
    }

    // Styling Header (Bold)
    public function styles(Worksheet $sheet)
    {
        // Format agar kolom Pegawai bisa Multi-line (Enter terbaca)
        $sheet->getStyle('E')->getAlignment()->setWrapText(true);

        // Format agar semua teks rata atas (rapi)
        $sheet->getStyle('A:H')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}