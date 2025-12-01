<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class P2mUpacara extends Model
{
    protected $table = 'p2m_upacara';
    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];
    protected $guarded = [];

    public function satuanKerja() {
        return $this->belongsTo(satuanKerja::class, 'satuan_kerja_id');
    }
}
