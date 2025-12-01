<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SafariReligi extends Model
{
    protected $table = 'safari_religi';

    protected $fillable = [
        'satker',
        'pegawai',
        'tempat_kegiatan',
        'tanggal_pelaksanaan',
        'bulan_pelaksanaan',
        'jumlah_masyarakat',
        'link_dokumentasi',
    ];

    public function satuanKerja()
    {
        return $this->belongsTo(SatuanKerja::class, 'satker', 'id');
    }

    public function namapegawai()
    {
        return $this->belongsTo(Pegawai::class, 'pegawai', 'nip');
    }
}
