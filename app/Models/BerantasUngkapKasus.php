<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class BerantasUngkapKasus extends Model
{
    use HasFactory;

    protected $table = 'berantas_ungkap_kasus';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_kejadian' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($kasus) {
            foreach ($kasus->dokumentasi as $doc) {
                if (Storage::disk('public')->exists($doc->path_file)) {
                    Storage::disk('public')->delete($doc->path_file);
                }
                $doc->delete();
            }
        });
    }

    public function dokumentasi(): MorphMany
    {
        return $this->morphMany(DokumentasiKegiatan::class, 'dokumentasiable');
    }

    public function tersangka(): HasMany
    {
        return $this->hasMany(BerantasUngkapTersangka::class, 'berantas_ungkap_kasus_id');
    }

    public function barangBukti(): HasMany
    {
        return $this->hasMany(BerantasUngkapBarangBukti::class, 'berantas_ungkap_kasus_id');
    }

    // --- TAMBAHKAN INI (YANG HILANG) ---
    // Mengambil BB yang tidak dimiliki tersangka spesifik (Milik Kasus/Bersama)
    public function barangBuktiBersama(): HasMany
    {
        return $this->hasMany(BerantasUngkapBarangBukti::class, 'berantas_ungkap_kasus_id')
                    ->whereNull('berantas_ungkap_tersangka_id');
    }
    // ------------------------------------
    
    public function satuanKerja()
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }
}