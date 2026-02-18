<?php

namespace Database\Seeders;

use App\Constants\Pekerjaan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class BerantasUngkapKasusSeeder extends Seeder
{
    protected $regionMapping = [
        1 => ['Kota Kendari'],
        2 => ['Kabupaten Kolaka', 'Kabupaten Kolaka Timur', 'Kabupaten Kolaka Utara'],
        3 => ['Kabupaten Muna', 'Kabupaten Muna Barat'],
        4 => ['Kabupaten Buton', 'Kabupaten Buton Selatan', 'Kabupaten Buton Tengah', 'Kabupaten Buton Utara', 'Kabupaten Wakatobi', 'Kota Bau Bau'],
        5 => ['Kota Kendari'],
        6 => ['Kabupaten Konawe', 'Kabupaten Konawe Kepulauan', 'Kabupaten Konawe Selatan', 'Kabupaten Konawe Utara', 'Kabupaten Bombana'],
    ];

    protected $geoJsonFeatures = [];

    public function run(): void
    {
        $this->loadGeoJson();
        $narkotikaIds = DB::table('berantas_narkotika')->pluck('id')->toArray();
        $satuanOptions = ['Gram', 'Kg', 'Ton'];

        if (empty($narkotikaIds)) {
            $this->command->error('Master Narkotika kosong.');
            return;
        }

        $this->command->info('Seeding Berantas Ungkap Kasus...');

        // Reset unique faker store untuk run kali ini
        fake()->unique(true);

        for ($i = 0; $i < 200; $i++) {
            $satkerId = rand(1, 6);
            $coords = $this->getRandomCoordinate($satkerId);
            $tglKejadian = Carbon::now()->subDays(rand(1, 365));

            // GENERATE LKN UNIK
            // Format: LKN/{NomorUnik}/{BulanRomawi}/{Tahun}/BNN
            // Menggunakan unique() numberBetween agar tidak ada nomor urut yang sama dalam seeding ini
            $noUrut = str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT);
            $blnRomawi = $this->romawi($tglKejadian->month);
            $thn = $tglKejadian->year;
            $nomorLkn = "LKN/{$noUrut}/{$blnRomawi}/{$thn}/BNN";

            // 1. Insert Kasus
            $kasusId = DB::table('berantas_ungkap_kasus')->insertGetId([
                'satuan_kerja_id' => $satkerId,
                'nomor_lkn' => $nomorLkn,
                'tanggal_kejadian' => $tglKejadian,
                'alamat_tkp' => 'Jalan ' . fake('id_ID')->streetName() . ', ' . $coords['region_name'],
                'latitude' => $coords['lat'],
                'longitude' => $coords['lng'],
                'kronologis' => 'Pada hari ' . $tglKejadian->isoFormat('dddd, D MMMM Y') . ', tim menerima informasi masyarakat dan melakukan penindakan di wilayah ' . $coords['region_name'] . '.',
                'created_at' => $tglKejadian,
                'updated_at' => $tglKejadian,
            ]);

            // 2. Insert Tersangka
            $jumlahTersangka = rand(1, 3);
            $tersangkaIds = [];
            for ($j = 0; $j < $jumlahTersangka; $j++) {
                $tersangkaIds[] = DB::table('berantas_ungkap_tersangka')->insertGetId([
                    'berantas_ungkap_kasus_id' => $kasusId,
                    'nama_tersangka' => fake('id_ID')->name(),
                    'jenis_kelamin' => rand(0, 1) ? 'Laki-Laki' : 'Perempuan',
                    'pekerjaan' => $this->getRandomPekerjaan(),
                    'tahap' => 'Penyidikan',
                    'foto_tersangka' => null,
                    'created_at' => $tglKejadian,
                    'updated_at' => $tglKejadian,
                ]);
            }

            // 3. Insert Barang Bukti (Hanya Narkotika, Satuan Acak)
            $jumlahBB = rand(1, 2);
            for ($k = 0; $k < $jumlahBB; $k++) {
                $bbId = DB::table('berantas_ungkap_barang_bukti')->insertGetId([
                    'berantas_ungkap_kasus_id' => $kasusId,
                    'kategori' => 'Narkotika',
                    'narkotika_id' => $narkotikaIds[array_rand($narkotikaIds)],
                    'nama_barang_non_narkotika' => null,
                    'kuantitas' => rand(10, 5000) / 100, // 0.1 - 50.0
                    'satuan_narkotika' => $satuanOptions[array_rand($satuanOptions)], // ACAK (Gram/Kg/Ton)
                    'satuan_non_narkotika' => null,
                    'created_at' => $tglKejadian,
                    'updated_at' => $tglKejadian,
                ]);

                // 4. Pivot
                DB::table('berantas_barang_bukti_tersangka')->insert([
                    'barang_bukti_id' => $bbId,
                    'tersangka_id' => $tersangkaIds[array_rand($tersangkaIds)],
                ]);
            }
        }
    }

    // --- HELPER FUNCTIONS (Duplicated to ensure standalone execution) ---
    private function getRandomPekerjaan() {
        if (class_exists(Pekerjaan::class)) {
            $jobs = Pekerjaan::ALL;
            return $jobs[array_rand($jobs)];
        }
        return 'Wiraswasta';
    }

    private function loadGeoJson() {
        $path = public_path('maps/sultra_kabupaten.geojson');
        if (File::exists($path)) {
            $this->geoJsonFeatures = json_decode(File::get($path), true)['features'] ?? [];
        }
    }

    private function getRandomCoordinate(int $satkerId): array {
        $defaultLat = -3.99846; $defaultLng = 122.512974; // Kendari
        $allowedRegions = $this->regionMapping[$satkerId] ?? [];
        
        if (empty($this->geoJsonFeatures) || empty($allowedRegions)) return ['lat' => $defaultLat, 'lng' => $defaultLng, 'region_name' => 'Kendari'];

        $matchingFeatures = array_filter($this->geoJsonFeatures, fn($f) => in_array($f['properties']['name'], $allowedRegions));
        
        if (empty($matchingFeatures)) return ['lat' => $defaultLat, 'lng' => $defaultLng, 'region_name' => 'Kendari'];

        $selected = $matchingFeatures[array_rand($matchingFeatures)];
        
        // Jitter +/- 0.05
        $lat = $selected['properties']['latitude'] + ((mt_rand() / mt_getrandmax() - 0.5) * 0.1);
        $lng = $selected['properties']['longitude'] + ((mt_rand() / mt_getrandmax() - 0.5) * 0.1);

        return ['lat' => $lat, 'lng' => $lng, 'region_name' => $selected['properties']['name']];
    }

    private function romawi($n) {
        $map = [1000=>'M', 900=>'CM', 500=>'D', 400=>'CD', 100=>'C', 90=>'XC', 50=>'L', 40=>'XL', 10=>'X', 9=>'IX', 5=>'V', 4=>'IV', 1=>'I'];
        $res = '';
        foreach ($map as $r => $v) { while ($n >= $v) { $n -= $v; $res .= $r; } }
        return $res;
    }
}