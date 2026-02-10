<?php

namespace App\Traits;

use App\Models\Dokumen;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasDokumen
{
    public function dokumen(): MorphMany
    {
        return $this->morphMany(Dokumen::class, 'dokumenable');
    }

    // Helper: Ambil dokumen berdasarkan kategori
    public function getDokumen(string $kategori)
    {
        return $this->dokumen()->where('kategori', $kategori);
    }
    
    // Helper Folder Penyimpanan (mengubah underscore jadi dash)
    public function getStorageFolder(): string
    {
        return str_replace('_', '-', $this->getTable());
    }
}