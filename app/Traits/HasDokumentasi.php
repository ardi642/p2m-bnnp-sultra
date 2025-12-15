<?php

namespace App\Traits;

use App\Models\DokumentasiKegiatan;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasDokumentasi
{
    /**
     * Relasi: Kegiatan memiliki banyak dokumentasi
     */
    public function dokumentasi(): MorphMany
    {
        return $this->morphMany(DokumentasiKegiatan::class, 'dokumentasiable');
    }
}