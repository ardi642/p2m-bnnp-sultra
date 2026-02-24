<?php

namespace App\Models;

use App\Traits\HasDokumen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class P2mPeranSertaMasyarakat extends Model
{
    use HasFactory;
    use HasDokumen; // Trait untuk dokumen/link

    protected $table = 'p2m_peran_serta_masyarakat';

    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];

    protected $guarded = [];

    /**
     * Relasi ke Satuan Kerja (Many to One)
     */
    public function satuanKerja(): BelongsTo
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }

    /**
     * Relasi ke Pegawai (Many to Many)
     * Menggunakan tabel pivot 'pegawai_p2m_peran_serta_masyarakat'
     */
    public function pegawai(): BelongsToMany
    {
        return $this->belongsToMany(
            Pegawai::class,                     // Model Tujuan
            'pegawai_p2m_peran_serta_masyarakat',// Nama Tabel Pivot
            'p2m_peran_serta_masyarakat_id',    // FK di Pivot (id kegiatan)
            'pegawai_nip',                      // FK di Pivot (nip pegawai)
            'id',                               // PK tabel ini
            'nip'                               // PK tabel tujuan
        )
        ->withPivot('saved_satuan_kerja_id')
        ->withTimestamps();
    }
}
