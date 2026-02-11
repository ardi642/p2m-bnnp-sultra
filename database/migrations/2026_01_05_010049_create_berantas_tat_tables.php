<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        
        // 1. Tabel Utama TAT
        Schema::create('berantas_tat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->string('no_register')->unique();
            $table->date('tanggal_pelaksanaan');
            
            // Data Kasus
            $table->text('pasal_disangkakan')->nullable();
            $table->date('tanggal_penangkapan')->nullable();
            $table->string('instansi_pengirim')->nullable(); 
            $table->date('tanggal_permohonan')->nullable();
            
            // JSON Dinamis (Tim Hukum & Medis)
            $table->json('tim_hukum')->nullable(); 
            $table->json('tim_medis')->nullable(); 
            
            // Asesmen & Rekomendasi
            $table->string('lembaga_rehab')->nullable(); 
            $table->text('proses_hukum_lanjut')->nullable(); 
            $table->enum('tindak_lanjut_rekomendasi', [
                'dilaksanakan', 
                'tidak dilaksanakan'
            ])->nullable(); 
            
            $table->decimal('biaya', 15, 2)->nullable()->default(0);
            $table->timestamps();

            // Foreign Key
            $table->foreign('satuan_kerja_id')
                  ->references('id')->on('satuan_kerja')
                  ->onDelete('cascade');
        });

        // 2. Tabel Tersangka
        Schema::create('berantas_tat_tersangka', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berantas_tat_id')
                  ->constrained('berantas_tat')
                  ->onDelete('cascade');
                  
            $table->string('nama_tersangka');
            $table->string('nik')->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->integer('usia')->nullable();
            
            // String (Validasi via App Reference)
            $table->string('pendidikan')->nullable();
            $table->string('pekerjaan')->nullable();
            
            $table->string('no_telepon')->nullable();
            $table->timestamps();
        });

        // 3. Tabel Barang Bukti
        Schema::create('berantas_tat_barang_bukti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berantas_tat_id')
                  ->constrained('berantas_tat')
                  ->onDelete('cascade');
                  
            $table->enum('kategori', ['Narkotika', 'Non-Narkotika']);
            
            // Relasi Narkotika
            $table->foreignId('narkotika_id')
                  ->nullable()
                  ->constrained('berantas_narkotika')
                  ->nullOnDelete();
            
            // Input Manual Non-Narkotika      
            $table->string('nama_barang_non_narkotika')->nullable();
            
            $table->decimal('kuantitas', 16, 4)->default(0);
            
            // Split Satuan
            $table->enum('satuan_narkotika', ['Gram', 'Kg', 'Ton'])->nullable();
            $table->string('satuan_non_narkotika')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('berantas_tat_barang_bukti');
        Schema::dropIfExists('berantas_tat_tersangka');
        Schema::dropIfExists('berantas_tat');
    }
};