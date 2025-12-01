<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class p2mOnline extends Model
{
    protected $table = 'p2m_online';
    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];
    protected $guarded = [];

    public function satuanKerja() {
        return $this->belongsTo(satuanKerja::class, 'satuan_kerja_id');
    }
}



