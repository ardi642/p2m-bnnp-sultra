<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class P2mTesUrine extends Model
{
    use HasFactory;

    protected $table = 'p2m_tes_urine';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];

    public function satuanKerja(): BelongsTo
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }

    public function pegawai(): BelongsToMany
    {
        return $this->belongsToMany(
            Pegawai::class, 
            'pegawai_p2m_tes_urine', // Nama tabel pivot
            'p2m_tes_urine_id', 
            'pegawai_nip', 
            'id', 
            'nip'
        )->withTimestamps();
    }
}