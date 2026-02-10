<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasDokumen;

class P2mElektronik extends Model
{
    use HasFactory, HasDokumen;

    protected $table = 'p2m_elektronik';

    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];

    protected $guarded = [];

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

    // Tidak ada relasi pegawai()
}