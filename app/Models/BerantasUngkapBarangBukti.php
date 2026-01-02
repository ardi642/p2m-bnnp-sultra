<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BerantasUngkapBarangBukti extends Model
{
    protected $table = 'berantas_ungkap_barang_bukti';
    protected $guarded = ['id'];

    // Relasi Many-to-Many ke Tersangka
    public function tersangka() {
        return $this->belongsToMany(
            BerantasUngkapTersangka::class, 
            'berantas_barang_bukti_tersangka', // Nama Tabel Pivot
            'barang_bukti_id', 
            'tersangka_id'
        );
    }
}