<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pegawai extends Model
{
    use HasFactory;

    protected $table = 'pegawai';
    
    // Beritahu Laravel primary key-nya bukan 'id', tapi 'nip'
    protected $primaryKey = 'nip';

    // Beritahu Laravel kalau primary key-nya BUKAN auto-increment integer
    public $incrementing = false;

    // Beritahu Laravel tipe datanya string
    protected $keyType = 'string';

    protected $guarded = [];

    /**
     * Relasi ke Satuan Kerja (Many to One)
     * Pegawai milik satu Satuan Kerja
     */
    public function satuanKerja(): BelongsTo
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }

    /**
     * Relasi ke Kegiatan P2mSosialisasi (Many to Many)
     * Menggunakan tabel pivot 'pegawai_p2m_sosialisasi'
     */
    public function p2mSosialisasi(): BelongsToMany
    {
        return $this->belongsToMany(
            P2mSosialisasi::class, 
            'pegawai_p2m_sosialisasi', // Nama tabel pivot
            'pegawai_nip',             // Foreign key untuk model ini (Pegawai)
            'p2m_sosialisasi_id',      // Foreign key untuk model lawan (Kegiatan)
            'nip',                     // Local Key (Primary Key model ini)
            'id'                       // Related Key (Primary Key model lawan)
        )->withTimestamps();
    }

     public function p2mcfd(): BelongsToMany
    {
        return $this->belongsToMany(
            p2mcfd::class, 
            'pegawai_p2m_cfd', // Nama tabel pivot
            'pegawai_nip',             // Foreign key untuk model ini (Pegawai)
            'p2m_cfd_id',      // Foreign key untuk model lawan (Kegiatan)
            'nip',                     // Local Key (Primary Key model ini)
            'id'                       // Related Key (Primary Key model lawan)
        )->withTimestamps();
    }
}