<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasDokumentasi; // Pastikan Trait ini ada sesuai kode sebelumnya

class P2mTesUrine extends Model
{
    use HasFactory;
    use HasDokumentasi; // Menggunakan trait polymorphic dokumentasi yang sama

    protected $table = 'p2m_tes_urine';

    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];

    protected $guarded = [];

    // Hapus dokumentasi otomatis saat data dihapus
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

    // Panitia Pelaksana
    public function pegawai(): BelongsToMany
    {
        return $this->belongsToMany(
            Pegawai::class, 
            'pegawai_p2m_tes_urine', 
            'p2m_tes_urine_id', 
            'pegawai_nip', 
            'id', 
            'nip'
        )
        ->withPivot('saved_satuan_kerja_id')
        ->withTimestamps();
    }
}