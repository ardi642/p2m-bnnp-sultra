<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasDokumen;

class BerantasTat extends Model
{
    use HasDokumen;

    protected $table = 'berantas_tat';
    protected $guarded = ['id'];
    
    protected $casts = [
        'tanggal_pelaksanaan' => 'date',
        'tanggal_penangkapan' => 'date',
        'tanggal_permohonan'  => 'date',
        // Auto convert JSON DB ke Array PHP
        'tim_hukum' => 'array', 
        'tim_medis' => 'array', 
    ];

    public function tersangka() { 
        return $this->hasMany(BerantasTatTersangka::class, 'berantas_tat_id'); 
    }
    
    public function barangBukti() { 
        return $this->hasMany(BerantasTatBarangBukti::class, 'berantas_tat_id'); 
    }
    
    public function satuanKerja() { 
        return $this->belongsTo(SatuanKerja::class); 
    }
}