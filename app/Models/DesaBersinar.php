<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DesaBersinar extends Model
{
    use SoftDeletes;

    protected $table = 'desa_bersinar';

    protected $fillable = [
        'satuan_kerja_id',
        'anggaran_pembentukan',
        'nama_desa',
        'nama_kecamatan',
        'kabupaten_kota',
        'tanggal_pencanangan',
        'bulan_pelaksanaan',
        'jumlah_penggiat_p4gn',
        'keberadaan_ibm',
        'nama_penanggung_jawab',
        'nomor_hp_penanggung_jawab',
        'link_kelengkapan_dokumentasi',
    ];

    protected $casts = [
        'tanggal_pencanangan' => 'date',
        'jumlah_penggiat_p4gn' => 'integer',
    ];

    public function satuanKerja()
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }
}