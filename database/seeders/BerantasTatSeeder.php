<?php

namespace Database\Seeders;

use App\Constants\Pekerjaan;
use App\Constants\Pendidikan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Faker\Factory as Faker;

class BerantasTatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        // Pastikan tabel referensi sudah ada datanya
        $satkerIds = DB::table('satuan_kerja')->pluck('id')->toArray();
        $narkotikaIds = DB::table('berantas_narkotika')->pluck('id')->toArray();

        if (empty($satkerIds) || empty($narkotikaIds)) {
            $this->command->error('Tabel satuan_kerja atau berantas_narkotika kosong! Harap isi terlebih dahulu.');
            return;
        }

        $this->command->info('Seeding Berantas TAT (Tim Asesmen Terpadu)...');

        // Reset unique faker store
        fake()->unique(true);

        $instansi = ['Polresta Kendari', 'Polda Sultra', 'Polres Baubau', 'BNNK Kendari', 'BNNP Sultra', 'Polres Muna', 'Polres Kolaka'];
        $pasal = ['Pasal 112 ayat (1) UU No. 35 Tahun 2009', 'Pasal 114 ayat (2) UU No. 35 Tahun 2009', 'Pasal 127 ayat (1) huruf a UU No. 35 Tahun 2009'];
        $lembagaRehab = ['Klinik Pratama BNNP', 'RSUD Bahteramas', 'Klinik BNNK', 'Puskesmas Mekar', 'Yayasan Sahabat'];
        $satuanOptions = ['Gram', 'Kg', 'Ton'];

        $totalKasus = 100; // Jumlah TAT yang ingin dibuat

        for ($i = 0; $i < $totalKasus; $i++) {
            $satkerId = $satkerIds[array_rand($satkerIds)];
            
            // Rentang waktu 1 tahun ke belakang
            $tglPelaksanaan = Carbon::now()->subDays(rand(1, 365));
            $tglPenangkapan = (clone $tglPelaksanaan)->subDays(rand(2, 14));
            $tglPermohonan = (clone $tglPelaksanaan)->subDays(rand(1, 5));

            // GENERATE NOMOR REGISTER UNIK (Format TAT)
            $noUrut = str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT);
            $blnRomawi = $this->romawi($tglPelaksanaan->month);
            $thn = $tglPelaksanaan->year;
            $noRegister = "TAT/{$noUrut}/{$blnRomawi}/{$thn}/BNN";

            // 1. INSERT DATA KASUS TAT (PARENT)
            $tatId = DB::table('berantas_tat')->insertGetId([
                'satuan_kerja_id' => $satkerId,
                'no_register' => $noRegister,
                'tanggal_pelaksanaan' => $tglPelaksanaan->format('Y-m-d'),
                
                'pasal_disangkakan' => $faker->randomElement($pasal),
                'tanggal_penangkapan' => $tglPenangkapan->format('Y-m-d'),
                'instansi_pengirim' => $faker->randomElement($instansi),
                'tanggal_permohonan' => $tglPermohonan->format('Y-m-d'),
                
                'tim_hukum' => json_encode([
                    ['nama' => $faker->name, 'instansi' => 'Kejaksaan Negeri', 'jabatan' => 'Jaksa'],
                    ['nama' => $faker->name, 'instansi' => 'Polri', 'jabatan' => 'Penyidik']
                ]),
                'tim_medis' => json_encode([
                    ['nama' => 'dr. ' . $faker->name, 'instansi' => 'Dinkes', 'jabatan' => 'Dokter Umum'],
                    ['nama' => $faker->name . ', S.Psi', 'instansi' => 'BNN', 'jabatan' => 'Psikolog']
                ]),
                
                'lembaga_rehab' => $faker->randomElement($lembagaRehab),
                'proses_hukum_lanjut' => $faker->sentence(6),
                'tindak_lanjut_rekomendasi' => $faker->randomElement(['dilaksanakan', 'tidak dilaksanakan']),
                'biaya' => $faker->randomElement([0, 0, 500000, 1000000, 2500000]), // Dominan 0
                
                'created_at' => $tglPelaksanaan,
                'updated_at' => $tglPelaksanaan,
            ]);

            // 2. INSERT DATA TERSANGKA (1 sampai 2 tersangka per kasus)
            $jumlahTersangka = rand(1, 2);
            for ($t = 0; $t < $jumlahTersangka; $t++) {
                $jk = $faker->randomElement(['Laki-laki', 'Perempuan']);
                DB::table('berantas_tat_tersangka')->insert([
                    'berantas_tat_id' => $tatId,
                    'nama_tersangka' => $faker->firstName($jk === 'Laki-laki' ? 'male' : 'female') . ' alias ' . $faker->lastName,
                    'nik' => $faker->nik(),
                    'jenis_kelamin' => $jk, // Sesuaikan dengan enum 'Laki-laki', 'Perempuan'
                    'usia' => $faker->numberBetween(14, 66), // Menyebar usia untuk Grafik Kelompok Usia
                    'pendidikan' => $this->getRandomPendidikan(),
                    'pekerjaan' => $this->getRandomPekerjaan(),
                    'no_telepon' => $faker->phoneNumber,
                    'created_at' => $tglPelaksanaan,
                    'updated_at' => $tglPelaksanaan,
                ]);
            }

            // 3. INSERT DATA BARANG BUKTI (1 sampai 3 BB per kasus)
            $jumlahBb = rand(1, 3);
            for ($b = 0; $b < $jumlahBb; $b++) {
                // 70% peluang BB adalah Narkotika, 30% Non-Narkotika
                $isNarkotika = rand(1, 100) <= 70;

                if ($isNarkotika) {
                    DB::table('berantas_tat_barang_bukti')->insert([
                        'berantas_tat_id' => $tatId,
                        'kategori' => 'Narkotika',
                        // TYPO DIPERBAIKI DISINI: $narkotikaIds
                        'narkotika_id' => $narkotikaIds[array_rand($narkotikaIds)],
                        'nama_barang_non_narkotika' => null,
                        'kuantitas' => rand(10, 5000) / 100, // 0.1 s/d 50.0
                        'satuan_narkotika' => $satuanOptions[array_rand($satuanOptions)],
                        'satuan_non_narkotika' => null,
                        'created_at' => $tglPelaksanaan,
                        'updated_at' => $tglPelaksanaan,
                    ]);
                } else {
                    $alat = ['Handphone', 'Bong Hisap', 'Timbangan Digital', 'Plastik Klip Kosong', 'Uang Tunai', 'Sepeda Motor'];
                    DB::table('berantas_tat_barang_bukti')->insert([
                        'berantas_tat_id' => $tatId,
                        'kategori' => 'Non-Narkotika',
                        'narkotika_id' => null,
                        'nama_barang_non_narkotika' => $faker->randomElement($alat),
                        'kuantitas' => rand(1, 10),
                        'satuan_narkotika' => null,
                        'satuan_non_narkotika' => $faker->randomElement(['Buah', 'Unit', 'Lembar']),
                        'created_at' => $tglPelaksanaan,
                        'updated_at' => $tglPelaksanaan,
                    ]);
                }
            }
        }

        $this->command->info("Berhasil membuat {$totalKasus} data TAT!");
    }

    // --- HELPER FUNCTIONS ---
    private function getRandomPekerjaan() {
        if (class_exists(Pekerjaan::class)) {
            $jobs = Pekerjaan::ALL;
            return $jobs[array_rand($jobs)];
        }
        return 'Wiraswasta';
    }

    private function getRandomPendidikan() {
        if (class_exists(Pendidikan::class)) {
            $edu = Pendidikan::ALL;
            return $edu[array_rand($edu)];
        }
        return 'SMA/ sederajat';
    }

    private function romawi($n) {
        $map = [1000=>'M', 900=>'CM', 500=>'D', 400=>'CD', 100=>'C', 90=>'XC', 50=>'L', 40=>'XL', 10=>'X', 9=>'IX', 5=>'V', 4=>'IV', 1=>'I'];
        $res = '';
        foreach ($map as $r => $v) { 
            while ($n >= $v) { 
                $n -= $v; 
                $res .= $r; 
            } 
        }
        return $res;
    }
}