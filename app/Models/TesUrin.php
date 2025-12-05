<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TesUrin extends Model
{
    use HasFactory;

    protected $table = 'tes_urin';

    protected $fillable = [
        'satker_id',
        'anggaran_pelaksanaan',
        'sasaran_kegiatan',
        'nama_instansi_pelaksana',
        'tanggal_pelaksanaan',
        'nama_katim',
        'link_kelengkapan_dokumentasi',
        'jumlah_peserta_test_urin',
        'jumlah_terindikasi_positif',
        'keterangan_parameter_positif',
    ];

    public function satuanKerja()
    {
        return $this->belongsTo(SatuanKerja::class, 'satker_id');
    }
}