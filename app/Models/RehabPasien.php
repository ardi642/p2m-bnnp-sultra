<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class RehabPasien extends Model
{
    protected $table = 'rehab_pasien';
    protected $guarded = ['id'];
    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function satuanKerja() {
        return $this->belongsTo(SatuanKerja::class);
    }
    
    public function riwayat() {
        return $this->hasMany(RehabRiwayat::class)
                    ->orderBy('tanggal_rehab', 'desc');
    }

    public function getUsiaAttribute() {
        return $this->tanggal_lahir ? $this->tanggal_lahir->age : 0;
    }
}