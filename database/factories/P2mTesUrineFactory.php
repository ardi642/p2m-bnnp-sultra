<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\P2mTesUrine;
use App\Models\SatuanKerja;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\P2mTesUrine>
 */
class P2mTesUrineFactory extends Factory
{
    protected $model = P2mTesUrine::class;

    public function definition(): array
    {
        // 1. Tentukan Jumlah Peserta dulu
        $jumlahPeserta = $this->faker->numberBetween(15, 150);

        // 2. Tentukan Jumlah Positif (80% kemungkinan 0, sisanya acak maks 10% dari peserta)
        // Logika: Kebanyakan tes urine hasilnya negatif semua
        $isClean = $this->faker->boolean(80); 
        $jumlahPositif = $isClean ? 0 : $this->faker->numberBetween(1, ceil($jumlahPeserta * 0.1));

        // 3. Generate Keterangan Parameter jika ada yang positif
        $keterangan = null;
        if ($jumlahPositif > 0) {
            $params = ['THC', 'AMP', 'MET', 'BZO', 'COC', 'MOP'];
            $selectedParam = $this->faker->randomElement($params);
            $keterangan = "$selectedParam: $jumlahPositif Orang";
            
            // Variasi jika jumlah positif > 1, mungkin ada mix parameter
            if($jumlahPositif > 2 && $this->faker->boolean(30)) {
                $split = floor($jumlahPositif / 2);
                $rem = $jumlahPositif - $split;
                $param2 = $this->faker->randomElement(array_diff($params, [$selectedParam]));
                $keterangan = "$selectedParam: $split Orang, $param2: $rem Orang";
            }
        }

        return [
            // Ambil ID Satker acak
            'satuan_kerja_id' => SatuanKerja::inRandomOrder()->first()->id ?? SatuanKerja::factory(),
            
            'anggaran_pelaksanaan' => $this->faker->randomElement(['DIPA', 'NON DIPA']),
            
            // Nama Instansi / Sekolah / Perusahaan
            'nama_instansi' => $this->faker->company() . ' ' . $this->faker->city(),
            
            'sasaran_kegiatan' => $this->faker->randomElement([
                'instansi pemerintah', 
                'lingkungan pendidikan', 
                'pekerja swasta', 
                'lingkungan masyarakat'
            ]),
            
            'tanggal_pelaksanaan' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'tempat_kegiatan' => $this->faker->address(),
            
            'jumlah_peserta' => $jumlahPeserta,
            'jumlah_positif' => $jumlahPositif,
            'keterangan_positif' => $keterangan,
        ];
    }
}