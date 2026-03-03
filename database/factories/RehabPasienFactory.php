<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\SatuanKerja;

class RehabPasienFactory extends Factory
{
    public function definition(): array
    {
        $jk = $this->faker->randomElement(['Laki-laki', 'Perempuan']);
        $tglLahir = $this->faker->dateTimeBetween('-50 years', '-15 years')->format('Y-m-d');
        $nama = $this->faker->name($jk == 'Laki-laki' ? 'male' : 'female');

        // Logic pembuatan ID Pasien seperti di Controller
        $namaBersih = strtoupper(trim($nama));
        $tglFormat = date('d-m-Y', strtotime($tglLahir));
        $kodeJk = $jk === 'Laki-laki' ? 'L' : 'P';
        $idPasienFormat = "{$namaBersih}-{$tglFormat}-{$kodeJk}";

        return [
            // Ambil Satker secara acak, fallback ke 1 jika kosong
            'satuan_kerja_id' => SatuanKerja::inRandomOrder()->value('id') ?? 1,
            'id_pasien' => $idPasienFormat,
            'nama_pasien' => $nama,
            'tanggal_lahir' => $tglLahir,
            'jenis_kelamin' => $jk,
        ];
    }
}