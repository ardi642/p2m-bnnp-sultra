<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class BerantasTat extends Model
{
    protected $table = 'berantas_tat';
    protected $guarded = ['id'];
    protected $casts = [
        'tanggal_pelaksanaan' => 'date',
        'tanggal_penangkapan' => 'date',
        'tanggal_permohonan'  => 'date',
    ];

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

    public function tersangka() { return $this->hasMany(BerantasTatTersangka::class, 'berantas_tat_id'); }
    public function barangBukti() { return $this->hasMany(BerantasTatBarangBukti::class, 'berantas_tat_id'); }
    public function satuanKerja() { return $this->belongsTo(SatuanKerja::class); }
    public function dokumentasi() { return $this->morphMany(DokumentasiKegiatan::class, 'dokumentasiable'); }
}