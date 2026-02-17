<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasDokumen;

class P2mCfd extends Model
{
    use HasFactory, HasDokumen;

    protected $table = 'p2m_cfd';

    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];

    protected $guarded = [];

    public function satuanKerja(): BelongsTo
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }

    public function pegawai(): BelongsToMany
    {
        return $this->belongsToMany(
            Pegawai::class, 
            'pegawai_p2m_cfd', 
            'p2m_cfd_id', 
            'pegawai_nip', 
            'id', 
            'nip'
        )
        ->withPivot('saved_satuan_kerja_id')
        ->withTimestamps();
    }
}