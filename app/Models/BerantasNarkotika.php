<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BerantasNarkotika extends Model
{
    protected $table = 'berantas_narkotika';
    protected $guarded = ['id'];

    public function rehabPasiens()
    {
        return $this->belongsToMany(
            RehabPasien::class,
            'rehab_pasien_narkotika'
        );
    }
}
