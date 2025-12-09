<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\P2mSafariReligi;
use App\Models\SatuanKerja;

class P2mSafariReligiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = P2mSafariReligi::class;

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
            
            // Tanggal Pelaksanaan (setahun terakhir)
            'tanggal_pelaksanaan' => $this->faker->dateTimeBetween('-1 year', 'now'),
            
            // Tempat Kegiatan (Nama Masjid, Gereja, Pura, dll)
            'tempat_kegiatan' => $this->faker->randomElement(['Masjid', 'Gereja', 'Pura', 'Vihara']) . ' ' . $this->faker->city(),
            
            // Jumlah Masyarakat
            'jumlah_masyarakat' => $this->faker->numberBetween(50, 500),
            
            // Link Dokumentasi
            'link_kelengkapan_dokumentasi' => $this->faker->url(),
        ];
    }
}