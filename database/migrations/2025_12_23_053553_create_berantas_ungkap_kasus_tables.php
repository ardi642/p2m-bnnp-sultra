<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. MASTER NARKOTIKA (Dibuat paling awal karena akan direlasikan)
        Schema::create('berantas_narkotika', function (Blueprint $table) {
            $table->id();
            $table->string('nama_narkotika');
            $table->enum('golongan', ['Golongan I', 'Golongan II', 'Golongan III', 'Non Golongan'])->default('Golongan I');
            $table->timestamps();
        });

        // 2. TABEL KASUS (PARENT)
        Schema::create('berantas_ungkap_kasus', function (Blueprint $table) {
            $table->id();
            // Asumsi tabel 'satuan_kerja' sudah ada sebelumnya (karena tidak diminta di prompt ini)
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->string('nomor_lkn')->unique();
            $table->date('tanggal_kejadian');
            $table->text('alamat_tkp');
            $table->timestamps();
            
            // FK ke Satuan Kerja
            $table->foreign('satuan_kerja_id')->references('id')->on('satuan_kerja')->onDelete('cascade');
        });

        // 3. TABEL TERSANGKA
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

        // 4. TABEL BARANG BUKTI (Dengan Kolom Baru Kategori & Relasi Master)
        Schema::create('berantas_ungkap_barang_bukti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berantas_ungkap_kasus_id')
                  ->constrained('berantas_ungkap_kasus')
                  ->cascadeOnDelete();
            
            // --- KOLOM BARU ---
            // Pembeda Narkotika / Non-Narkotika
            $table->enum('kategori_barang_bukti', ['Narkotika', 'Non-Narkotika'])->default('Narkotika');
            
            // Relasi ke Master (Nullable, karena Non-Narkotika tidak punya ID ini)
            // Jika master narkotika dihapus, set null (jangan hapus barang buktinya)
            $table->foreignId('narkotika_id')
                  ->nullable()
                  ->constrained('berantas_narkotika')
                  ->nullOnDelete(); 
            // ------------------

            // Kolom String Manual (Untuk Non-Narkotika ATAU Backup Nama Narkotika)
            $table->string('jenis_barang_bukti')->nullable(); 
            
            // Decimal presisi tinggi (16 digit total, 4 di belakang koma)
            $table->decimal('jumlah_barang_bukti', 16, 4); 
            
            $table->enum('satuan_barang_bukti', ['Gram', 'Kg', 'Ton'])->default('Gram');
            
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        // 5. TABEL PIVOT (MANY-TO-MANY: Barang Bukti <-> Tersangka)
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop urutan dibalik agar tidak error constraint FK
        Schema::dropIfExists('berantas_barang_bukti_tersangka');
        Schema::dropIfExists('berantas_ungkap_barang_bukti'); // Child
        Schema::dropIfExists('berantas_ungkap_tersangka');    // Child
        Schema::dropIfExists('berantas_ungkap_kasus');        // Parent
        Schema::dropIfExists('berantas_narkotika');           // Master
    }
};