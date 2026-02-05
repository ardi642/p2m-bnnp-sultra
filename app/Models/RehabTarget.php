<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RehabTarget extends Model
{
    use HasFactory;

    protected $table = 'rehab_target';
    protected $guarded = ['id'];

    public function satuanKerja() {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }
}