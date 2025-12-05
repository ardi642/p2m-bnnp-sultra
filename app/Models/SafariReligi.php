<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SafariReligi extends Model
{
    use HasFactory;

    protected $table = 'safari_religi';

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
        'link_kelengkapan_dokumentasi',
    ];

    /**
     * Relasi ke tabel satuan_kerja
     */
    public function satuan_kerja()
    {
        return $this->belongsTo(SatuanKerja::class, 'satker');
    }
}