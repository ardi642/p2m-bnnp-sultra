<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasDokumentasi;

class P2mNonElektronik extends Model
{
    use HasFactory, HasDokumentasi;

    protected $table = 'p2m_non_elektronik';

    protected $casts = [
        'tanggal_mulai_pelaksanaan' => 'date'
    ];

    protected $guarded = [];

    // Helper untuk Label Panjang di View & Excel
    public static function getJenisMediaOptions()
    {
        return [
            'Media Cetak' => 'Media Cetak (Banner, Brosur, Stiker, dll)',
            'Media Luar Ruang' => 'Media Luar Ruang (Baliho, Spanduk, Umbul-umbul)',
            'Branding Sarana Publik' => 'Branding Sarana Publik'
        ];
    }

    public function satuanKerja(): BelongsTo
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }
}