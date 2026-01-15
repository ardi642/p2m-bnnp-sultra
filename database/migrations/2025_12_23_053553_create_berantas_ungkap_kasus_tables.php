<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MASTER NARKOTIKA
        Schema::create('berantas_narkotika', function (Blueprint $table) {
            $table->id();
            $table->string('nama_narkotika');
            $table->enum('golongan', ['Golongan I', 'Golongan II', 'Golongan III', 'Non Golongan'])->default('Golongan I');
            $table->timestamps();
        });

        // TABEL KASUS (PARENT)
        Schema::create('berantas_ungkap_kasus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->string('nomor_lkn')->unique();
            $table->date('tanggal_kejadian');
            $table->text('alamat_tkp');
            $table->timestamps();
            
            $table->foreign('satuan_kerja_id')->references('id')->on('satuan_kerja')->onDelete('cascade');
        });

        // TABEL TERSANGKA
        Schema::create('berantas_ungkap_tersangka', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berantas_ungkap_kasus_id')
                  ->constrained('berantas_ungkap_kasus')
                  ->cascadeOnDelete();
            
            $table->string('nama_tersangka');
            $table->enum('jenis_kelamin', ['Laki-Laki', 'Perempuan']);
            $table->string('pekerjaan')->nullable();
            $table->string('tahap'); 
            $table->string('foto_tersangka')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        // TABEL BARANG BUKTI (FINAL)
        Schema::create('berantas_ungkap_barang_bukti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berantas_ungkap_kasus_id')
                  ->constrained('berantas_ungkap_kasus')
                  ->cascadeOnDelete();
            
            $table->enum('kategori', ['Narkotika', 'Non-Narkotika'])->default('Narkotika');
            
            // A. Field Narkotika (Relasi)
            $table->foreignId('narkotika_id')
                  ->nullable()
                  ->constrained('berantas_narkotika')
                  ->onDelete('restrict');

            // B. Field Non-Narkotika (Manual)
            $table->string('nama_barang_non_narkotika')->nullable(); 
            
            // Kuantitas (Decimal Presisi)
            $table->decimal('kuantitas', 16, 4); 
            
            // --- SATUAN ---
            // 1. Satuan Narkotika (ENUM: Gram, Kg, Ton)
            $table->enum('satuan_narkotika', ['Gram', 'Kg', 'Ton'])->nullable();

            // 2. Satuan Non-Narkotika (STRING: Bebas)
            $table->string('satuan_non_narkotika')->nullable();
            
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        // TABEL PIVOT
        Schema::create('berantas_barang_bukti_tersangka', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_bukti_id')
                  ->constrained('berantas_ungkap_barang_bukti')
                  ->cascadeOnDelete();
            $table->foreignId('tersangka_id')
                  ->constrained('berantas_ungkap_tersangka')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berantas_barang_bukti_tersangka');
        Schema::dropIfExists('berantas_ungkap_barang_bukti');
        Schema::dropIfExists('berantas_ungkap_tersangka');
        Schema::dropIfExists('berantas_ungkap_kasus');
        Schema::dropIfExists('berantas_narkotika');
    }
};