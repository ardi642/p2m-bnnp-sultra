<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithChunkReading; // <--- WAJIB UNTUK DATA BESAR
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class IkanExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithChunkReading
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

    // --- OPTIMASI MEMORI (CHUNKING) ---
    // Excel akan memproses data per 1.000 baris, lalu membersihkan memori.
    // Ini membuat export 1 juta baris pun tetap ringan.
    public function chunkSize(): int
    {
        return 1000;
    }

    public function headings(): array
    {
        return [
            'Satuan Kerja',
            'Anggaran',
            'Nama Kegiatan',
            'Sasaran',
            'Tanggal Pelaksanaan',
            'Tempat',
            'Pegawai',
            'Jumlah Peserta',
            'Dibuat Pada'
        ];
    }

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
            $row->nama_kegiatan,
            $row->sasaran_kegiatan,
            $row->tanggal_pelaksanaan->locale('id')->translatedFormat('d F Y'),
            $row->tempat_kegiatan,
            $pegawaiString, // Kolom Pegawai yang sudah diformat
            $row->jumlah_peserta,
            $row->created_at->locale('id')->translatedFormat('d F Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Format agar kolom Pegawai bisa Multi-line (Enter terbaca)
        $sheet->getStyle('G')->getAlignment()->setWrapText(true);

        // Format agar semua teks rata atas (rapi)
        $sheet->getStyle('A:I')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        return [
            1 => ['font' => ['bold' => true]], // Header Bold
        ];
    }
}
