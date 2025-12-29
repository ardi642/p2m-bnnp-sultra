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

    // Casting tanggal agar otomatis jadi object Carbon
    protected $casts = [
        'tanggal_kejadian' => 'date',
    ];

    /**
     * Hapus otomatis anak-anaknya (Tersangka, BB, Dokumentasi) saat Kasus dihapus
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($kasus) {
            // Hapus file fisik & record dokumentasi
            foreach ($kasus->dokumentasi as $doc) {
                if (Storage::disk('public')->exists($doc->path_file)) {
                    Storage::disk('public')->delete($doc->path_file);
                }
                $doc->delete();
            }
            
            // Note: Tersangka & BB otomatis terhapus via Database Cascade (Migration)
        });
    }

    // RELASI KE DOKUMENTASI (PENTING: Menggunakan tabel yang sama dengan P2M)
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
    
    public function satuanKerja()
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }
}