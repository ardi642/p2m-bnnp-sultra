<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SatuanKerja extends Model
{
    protected $table = 'satuan_kerja';
    protected $guarded = ['id'];

    public function pegawai(): HasMany
    {
        // Parameter kedua 'satuan_kerja_id' adalah foreign key di tabel pegawai
        return $this->hasMany(Pegawai::class, 'satuan_kerja_id');
    }

    public function p2mSosialisasis() {
        return $this->hasMany(P2mSosialisasi::class);
    }

    public function p2mTesUrines() {
        return $this->hasMany(P2mTesUrine::class);
    }
}
