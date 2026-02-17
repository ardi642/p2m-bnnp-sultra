<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Master Narkotika
        Schema::create('berantas_narkotika', function (Blueprint $table) {
            $table->id();
            $table->string('nama_narkotika');
            $table->enum('golongan', [
                'Golongan I', 
                'Golongan II', 
                'Golongan III', 
                'Non Golongan'
            ])->default('Golongan I');
            $table->timestamps();
        });

        // 2. Tabel Kasus (Parent)
        Schema::create('berantas_ungkap_kasus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            
            $table->string('nomor_lkn')->unique();
            $table->date('tanggal_kejadian');
            $table->text('alamat_tkp');
            
            // Koordinat
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('kronologis')->nullable();

            $table->timestamps();
            
            $table->foreign('satuan_kerja_id')
                  ->references('id')
                  ->on('satuan_kerja')
                  ->onDelete('cascade');
        });

        // 3. Tabel Tersangka
        Schema::create('berantas_ungkap_tersangka', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berantas_ungkap_kasus_id')
                  ->constrained('berantas_ungkap_kasus')
                  ->cascadeOnDelete();
            
            $table->string('nama_tersangka');
            $table->enum('jenis_kelamin', ['Laki-Laki', 'Perempuan']);
            $table->string('pekerjaan')->nullable();
            
            // PERBAIKAN: Default dihapus, urutan dihapus
            $table->string('tahap'); 
            
            $table->string('foto_tersangka')->nullable();
            
            $table->timestamps();
        });

        // 4. Tabel Barang Bukti
        Schema::create('berantas_ungkap_barang_bukti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berantas_ungkap_kasus_id')
                  ->constrained('berantas_ungkap_kasus')
                  ->cascadeOnDelete();
            
            $table->enum('kategori', ['Narkotika', 'Non-Narkotika'])
                  ->default('Narkotika');
            
            $table->foreignId('narkotika_id')
                  ->nullable()
                  ->constrained('berantas_narkotika')
                  ->onDelete('restrict');

            $table->string('nama_barang_non_narkotika')->nullable(); 
            
            $table->decimal('kuantitas', 16, 4); 
            $table->enum('satuan_narkotika', ['Gram', 'Kg', 'Ton'])
                  ->nullable();
            $table->string('satuan_non_narkotika')->nullable();
            
            // PERBAIKAN: Urutan dihapus
            
            $table->timestamps();
        });

        // 5. Tabel Pivot (Many-to-Many)
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