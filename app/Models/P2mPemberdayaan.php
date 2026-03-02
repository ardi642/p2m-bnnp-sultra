<?php

namespace App\Models;

use App\Traits\HasDokumen;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class P2mPemberdayaan extends Model
{
    use HasFactory;
    use HasDokumen; // Trait untuk dokumen/link

    protected $table = 'p2m_pemberdayaan';

    protected $casts = [
        'tanggal_pelaksanaan' => 'date'
    ];

    protected $guarded = [];

    public function getSubKegiatanLabelAttribute()
    {
        return [
            'pemetaan' => 'Pemetaan Kawasan Rawan Narkoba',
            'kapasitas' => 'Pengembangan Kapasitas Masyarakat',
            'monev' => 'Monitoring dan Evaluasi',
        ][$this->sub_kegiatan] ?? '-';
    }

    public function getDetailKegiatanLabelAttribute()
    {
        return [
            'pemetaan_sdm_sda' => 'Pemetaan SDM dan SDA',
            'rapat_kerja' => 'Rapat Kerja',
            'bimtek_life_skill' => 'Bimbingan Teknis Life Skill',
            'pengukuran_skm' => 'Pengukuran SKM',
            'monev_program' => 'Monev Program Pemberdayaan Alternatif',
            'pengukuran_ikkrn' => 'Pengukuran IKKRN',
            'pengukuran_kuesioner' => 'Pengukuran Kuesioner',
        ][$this->detail_kegiatan] ?? '-';
    }
    
    /**
     * Relasi ke Satuan Kerja (Many to One)
     */
    public function satuanKerja(): BelongsTo
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }

    public function pegawai(): BelongsToMany
    {
        return $this->belongsToMany(
            Pegawai::class,                 // Model Tujuan
            'pegawai_p2m_pemberdayaan', // Nama Tabel Pivot
            'p2m_pemberdayaan_id',     // FK di Pivot (id kegiatan)
            'pegawai_nip',                  // FK di Pivot (nip pegawai)
            'id',                           // PK tabel ini
            'nip'                           // PK tabel tujuan
        )
            ->withPivot('saved_satuan_kerja_id')
            ->withTimestamps();
    }
}
