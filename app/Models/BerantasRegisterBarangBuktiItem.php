<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BerantasRegisterBarangBuktiItem extends Model
{
    protected $table = 'berantas_register_barang_bukti_items';
    protected $guarded = ['id'];

    public function register() { 
        return $this->belongsTo(BerantasRegisterBarangBukti::class, 'register_barang_bukti_id'); 
    }
    
    public function narkotika() { 
        return $this->belongsTo(BerantasNarkotika::class, 'narkotika_id'); 
    }

    // Helper: Ambil nama barang otomatis
    public function getNamaBarangAttribute()
    {
        if ($this->kategori === 'Narkotika') {
            return $this->narkotika ? $this->narkotika->nama_narkotika : 'Unknown Narkotika';
        }
        return $this->nama_barang_non_narkotika;
    }

    // Helper: Ambil satuan otomatis
    public function getSatuanAttribute()
    {
        return $this->kategori === 'Narkotika' ? $this->satuan_narkotika : $this->satuan_non_narkotika;
    }
}