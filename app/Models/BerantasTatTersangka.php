<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BerantasTatTersangka extends Model
{
    protected $table = 'berantas_tat_tersangka';
    protected $guarded = ['id'];

    /**
     * Relasi ke parent TAT (Kasus)
     * Wajib ada agar export bisa mengambil data register, tanggal, dll.
     */
    public function tat()
    {
        return $this->belongsTo(BerantasTat::class, 'berantas_tat_id');
    }
}