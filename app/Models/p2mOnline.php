<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasDokumen;

class P2mOnline extends Model
{
    use HasFactory, HasDokumen;

    protected $table = 'p2m_online';

    protected $casts = [
        'tanggal_mulai_pelaksanaan' => 'date'
    ];

    protected $guarded = [];

    // Helper Label Lengkap
    public static function getJenisMediaOptions()
    {
        return [
            'Media Online' => 'Media Online (Portal Berita Online)',
            'Medsos Stakeholder' => 'Medsos Stakeholder',
            'Media lain' => 'Media lain'
        ];
    }

    public function satuanKerja(): BelongsTo
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }
}