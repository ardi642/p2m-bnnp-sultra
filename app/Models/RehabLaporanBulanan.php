<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RehabLaporanBulanan extends Model
{
    use HasFactory;

    protected $table = 'rehab_laporan_bulanan';
    protected $guarded = ['id'];

    // Casting periode ke Date
    protected $casts = [
        'periode' => 'date',
    ];

    /**
     * ACCESSOR
     */
    public function getBulanAttribute() {
        return $this->periode ? $this->periode->format('n') : null;
    }

    public function getTahunAttribute() {
        return $this->periode ? $this->periode->format('Y') : null;
    }

    public function getPeriodeTextAttribute() {
        return $this->periode ? $this->periode->locale('id')->translatedFormat('F Y') : '-';
    }

    /**
     * RELASI
     */
    public function satuanKerja() {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }

    public function dokumentasi() {
        return $this->morphMany(DokumentasiKegiatan::class, 'dokumentasiable');
    }

    /**
     * BOOT (Auto Delete File)
     */
    protected static function boot() {
        parent::boot();
        static::deleting(function ($model) {
            foreach ($model->dokumentasi as $doc) {
                if (Storage::disk('public')->exists($doc->path_file)) {
                    Storage::disk('public')->delete($doc->path_file);
                }
                $doc->delete();
            }
        });
    }
}