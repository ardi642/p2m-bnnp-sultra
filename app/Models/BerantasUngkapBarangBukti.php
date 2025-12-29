<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class BerantasUngkapBarangBukti extends Model
{
    protected $table = 'berantas_ungkap_barang_bukti';
    protected $guarded = [];
    
    public function tersangka()
    {
        return $this->belongsTo(BerantasUngkapTersangka::class, 'berantas_ungkap_tersangka_id');
    }
}