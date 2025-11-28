<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SatuanKerjaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'satuan_kerja' => 'BNNP Sultra',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'satuan_kerja' => 'BNNK Kolaka',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'satuan_kerja' => 'BNNK Muna',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'satuan_kerja' => 'BNNK Baubau',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ],
            [
                'satuan_kerja' => 'BNNK Kendari',
                'created_at'    => Carbon::now(),
                'updated_at'    => Carbon::now(),
            ]
        ];
        DB::table('satuan_kerja')->insert($data);
    }
}
