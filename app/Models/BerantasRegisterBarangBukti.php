<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BerantasRegisterBarangBukti extends Model
{
    protected $table = 'berantas_register_barang_bukti';
    protected $guarded = ['id'];
    protected $casts = ['tanggal_perolehan' => 'date'];

    protected static function boot() {
        parent::boot();
        static::deleting(function ($model) {
            foreach ($model->dokumentasi as $doc) {
                if (Storage::disk('public')->exists($doc->path_file)) {
                    Storage::disk('public')->delete($doc->path_file);
                }
                $doc->delete();
            }
        });
    }

    public function items() { 
        return $this->hasMany(BerantasRegisterBarangBuktiItem::class, 'register_barang_bukti_id'); 
    }
    
    public function satuanKerja() { 
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id'); 
    }
    
    public function dokumentasi() { 
        return $this->morphMany(DokumentasiKegiatan::class, 'dokumentasiable'); 
    }
}