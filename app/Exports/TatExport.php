<?php
namespace App\Exports;

use App\Models\BerantasTatTersangka;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\{Alignment, Border, Fill};

class TatExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles {
    protected $query;
    public function __construct($query) { $this->query = $query; }

    public function collection() {
        return BerantasTatTersangka::with(['tat.satuanKerja', 'tat.barangBukti.narkotika'])
               ->whereIn('berantas_tat_id', $this->query->pluck('id'))->get();
    }

    public function headings(): array {
        return ['No Register', 'Tanggal', 'Satker', 'Nama TSK', 'NIK', 'JK', 'Usia', 'No Telp', 'Instansi', 'Narkotika (Gram)', 'Non-Narkotika', 'Biaya'];
    }

    public function map($row): array {
        $narkoba = []; $nonNarkoba = [];
        foreach ($row->tat->barangBukti as $bb) {
            if ($bb->kategori === 'Narkotika') {
                $qty = (float)$bb->kuantitas;
                if ($bb->satuan === 'Kg') $qty *= 1000;
                if ($bb->satuan === 'Ton') $qty *= 1000000;
                $narkoba[] = $bb->nama_barang . " (" . $qty . " Gram)";
            } else {
                $nonNarkoba[] = $bb->nama_barang_non_narkotika . " (" . (float)$bb->kuantitas . " " . $bb->satuan . ")";
            }
        }
        return [
            $row->tat->no_register, $row->tat->tanggal_pelaksanaan->format('d-m-Y'),
            $row->tat->satuanKerja->satuan_kerja ?? '-', $row->nama_tersangka, " ".$row->nik, $row->jenis_kelamin,
            $row->usia, $row->no_telepon, $row->tat->instansi_pengirim,
            implode("\n", $narkoba), implode("\n", $nonNarkoba), $row->tat->biaya
        ];
    }

    public function styles(Worksheet $sheet) {
        $sheet->getStyle('A1:L1000')->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        return [1 => ['font'=>['bold'=>true,'color'=>['rgb'=>'FFFFFF']], 'fill'=>['fillType'=>Fill::FILL_SOLID, 'startColor'=>['rgb'=>'4472C4']]]];
    }
}