<?php

namespace App\Traits;

use App\Models\Dokumen;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

trait HasDokumen
{

    // --- 1. MAGIC BOOT METHOD ---
    // Method ini otomatis dipanggil oleh Laravel saat Model digunakan.
    // Tidak perlu lagi tulis protected static function boot() di setiap Model.
    public static function bootHasDokumen()
    {
        static::deleting(function ($model) {
            // Ambil semua dokumen milik model ini (Tersangka/Tat/dll)
            foreach ($model->dokumen()->get() as $doc) {
                
                // Cek 1: Apakah ini FILE FISIK? (Bukan Link)
                // Cek 2: Apakah path_file ada isinya?
                if (!$doc->is_link && !empty($doc->path_file)) {
                    
                    // Cek 3: Apakah file fisiknya benar-benar ada di storage?
                    if (Storage::disk('public')->exists($doc->path_file)) {
                        Storage::disk('public')->delete($doc->path_file);
                    }
                }

                // Hapus record di database
                $doc->delete();
            }
        });
    }

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