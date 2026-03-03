<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Constants\Pendidikan;
use App\Constants\Pekerjaan;
use App\Constants\SumberPasien;

class RehabRiwayatFactory extends Factory
{
    public function definition(): array
    {
        // Campurkan pekerjaan bawaan dan ketikan manual untuk ngetes filter hybrid Anda
        $pekerjaanHybrid = array_merge(Pekerjaan::ALL, ['Supir Truk', 'Programmer', 'Content Creator', 'Tukang Jahit']);

        return [
            // Rentang waktu dari 1 Januari 2024 sampai Hari Ini (2026)
            'tanggal_rehab' => $this->faker->dateTimeBetween('2024-01-01', 'now')->format('Y-m-d'),
            'pendidikan' => $this->faker->randomElement(Pendidikan::ALL),
            'pekerjaan' => $this->faker->randomElement($pekerjaanHybrid),
            'sumber_pasien' => $this->faker->randomElement(SumberPasien::ALL),
        ];
    }
}