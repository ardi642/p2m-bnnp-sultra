<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class P2mMediaNonElektronik extends Model
{
    use HasFactory;
    
    protected $table = 'p2m_media_non_elektronik';

    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];

    protected $guarded = [];

    public function satuanKerja(): BelongsTo
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }
}