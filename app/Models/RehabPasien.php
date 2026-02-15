<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BerantasNarkotika;

class RehabPasien extends Model
{
    protected $table = 'rehab_pasien';
    protected $fillable = [
        'satuan_kerja_id',
        'nama_pasien',
        'jenis_kelamin',
        'usia',
        'pekerjaan',
        'pendidikan',
        'narkotika_id',
        'sumber_pasien',
    ];

    public const Pekerjaan = [
        'Tidak Bekerja',
        'Pelajar/Mahasiswa',
        'ASN',
        'TNI/Polri',
        'Wirausaha',
        'Pegawai Swasta',
        'Wiraswasta',
        'Mengurus Rumah Tangga',
        'Pegawai BUMN/BUMD',
        'Pekerja Sosial',
        'Petani/Nelayan/Pedagang',
        'Buruh/Kuli Bangunan',
        'Content Creator',
    ];

    public const Pendidikan = [
        'Tidak Sekolah',
        'SD',
        'SMP',
        'SMA',
        'Diploma',
        'Strata 1',
        'Strata 2',
    ];

    public const Sumber_pasien = [
        'Voluntary',
        'Compulsory',
    ];


    // Relasi ke BerantasNarkotika
    public function narkotika()
    {
        return $this->belongsTo(BerantasNarkotika::class, 'narkotika_id');
    }

    // Relasi ke SatuanKerja
    public function satuanKerja()
    {
        return $this->belongsTo(SatuanKerja::class, 'satuan_kerja_id');
    }
}
