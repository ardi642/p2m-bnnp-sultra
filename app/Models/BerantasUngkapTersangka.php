<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BerantasUngkapTersangka extends Model
{
    protected $table = 'berantas_ungkap_tersangka';
    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($t) {
            if ($t->foto_tersangka && Storage::disk('public')->exists($t->foto_tersangka)) {
                Storage::disk('public')->delete($t->foto_tersangka);
            }
        });
    }
    
    public function barangBukti()
    {
        return $this->hasMany(BerantasUngkapBarangBukti::class, 'berantas_ungkap_tersangka_id');
    }

    public function kasus()
    {
        return $this->belongsTo(BerantasUngkapKasus::class, 'berantas_ungkap_kasus_id');
    }
}