<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasDokumentasi;

class P2mSosialisasi extends Model
{
    use HasFactory;
    use HasDokumentasi;
    protected $table = 'p2m_sosialisasi';

    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];

    protected $guarded = [];

    /**
     * CLEANUP DATABASE OTOMATIS
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($kegiatan) {
            // Hapus record database anak-anaknya.
            // Tidak pakai cursor disini karena ->delete() langsung eksekusi query SQL (Cepat & Ringan)
            $kegiatan->dokumentasi()->delete(); 
        });
    }

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
            'pegawai_p2m_sosialisasi',     // Nama Tabel Pivot
            'p2m_sosialisasi_id',          // Foreign Key tabel ini di Pivot
            'pegawai_nip',                 // Foreign Key tabel tujuan (Pegawai) di Pivot
            'id',                          // Primary Key tabel ini (Local Key)
            'nip'                          // Primary Key tabel tujuan (Pegawai Key - NIP)
        )
        ->withPivot('saved_satuan_kerja_id')    // ambil kolom history
        ->withTimestamps();               // Opsional: jika tabel pivot punya created_at/updated_at
    }
}