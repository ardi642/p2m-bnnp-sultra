<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SatuanKerja extends Model
{
    protected $table = 'satuan_kerja';
    protected $guarded = ['id'];

    public function p2mSosialisasis() {
        return $this->hasMany(P2mSosialisasi::class);
    }

    public function p2mTesUrines() {
        return $this->hasMany(P2mTesUrine::class);
    }
}
