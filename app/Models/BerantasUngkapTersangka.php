<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BerantasUngkapTersangka extends Model
{
    protected $table = 'berantas_ungkap_tersangka';
    
    protected $guarded = ['id'];

    /**
     * Relasi Balik ke Kasus
     */
    public function kasus()
    {
        return $this->belongsTo(
            BerantasUngkapKasus::class, 
            'berantas_ungkap_kasus_id'
        );
    }

    /**
     * Relasi Many-to-Many ke Barang Bukti
     * (Untuk mengetahui tersangka ini memiliki barang bukti apa saja)
     */
    public function barangBukti()
    {
        return $this->belongsToMany(
            BerantasUngkapBarangBukti::class,
            'berantas_barang_bukti_tersangka', // Nama Tabel Pivot
            'tersangka_id',                    // FK di Pivot untuk model ini
            'barang_bukti_id'                  // FK di Pivot untuk model lawan
        );
    }
}