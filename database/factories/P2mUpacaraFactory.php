<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\P2mUpacara;
use App\Models\SatuanKerja;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\P2mUpacara>
 */
class P2mUpacaraFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = P2mUpacara::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Ambil ID Satuan Kerja yang sudah ada secara acak
            'satuan_kerja_id' => SatuanKerja::inRandomOrder()->first()->id ?? SatuanKerja::factory(),

            'anggaran_pelaksanaan' => $this->faker->randomElement(['DIPA', 'NON DIPA']),
            
            // Generate nama sekolah (contoh: SMAN 5 Jakarta)
            'nama_sekolah' => 'SMAN ' . $this->faker->numberBetween(1, 20) . ' ' . $this->faker->city(),
            
            'tanggal_pelaksanaan' => $this->faker->dateTimeBetween('-1 year', 'now'), // Tanggal setahun terakhir
            
            'jumlah_peserta_upacara' => $this->faker->numberBetween(50, 1000),
        ];
    }
}