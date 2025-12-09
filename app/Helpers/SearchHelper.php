<?php

namespace App\Helpers;

class SearchHelper
{
    /**
     * Menerjemahkan input pencarian (Hari/Bulan Indo) ke Inggris
     * agar cocok dengan format default MySQL.
     *
     * @param string $search
     * @return string
     */
    public static function translateDateInput($search)
    {
        // Ubah ke huruf kecil semua agar mudah dicocokkan
        $searchLower = strtolower($search);

        // Daftar Kata Kunci (Indonesia => Inggris)
        $map = [
            // HARI
            'minggu' => 'sunday',
            'senin'  => 'monday',
            'selasa' => 'tuesday',
            'rabu'   => 'wednesday',
            'kamis'  => 'thursday',
            'jumat'  => 'friday',
            'sabtu'  => 'saturday',

            // BULAN (Lengkap)
            'januari'   => 'january',
            'februari'  => 'february',
            'maret'     => 'march',
            'april'     => 'april',
            'mei'       => 'may',
            'juni'      => 'june',
            'juli'      => 'july',
            'agustus'   => 'august',
            'september' => 'september',
            'oktober'   => 'october',
            'november'  => 'november',
            'desember'  => 'december',

            // BULAN (Singkatan 3 Huruf)
            'jan' => 'jan', 
            'feb' => 'feb', 
            'mar' => 'mar', 
            'apr' => 'apr',
            'mei' => 'may', 
            'jun' => 'jun', 
            'jul' => 'jul', 
            'agt' => 'aug', // Agustus (Indo) -> Aug (Inggris)
            'sep' => 'sep', 
            'okt' => 'oct', // Oktober -> Oct
            'nop' => 'nov', // Nopember -> Nov
            'nov' => 'nov', 
            'des' => 'dec'  // Desember -> Dec
        ];

        // Lakukan replace
        return str_replace(array_keys($map), array_values($map), $searchLower);
    }
}