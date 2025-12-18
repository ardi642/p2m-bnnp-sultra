<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\P2mElektronik;

class P2mElektronikSeeder extends Seeder
{
    public function run(): void
    {
        // Hanya create data, tidak ada attach pegawai
        P2mElektronik::factory(50)->create();
    }
}