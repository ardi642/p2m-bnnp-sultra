<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasDokumen;

class RehabRiwayat extends Model
{
    use HasFactory, HasDokumen;

    protected $table = 'rehab_riwayat';
    protected $guarded = ['id'];
    protected $casts = [
        'tanggal_rehab' => 'date',
    ];

    public function pasien() {
        return $this->belongsTo(RehabPasien::class, 'rehab_pasien_id');
    }

    public function narkotika() {
        return $this->belongsToMany(
            BerantasNarkotika::class, 
            'rehab_riwayat_narkotika', 
            'rehab_riwayat_id', 
            'narkotika_id'
        );
    }

    public function getUsiaSaatRehabAttribute() {
        if ($this->pasien && $this->pasien->tanggal_lahir && $this->tanggal_rehab) {
            // (int) akan otomatis membuang angka desimal di belakang koma
            return (int) $this->pasien->tanggal_lahir->diffInYears($this->tanggal_rehab);
        }
        return 0;
    }
}