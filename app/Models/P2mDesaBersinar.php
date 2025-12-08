<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class P2mDesaBersinar extends Model
{
    use HasFactory;

    protected $table = 'p2m_desa_bersinar';

    protected $casts = [
        'tanggal_pencanangan' => 'date',
    ];

    protected $guarded = [];

    public function satuanKerja(): BelongsTo
    {
        return $this->belongsTo(SatuanKerja::class);
    }

    public function kabupatenKota(): BelongsTo
    {
        return $this->belongsTo(KabupatenKota::class);
    }

    public function pegawai(): BelongsToMany
    {
        return $this->belongsToMany(
            Pegawai::class,
            'pegawai_p2m_desa_bersinar',
            'p2m_desa_bersinar_id',
            'pegawai_nip',
            'id',
            'nip'
        )->withTimestamps();
    }
}