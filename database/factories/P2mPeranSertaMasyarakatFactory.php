<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\P2mPeranSertaMasyarakat;
use App\Models\SatuanKerja;
use App\Constants\KategoriPeranSertaMasyarakat;

class P2mPeranSertaMasyarakatFactory extends Factory
{
    protected $model = P2mPeranSertaMasyarakat::class;

    public function definition(): array
    {
        // Ambil acak key Kategori dari Konstanta
        $kategoriKeys = array_keys(KategoriPeranSertaMasyarakat::KATEGORI);
        $kategori = $this->faker->randomElement($kategoriKeys);
        
        // Ambil acak Nama Kegiatan berdasarkan Kategori terpilih
        $namaKegiatan = $this->faker->randomElement(
            KategoriPeranSertaMasyarakat::KEGIATAN_MAP[$kategori]
        );

        return [
            'satuan_kerja_id' => SatuanKerja::inRandomOrder()->first()->id 
                                 ?? SatuanKerja::factory(),
            
            'anggaran_pelaksanaan' => $this->faker->randomElement(['DIPA', 'NON DIPA']),
            
            // Masukkan data sesuai konstanta
            'kategori_kegiatan' => $kategori,
            'nama_kegiatan'     => $namaKegiatan,
            
            'tanggal_pelaksanaan' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'tempat_kegiatan'     => $this->faker->city(),
            'jumlah_peserta'      => $this->faker->numberBetween(20, 200),
        ];
    }
}