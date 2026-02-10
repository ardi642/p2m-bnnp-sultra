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

            // 2. Kategori
            $table->enum('kategori', ['dokumentasi', 'lampiran'])->index(); 

            // 3. Metadata Umum (Label Link atau Nama File)
            $table->string('nama_file_asli'); 

            // 4. Khusus File Fisik (Boleh NULL jika tipe Link)
            $table->string('path_file')->nullable();       
            $table->string('tipe_file')->nullable();       
            $table->unsignedBigInteger('ukuran_file')->nullable(); 
            $table->string('disk')->nullable()->default('public'); 

            // 5. Khusus Link Eksternal
            $table->text('path_url')->nullable(); // Menyimpan URL (https://...)
            $table->boolean('is_link')->default(false); // Penanda: True = Link, False = File

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen');
    }
};