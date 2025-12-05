<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DesaBersinar extends Model
{
    use HasFactory;

    protected $table = 'desa_bersinar';

    protected $fillable = [
        'satker',
        'anggaran_pembentukan',
        'nama_desa',
        'nama_kecamatan',
        'nama_kota_kabupaten',
        'tanggal_pencanangan',
        'bulan_pelaksanaan',
        'jumlah_penggiat_p4gn',
        'keberadaan_ibm',
        'nama_penanggung_jawab',
        'nomor_hp_penanggung_jawab',
        'link_dokumentasi',
    ];

    public function satuanKerja()
    {
        return $this->belongsTo(SatuanKerja::class, 'satker');
    }
}