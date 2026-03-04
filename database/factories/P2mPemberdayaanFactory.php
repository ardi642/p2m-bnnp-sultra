<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\P2mPemberdayaan;
use App\Models\SatuanKerja;
use App\Constants\KategoriPemberdayaan;

class P2mPemberdayaanFactory extends Factory
{
    protected $model = P2mPemberdayaan::class;

    public function definition(): array
    {
        // Ambil acak key Sub Kegiatan dari Konstanta
        $subKegiatanKeys = array_keys(KategoriPemberdayaan::SUB_KEGIATAN);
        $subKegiatan = $this->faker->randomElement($subKegiatanKeys);
        
        // Ambil acak key Detail Kegiatan berdasarkan Sub Kegiatan terpilih
        $detailKeys = array_keys(KategoriPemberdayaan::DETAIL_KEGIATAN_MAP[$subKegiatan]);
        $detailKegiatan = $this->faker->randomElement($detailKeys);

        return [
            'satuan_kerja_id' => SatuanKerja::inRandomOrder()->first()->id 
                                 ?? SatuanKerja::factory(),
            
            // Masukkan Sub dan Detail sesuai mapping konstanta
            'sub_kegiatan'    => $subKegiatan,
            'detail_kegiatan' => $detailKegiatan,
            
            'anggaran_pelaksanaan' => $this->faker->randomElement(['DIPA', 'NON DIPA']),
            
            // Nama kegiatan spesifik di lapangan (contoh: "Pelatihan Sablon Warga Desa X")
            'nama_kegiatan' => $this->faker->sentence(4),
            
            'sasaran_kegiatan' => $this->faker->randomElement([
                'lingkungan pendidikan', 
                'lingkungan pemerintah', 
                'lingkungan masyarakat', 
                'lingkungan swasta'
            ]),
            
            'tanggal_pelaksanaan' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'tempat_kegiatan'     => $this->faker->city(),
            'jumlah_peserta'      => $this->faker->numberBetween(10, 100),
        ];
    }
}