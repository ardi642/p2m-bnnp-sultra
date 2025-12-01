<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class p2mElektronik extends Model
{
    protected $table = 'p2m_elektronik';
    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];
    protected $guarded = [];

    public function satuanKerja() {
        return $this->belongsTo(satuanKerja::class, 'satuan_kerja_id');
    }
}



