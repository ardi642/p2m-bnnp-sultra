<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\P2mLingkunganBersinar;
use App\Models\SatuanKerja;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\P2mLingkunganBersinar>
 */
class P2mLingkunganBersinarFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = P2mLingkunganBersinar::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Ambil ID Satuan Kerja secara acak dari database, atau buat baru jika kosong
            'satuan_kerja_id' => SatuanKerja::inRandomOrder()->first()->id ?? SatuanKerja::factory(),

            'anggaran_pelaksanaan' => $this->faker->randomElement(['DIPA', 'NON DIPA']),
            
            // Pilih acak dari Enum yang tersedia
            'sasaran_kegiatan' => $this->faker->randomElement([
                'sekolah/kampus bersinar', 
                'pondok pesantren bersinar', 
                'tempat hiburan bersinar',
                'tempat wisata bersinar',
                'industri bersinar'
            ]),
            
            // Generate nama tempat/wilayah yang terlihat realistis
            'nama_tempat_wilayah' => 'Desa Bersinar ' . $this->faker->streetName . ', Kelurahan ' . $this->faker->citySuffix . ', Kecamatan ' . $this->faker->city,
            
            // Tanggal dalam rentang 1 tahun terakhir sampai sekarang
            'tanggal_pencanangan' => $this->faker->dateTimeBetween('-1 year', 'now'),
            
            // Jumlah penggiat (angka acak)
            'jumlah_penggiat_p4gn' => $this->faker->numberBetween(15, 100),
            
            // Nomor HP dummy
            'no_hp_penanggung_jawab' => $this->faker->phoneNumber(),
        ];
    }
}