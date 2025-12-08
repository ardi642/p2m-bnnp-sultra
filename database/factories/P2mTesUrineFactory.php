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
        // Simulasi hasil tes
        $jumlahPeserta = $this->faker->numberBetween(10, 200);
        
        // Peluang 80% tidak ada yang positif (0), 20% ada yang positif (1-5 orang)
        $jumlahPositif = $this->faker->boolean(80) ? 0 : $this->faker->numberBetween(1, 5);

        // Keterangan hanya ada jika jumlah positif > 0
        $keteranganPositif = $jumlahPositif > 0 
            ? 'Positif: ' . $this->faker->randomElement(['THC', 'Meth', 'Benzo']) . ' pada peserta inisial ' . $this->faker->lexify('??') 
            : null;

        return [
            // Ambil ID Satker acak yang sudah ada
            'satuan_kerja_id' => SatuanKerja::inRandomOrder()->first()->id ?? SatuanKerja::factory(),
            
            'anggaran_pelaksanaan' => $this->faker->randomElement(['DIPA', 'NON DIPA']),
            
            // Sasaran Kegiatan sesuai Enum Tes Urine
            'sasaran_kegiatan' => $this->faker->randomElement([
                'Instansi Pemerintah', 
                'Lingkungan Pendidikan', 
                'Pekerja Swasta', 
                'Lingkungan Masyarakat'
            ]),

            'nama_instansi_pelaksana' => $this->faker->company(), // Nama instansi yang dites
            'tanggal_pelaksanaan' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'tempat_kegiatan' => $this->faker->address(),
            
            'jumlah_peserta' => $jumlahPeserta,
            'jumlah_positif' => $jumlahPositif,
            'keterangan_positif' => $keteranganPositif,
            
            'link_kelengkapan_dokumentasi' => $this->faker->url(),
        ];
    }
}