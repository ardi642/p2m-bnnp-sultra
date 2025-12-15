<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DokumentasiKegiatan extends Model
{
    protected $table = 'dokumentasi_kegiatan';
    protected $guarded = ['id'];

    /**
     * Relasi balik ke Parent (Kegiatan apa saja)
     */
    public function dokumentasiable(): MorphTo
    {
        return $this->morphTo();
    }
}