<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDokumen;

class RehabRiwayat extends Model
{
    use HasDokumen;
    protected $table = 'rehab_riwayat';
    protected $guarded = ['id'];
    protected $casts = [
        'tanggal_rehab' => 'date',
    ];

    public function pasien() {
        return $this->belongsTo(RehabPasien::class, 'rehab_pasien_id');
    }

    public function narkotika() {
        // Relasi pivot langsung ke master Narkotika
        return $this->belongsToMany(BerantasNarkotika::class, 'rehab_riwayat_narkotika', 'rehab_riwayat_id', 'narkotika_id');
    }
}