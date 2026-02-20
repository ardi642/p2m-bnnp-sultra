<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RehabPasien extends Model
{
    protected $table = 'rehab_pasien';
    protected $guarded = ['id'];

    public function satuanKerja() {
        return $this->belongsTo(SatuanKerja::class);
    }
    
    public function riwayat() {
        // Relasi ke tabel riwayat (satu pasien bisa banyak riwayat kedatangan)
        return $this->hasMany(RehabRiwayat::class)->orderBy('tanggal_rehab', 'desc');
    }
}