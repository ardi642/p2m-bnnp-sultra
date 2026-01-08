<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BerantasUngkapTersangka extends Model
{
    protected $table = 'berantas_ungkap_tersangka';
    protected $guarded = ['id'];

    protected static function boot() {
        parent::boot();
        static::deleting(function ($t) {
            if ($t->foto_tersangka && Storage::disk('public')->exists($t->foto_tersangka)) {
                Storage::disk('public')->delete($t->foto_tersangka);
            }
        });
    }

    // Relasi ke Parent (Kasus)
    public function kasus() {
        return $this->belongsTo(BerantasUngkapKasus::class, 'berantas_ungkap_kasus_id');
    }

    // Relasi Many-to-Many ke BB
    public function barangBukti() {
        return $this->belongsToMany(
            BerantasUngkapBarangBukti::class, 
            'berantas_barang_bukti_tersangka', // Nama Tabel Pivot
            'tersangka_id', 
            'barang_bukti_id'
        );
    }
}