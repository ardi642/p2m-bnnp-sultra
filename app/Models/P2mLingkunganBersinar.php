<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasDokumentasi; 

class P2mLingkunganBersinar extends Model
{
    use HasFactory, HasDokumentasi;

    protected $table = 'p2m_lingkungan_bersinar';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_pencanangan' => 'date'
    ];

    /**
     * Hapus dokumentasi fisik & DB saat data kegiatan dihapus
     */
    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($kegiatan) {
            $kegiatan->dokumentasi()->delete(); 
        });
    }

    public function satuanKerja(): BelongsTo
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }

    public function pegawai(): BelongsToMany
    {
        return $this->belongsToMany(
            Pegawai::class, 
            'pegawai_p2m_lingkungan_bersinar', // Nama Tabel Pivot
            'p2m_lingkungan_bersinar_id',      // FK Kegiatan
            'pegawai_nip',                     // FK Pegawai
            'id', 
            'nip'
        )
        ->withPivot('saved_satuan_kerja_id')
        ->withTimestamps();
    }
}