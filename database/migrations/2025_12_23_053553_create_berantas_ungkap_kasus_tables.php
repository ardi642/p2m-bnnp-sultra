<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. TABEL KASUS (PARENT)
        Schema::create('berantas_ungkap_kasus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->string('nomor_lkn')->unique();
            $table->date('tanggal_kejadian');
            $table->text('alamat_tkp');
            $table->timestamps();
            
            $table->foreign('satuan_kerja_id')->references('id')->on('satuan_kerja')->onDelete('cascade');
        });

        // 2. TABEL TERSANGKA
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

        // 3. TABEL BARANG BUKTI
        Schema::create('berantas_ungkap_barang_bukti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berantas_ungkap_kasus_id')
                  ->constrained('berantas_ungkap_kasus')
                  ->cascadeOnDelete();
            
            $table->string('jenis_barang_bukti');
            // Decimal presisi tinggi (16 digit total, 4 di belakang koma)
            $table->decimal('jumlah_barang_bukti', 16, 4); 
            
            // HANYA 3 OPSI SATUAN
            $table->enum('satuan_barang_bukti', ['Gram', 'Kg', 'Ton'])->default('Gram');
            
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        // 4. TABEL PIVOT (MANY-TO-MANY)
        Schema::create('berantas_barang_bukti_tersangka', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_bukti_id')->constrained('berantas_ungkap_barang_bukti')->cascadeOnDelete();
            $table->foreignId('tersangka_id')->constrained('berantas_ungkap_tersangka')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berantas_barang_bukti_tersangka');
        Schema::dropIfExists('berantas_ungkap_barang_bukti');
        Schema::dropIfExists('berantas_ungkap_tersangka');
        Schema::dropIfExists('berantas_ungkap_kasus');
    }
};