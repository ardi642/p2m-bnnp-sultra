<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Pegawai;
use App\Models\SatuanKerja;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pegawai>
 */
class PegawaiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Pegawai::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // NIP generated sebagai string angka unik (contoh format 18 digit)
            'nip' => $this->faker->unique()->numerify('19################'), 
            'nama' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'nomor_hp' => $this->faker->phoneNumber(),
            
            // PERUBAHAN DI SINI:
            // Ambil ID dari tabel satuan_kerja secara acak.
            // Jika tabel kosong (belum di-seed), barulah buat baru sebagai fallback (??).
            'satuan_kerja_id' => SatuanKerja::inRandomOrder()->first()->id ?? SatuanKerja::factory(),
        ];
    }
}