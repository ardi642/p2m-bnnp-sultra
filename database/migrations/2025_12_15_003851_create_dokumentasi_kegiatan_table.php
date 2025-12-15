<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumentasi_kegiatan', function (Blueprint $table) {
            $table->id();

            // 1. KUNCI POLIMORFIK
            // Otomatis membuat kolom: 'dokumentasiable_id' dan 'dokumentasiable_type'
            // Ini yang memungkinkan tabel ini dipakai oleh 11 jenis kegiatan berbeda
            // Kita buat kolomnya manual agar bisa memberi nama index sendiri
            $table->string('dokumentasiable_type');
            $table->unsignedBigInteger('dokumentasiable_id');

            // Buat index dengan nama pendek (misal: 'dok_keg_morph_idx')
            $table->index(['dokumentasiable_type', 'dokumentasiable_id'], 'dok_keg_morph_idx');

            // 2. INFO FILE
            $table->string('nama_file_asli'); // Nama asli dari user (ex: Laporan.pdf)
            $table->string('path_file');      // Lokasi fisik di server
            $table->string('tipe_file');      // Mime type (image/jpeg, application/pdf)
            $table->unsignedBigInteger('ukuran_file'); // Size (bytes)
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumentasi_kegiatan');
    }
};