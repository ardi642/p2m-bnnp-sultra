<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasDokumentasi; // Pastikan Trait ini ada sesuai referensi Anda

class P2mUpacara extends Model
{
    use HasFactory, HasDokumentasi;

    protected $table = 'p2m_upacara';

    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];

    protected $guarded = ['id'];

    // Cleanup otomatis dokumentasi saat data dihapus
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
            'pegawai_p2m_upacara', // Nama tabel pivot
            'p2m_upacara_id', 
            'pegawai_nip', 
            'id', 
            'nip'
        )
        ->withPivot('saved_satuan_kerja_id')
        ->withTimestamps();
    }
}