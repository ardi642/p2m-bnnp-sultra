<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\P2mMediaNonElektronik;
use App\Models\SatuanKerja;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\P2mMediaNonElektronik>
 */
class P2mMediaNonElektronikFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = P2mMediaNonElektronik::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Ambil ID Satuan Kerja secara acak
            'satuan_kerja_id' => SatuanKerja::inRandomOrder()->first()->id ?? SatuanKerja::factory(),
            
            // Enum Anggaran
            'anggaran_pelaksanaan' => $this->faker->randomElement(['DIPA', 'NON DIPA']),
            
            // Enum Jenis Media
            'jenis_media' => $this->faker->randomElement([
                'Media Cetak', 
                'Media Luar Ruang', 
                'Branding Sarana Publik'
            ]),
            
            // Durasi dalam hari (misal 1 s/d 60 hari)
            'durasi_pelaksanaan' => $this->faker->numberBetween(1, 60),
            
            // Tanggal Mulai
            'tanggal_pelaksanaan' => $this->faker->dateTimeBetween('-1 year', 'now'),
            
            // Tempat Pemasangan
            'tempat_kegiatan' => $this->faker->address(),
            
            // Link Dokumentasi
            'link_kelengkapan_dokumentasi' => $this->faker->url(),
        ];
    }
}