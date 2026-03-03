<?php

namespace App\Models;

use App\Traits\HasDokumen;
use App\Constants\KategoriPemberdayaan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class P2mPemberdayaan extends Model
{
    use HasFactory;
    use HasDokumen;

    protected $table = 'p2m_pemberdayaan';

    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];

    protected $guarded = [];

    public function getSubKegiatanLabelAttribute()
    {
        return KategoriPemberdayaan::SUB_KEGIATAN[$this->sub_kegiatan] ?? '-';
    }

    public function getDetailKegiatanLabelAttribute()
    {
        $allDetails = KategoriPemberdayaan::getAllDetailLabels();
        return $allDetails[$this->detail_kegiatan] ?? '-';
    }
    
    public function satuanKerja(): BelongsTo
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }

    public function pegawai(): BelongsToMany
    {
        return $this->belongsToMany(
            Pegawai::class,                 
            'pegawai_p2m_pemberdayaan', 
            'p2m_pemberdayaan_id',      
            'pegawai_nip',                  
            'id',                           
            'nip'                           
        )
        ->withPivot('saved_satuan_kerja_id')
        ->withTimestamps();
    }
}