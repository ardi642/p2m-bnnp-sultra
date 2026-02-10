<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen', function (Blueprint $table) {
            $table->id();

            // 1. Relasi Polimorfik (dokumenable_id, dokumenable_type)
            $table->morphs('dokumenable'); 

            // 2. Kategori (Enum di Database)
            $table->enum('kategori', ['dokumentasi', 'lampiran'])->index(); 

            // 3. Metadata File (Bahasa Indonesia)
            $table->string('nama_file_asli');
            $table->string('path_file');       // Lokasi: uploads/...
            $table->string('tipe_file');       // Mime type
            $table->unsignedBigInteger('ukuran_file'); // Bytes
            
            // 4. Penunjuk Lokasi Penyimpanan (Support S3/Local)
            $table->string('disk')->default('public'); 

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen');
    }
};