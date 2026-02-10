<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasDokumen; 

class P2mDesaKelurahanBersinar extends Model
{
    use HasFactory, HasDokumen;

    protected $table = 'p2m_desa_kelurahan_bersinar';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_pencanangan' => 'date'
    ];

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($kegiatan) {
            $kegiatan->dokumen()->delete();
        });
    }

    public function satuanKerja(): BelongsTo
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }

    public function kabupatenKota(): BelongsTo
    {
        return $this->belongsTo(KabupatenKota::class, 'kabupaten_kota_id');
    }

    public function pegawai(): BelongsToMany
    {
        return $this->belongsToMany(
            Pegawai::class, 
            'pegawai_p2m_desa_kelurahan_bersinar', 
            'p2m_desa_kelurahan_bersinar_id', 
            'pegawai_nip', 
            'id', 
            'nip'
        )->withPivot('saved_satuan_kerja_id')->withTimestamps();
    }
}