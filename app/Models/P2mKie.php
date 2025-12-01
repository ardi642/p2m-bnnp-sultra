<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class P2mKie extends Model
{
    protected $table = 'p2m_kie';
    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];
    protected $guarded = [];

    public function satuanKerja() {
        return $this->belongsTo(satuanKerja::class, 'satuan_kerja_id');
    }
}
