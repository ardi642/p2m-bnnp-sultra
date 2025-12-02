<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\p2mElektronik;
use App\Models\SatuanKerja;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\P2mSosialisasi>
 */
class P2mElektronikFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = P2mElektronik::class;

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
            
            // Pilihan Enum
            'anggaran_pelaksanaan' => $this->faker->randomElement(['DIPA', 'NON DIPA']),
            
            // Pilihan Enum
            'media' => $this->faker->randomElement([
                'Televisi', 
                'Radio', 
                'Video Tron',
                'Bioskop',
                'TV Plasma',
                'Media Lain'
                
            ]),
            'nama_media' => $this->faker->sentence(2), // Kalimat acak 2 kata
            'tanggal_pelaksanaan' => $this->faker->dateTimeBetween('-1 year', 'now'), // Tanggal setahun terakhir   
            'durasi_pelaksanaan' => $this->faker->numberBetween(10, 50),
            'link_kelengkapan_dokumentasi' => $this->faker->url(),
        ];
    }
}