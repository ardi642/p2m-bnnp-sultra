<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\RehabPasien;

class BerantasNarkotika extends Model
{
    protected $table = 'berantas_narkotika';
    protected $guarded = ['id'];


    public function pasien()
    {
        return $this->hasMany(RehabPasien::class, 'narkotika_id');
    }
}
