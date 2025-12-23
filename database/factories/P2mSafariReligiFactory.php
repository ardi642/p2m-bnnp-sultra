<?php

namespace Database\Factories;

use App\Models\P2mSafariReligi;
use App\Models\SatuanKerja;
use Illuminate\Database\Eloquent\Factories\Factory;

class P2mSafariReligiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = P2mSafariReligi::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            // Ambil ID Satker secara acak, atau buat baru jika kosong
            'satuan_kerja_id' => SatuanKerja::inRandomOrder()->first()->id ?? SatuanKerja::factory(),
            
            // Tanggal dalam rentang 1 tahun terakhir sampai sekarang
            'tanggal_pelaksanaan' => $this->faker->dateTimeBetween('-1 year', 'now'),
            
            // Generate nama tempat yang terdengar nyata (Masjid, Aula, Lapangan)
            'tempat_kegiatan' => 'Masjid ' . $this->faker->streetName() . ', ' . $this->faker->city(),
            
            // Jumlah audiens/masyarakat
            'jumlah_masyarakat' => $this->faker->numberBetween(30, 200),
        ];
    }
}