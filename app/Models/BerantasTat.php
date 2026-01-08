<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

class BerantasTat extends Model
{
    protected $table = 'berantas_tat';
    protected $guarded = ['id'];

    protected $casts = [
        'tanggal_pelaksanaan' => 'date',
        'tanggal_penangkapan' => 'date',
        'tanggal_permohonan'  => 'date',
        'biaya'               => 'decimal:2',
        'usia'                => 'integer',
    ];

    protected static function boot()
    {
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

    public function satuanKerja(): BelongsTo
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }

    public function dokumentasi(): MorphMany
    {
        return $this->morphMany(DokumentasiKegiatan::class, 'dokumentasiable');
    }
}