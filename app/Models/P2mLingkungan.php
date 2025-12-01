<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class P2mLingkungan extends Model
{
    protected $table = 'p2m_lingkungan';
    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];
    protected $guarded = [];

    public function satuanKerja() {
        return $this->belongsTo(satuanKerja::class, 'satuan_kerja_id');
    }
}
