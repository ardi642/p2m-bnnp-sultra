<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BerantasUngkapBarangBukti extends Model
{
    protected $table = 'berantas_ungkap_barang_bukti';
    
    protected $guarded = ['id'];

    /**
     * Relasi ke Master Narkotika (Jika kategori = Narkotika)
     */
    public function narkotika()
    {
        return $this->belongsTo(
            BerantasNarkotika::class, 
            'narkotika_id'
        );
    }

    /**
     * Relasi Many-to-Many ke Tersangka
     * (Untuk mengetahui barang bukti ini milik siapa saja)
     */
    public function tersangka()
    {
        return $this->belongsToMany(
            BerantasUngkapTersangka::class,
            'berantas_barang_bukti_tersangka', // Nama Tabel Pivot
            'barang_bukti_id',                 // FK di Pivot untuk model ini
            'tersangka_id'                     // FK di Pivot untuk model lawan
        );
    }

    /**
     * Accessor Helper: Mengambil nama barang secara dinamis
     * Jika Narkotika -> Ambil dari relasi master
     * Jika Non-Narkotika -> Ambil dari kolom input manual
     */
    public function getNamaBarangAttribute()
    {
        if ($this->kategori === 'Narkotika') {
            return $this->narkotika ? $this->narkotika->nama_narkotika : 'Unknown Narkotika';
        }
        return $this->nama_barang_non_narkotika;
    }

    /**
     * Accessor Helper: Mengambil satuan secara dinamis
     */
    public function getSatuanAttribute()
    {
        return $this->kategori === 'Narkotika' 
            ? $this->satuan_narkotika 
            : $this->satuan_non_narkotika;
    }
}