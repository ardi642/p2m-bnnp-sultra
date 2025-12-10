<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Menggunakan Raw SQL karena ->change() pada enum sering bermasalah di beberapa versi DB
        // Saya sarankan gunakan snake_case 'admin_satker' daripada pakai spasi
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'operator', 'admin_satker') NOT NULL DEFAULT 'operator'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke opsi lama (Hati-hati: data 'admin_satker' akan jadi error/kosong jika di-rollback)
        // Biasanya kita mapping dulu data lama ke default sebelum alter
        DB::table('users')->where('role', 'admin_satker')->update(['role' => 'operator']);
        
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'operator') NOT NULL DEFAULT 'operator'");
    }
};