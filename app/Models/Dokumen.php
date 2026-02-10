<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Dokumen extends Model
{
    // Definisikan nama tabel eksplisit
    protected $table = 'dokumen'; 
    protected $guarded = ['id'];

    /**
     * Relasi balik ke Parent
     */
    public function dokumenable(): MorphTo
    {
        return $this->morphTo();
    }
}