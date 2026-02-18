<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class BerantasRegisterBarangBuktiSeeder extends Seeder
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

        $this->command->info('Seeding Berantas Register Barang Bukti...');

        for ($i = 0; $i < 125; $i++) {
            $satkerId = rand(1, 6);
            $coords = $this->getRandomCoordinate($satkerId);
            $tglPerolehan = Carbon::now()->subDays(rand(1, 365));

            // 1. Insert Register Parent
            $registerId = DB::table('berantas_register_barang_bukti')->insertGetId([
                'satuan_kerja_id' => $satkerId,
                'tanggal_perolehan' => $tglPerolehan,
                'lokasi_perolehan' => 'Kawasan ' . $coords['region_name'] . ', ' . fake('id_ID')->streetName(),
                'latitude' => $coords['lat'],
                'longitude' => $coords['lng'],
                'created_at' => $tglPerolehan,
                'updated_at' => $tglPerolehan,
            ]);

            // 2. Insert Items (Hanya Narkotika, Satuan Acak)
            $jumlahItem = rand(1, 3);
            for ($m = 0; $m < $jumlahItem; $m++) {
                DB::table('berantas_register_barang_bukti_items')->insert([
                    'register_barang_bukti_id' => $registerId,
                    'sumber_perolehan' => rand(0, 1) ? 'Hasil Tangkap' : 'Temuan',
                    'kategori' => 'Narkotika',
                    'narkotika_id' => $narkotikaIds[array_rand($narkotikaIds)],
                    'nama_barang_non_narkotika' => null,
                    'modus_pengiriman' => rand(0,1) ? 'Kurir / Tempel' : 'Ekspedisi',
                    'kuantitas' => rand(5, 2000), 
                    'satuan_narkotika' => $satuanOptions[array_rand($satuanOptions)], // ACAK (Gram/Kg/Ton)
                    'satuan_non_narkotika' => null,
                    'created_at' => $tglPerolehan,
                    'updated_at' => $tglPerolehan,
                ]);
            }
        }
    }

    // --- HELPER FUNCTIONS ---
    private function loadGeoJson() {
        $path = public_path('maps/sultra_kabupaten.geojson');
        if (File::exists($path)) {
            $this->geoJsonFeatures = json_decode(File::get($path), true)['features'] ?? [];
        }
    }

    private function getRandomCoordinate(int $satkerId): array {
        $defaultLat = -3.99846; $defaultLng = 122.512974; 
        $allowedRegions = $this->regionMapping[$satkerId] ?? [];
        
        if (empty($this->geoJsonFeatures) || empty($allowedRegions)) return ['lat' => $defaultLat, 'lng' => $defaultLng, 'region_name' => 'Kendari'];

        $matchingFeatures = array_filter($this->geoJsonFeatures, fn($f) => in_array($f['properties']['name'], $allowedRegions));
        
        if (empty($matchingFeatures)) return ['lat' => $defaultLat, 'lng' => $defaultLng, 'region_name' => 'Kendari'];

        $selected = $matchingFeatures[array_rand($matchingFeatures)];
        
        $lat = $selected['properties']['latitude'] + ((mt_rand() / mt_getrandmax() - 0.5) * 0.1);
        $lng = $selected['properties']['longitude'] + ((mt_rand() / mt_getrandmax() - 0.5) * 0.1);

        return ['lat' => $lat, 'lng' => $lng, 'region_name' => $selected['properties']['name']];
    }
}