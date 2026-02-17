<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDokumen; 

class BerantasRegisterBarangBukti extends Model
{
    use HasDokumen; // Trait untuk relasi ke tabel dokumen (FilePond)

    protected $table = 'berantas_register_barang_bukti';
    protected $guarded = ['id'];
    protected $casts = ['tanggal_perolehan' => 'date'];

    public function items() { 
        return $this->hasMany(BerantasRegisterBarangBuktiItem::class, 'register_barang_bukti_id'); 
    }
    
    public function satuanKerja() { 
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id'); 
    }

}