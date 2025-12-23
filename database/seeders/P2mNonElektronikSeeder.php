<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\P2mNonElektronik;

class P2mNonElektronikSeeder extends Seeder
{
    public function run(): void
    {
        P2mNonElektronik::factory(50)->create();
    }
}