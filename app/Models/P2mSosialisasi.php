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
            Pegawai::class,                // 1. Model Tujuan
            'pegawai_p2m_sosialisasi',     // 2. Nama Tabel Pivot
            'p2m_sosialisasi_id',          // 3. Foreign Key tabel ini di Pivot
            'pegawai_nip',                 // 4. Foreign Key tabel tujuan (Pegawai) di Pivot
            'id',                          // 5. Primary Key tabel ini (Local Key)
            'nip'                          // 6. Primary Key tabel tujuan (Pegawai Key - NIP)
        )->withTimestamps();               // Opsional: jika tabel pivot punya created_at/updated_at
    }
}