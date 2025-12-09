<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class P2mSafariReligi extends Model
{
    use HasFactory;

    protected $table = 'p2m_safari_religi';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];

    /**
     * Relasi ke Satuan Kerja (Many to One)
     */
    public function satuanKerja(): BelongsTo
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }

    /**
     * Relasi ke Pegawai (Many to Many)
     */
    public function pegawai(): BelongsToMany
    {
        return $this->belongsToMany(
            Pegawai::class, 
            'pegawai_p2m_safari_religi', // Nama tabel pivot
            'p2m_safari_religi_id', 
            'pegawai_nip', 
            'id', 
            'nip'
        )->withTimestamps();
    }
}