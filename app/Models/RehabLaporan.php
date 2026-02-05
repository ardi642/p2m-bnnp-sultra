<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RehabLaporan extends Model
{
    use HasFactory;

    protected $table = 'rehab_laporan';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function getTanggalTextAttribute() {
        return $this->tanggal ? $this->tanggal->locale('id')->translatedFormat('d M Y') : '-';
    }

    public function satuanKerja() {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }

    public function dokumentasi() {
        return $this->morphMany(DokumentasiKegiatan::class, 'dokumentasiable');
    }

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