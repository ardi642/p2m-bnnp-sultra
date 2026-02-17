<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasDokumentasi;
use App\Traits\HasDokumen;

class P2mPelatihan extends Model
{
    use HasFactory;
    use HasDokumen;
    protected $table = 'p2m_pelatihan';

    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];

    protected $guarded = [];

    /**
     * Relasi ke Satuan Kerja (Many to One)
     */
    public function satuanKerja(): BelongsTo
    {
        // Pastikan nama Model SatuanKerja Anda sesuai (huruf besar/kecilnya)
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }

    /**
     * Relasi ke Pegawai (Many to Many)
     * Menggunakan tabel pivot 'pegawai_p2m_sosialisasi'
     */
    public function pegawai(): BelongsToMany
    {
        return $this->belongsToMany(
            Pegawai::class,                // Model Tujuan
            'pegawai_p2m_pelatihan',     // Nama Tabel Pivot
            'p2m_pelatihan_id',          // Foreign Key tabel ini di Pivot
            'pegawai_nip',                 // Foreign Key tabel tujuan (Pegawai) di Pivot
            'id',                          // Primary Key tabel ini (Local Key)
            'nip'                          // Primary Key tabel tujuan (Pegawai Key - NIP)
        )
        ->withPivot('saved_satuan_kerja_id')    // ambil kolom history
        ->withTimestamps();               // Opsional: jika tabel pivot punya created_at/updated_at
    }
}