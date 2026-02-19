<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDokumen;

class RehabLaporan extends Model
{
    use HasFactory;
    use HasDokumen;

    protected $table = 'rehab_laporan';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function getTanggalTextAttribute() {
        return $this->tanggal ? $this->tanggal->locale('id')->translatedFormat('d M Y') : '-';
    }

    public function satuanKerja() {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }

}