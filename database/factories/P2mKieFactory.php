<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\P2mKie;
use App\Models\SatuanKerja;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\P2mKie>
 */
class P2mKieFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = P2mKie::class;

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
            
            // Tanggal Pelaksanaan (1 tahun terakhir)
            'tanggal_pelaksanaan' => $this->faker->dateTimeBetween('-1 year', 'now'), 
            
            // Tempat Kegiatan (Textarea: Kalimat agak panjang)
            'tempat_kegiatan' => $this->faker->paragraph(2), 
        ];
    }
}