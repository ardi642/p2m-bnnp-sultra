<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasDokumentasi;

class P2mKie extends Model
{
    use HasFactory, HasDokumentasi;

    protected $table = 'p2m_kie';

    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];

    protected $guarded = ['id'];

    // Cleanup otomatis dokumentasi
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
            'pegawai_p2m_kie', 
            'p2m_kie_id', 
            'pegawai_nip', 
            'id', 
            'nip'
        )
        ->withPivot('saved_satuan_kerja_id')
        ->withTimestamps();
    }
}