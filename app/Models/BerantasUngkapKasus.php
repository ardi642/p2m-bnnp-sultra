<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDokumen; 

class BerantasUngkapKasus extends Model
{
    use HasDokumen; // Trait untuk relasi ke tabel dokumen (FilePond)

    protected $table = 'berantas_ungkap_kasus';
    
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_kejadian' => 'date',
        // Casting desimal agar saat diambil tidak jadi string
        'latitude'  => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * Relasi ke Tersangka (One to Many)
     */
    public function tersangka()
    {
        return $this->hasMany(
            BerantasUngkapTersangka::class, 
            'berantas_ungkap_kasus_id'
        );
    }

    /**
     * Relasi ke Barang Bukti (One to Many)
     */
    public function barangBukti()
    {
        return $this->hasMany(
            BerantasUngkapBarangBukti::class, 
            'berantas_ungkap_kasus_id'
        );
    }

    /**
     * Relasi ke Satuan Kerja (Milik Siapa)
     */
    public function satuanKerja()
    {
        return $this->belongsTo(
            SatuanKerja::class, 
            'satuan_kerja_id'
        );
    }
}