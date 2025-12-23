<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\P2mOnline;

class P2mOnlineSeeder extends Seeder
{
    public function run(): void
    {
        P2mOnline::factory(50)->create();
    }
}