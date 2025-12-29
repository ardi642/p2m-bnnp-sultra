<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. TABEL KASUS (HEAD)
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
            $table->foreignId('berantas_ungkap_kasus_id')->constrained('berantas_ungkap_kasus')->onDelete('cascade');
            
            $table->string('nama_tersangka');
            $table->enum('jenis_kelamin', ['Laki-Laki', 'Perempuan']);
            $table->string('pekerjaan')->nullable();
            $table->string('status_tahap')->default('Proses Sidik'); 
            $table->string('foto_tersangka')->nullable();
            
            $table->timestamps();
        });

        // 3. TABEL BARANG BUKTI
        Schema::create('berantas_ungkap_barang_bukti', function (Blueprint $table) {
            $table->id();
            
            // Link ke Kasus (Wajib)
            $table->unsignedBigInteger('berantas_ungkap_kasus_id');
            $table->foreign('berantas_ungkap_kasus_id', 'fk_buk_kasus')
                ->references('id')->on('berantas_ungkap_kasus')->onDelete('cascade');
            
            // Link ke Tersangka (Nullable)
            // Jika NULL = Milik Bersama (Mapping ke semua TSK di LKN ini). 
            // Jika Terisi = Milik Personal TSK tersebut.
            $table->unsignedBigInteger('berantas_ungkap_tersangka_id')->nullable();
            $table->foreign('berantas_ungkap_tersangka_id', 'fk_buk_tersangka')
                ->references('id')->on('berantas_ungkap_tersangka')->onDelete('cascade');
            
            $table->string('jenis_barang_bukti'); 
            $table->decimal('jumlah_barang_bukti', 12, 2);
            $table->string('satuan_barang_bukti')->default('Gram');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berantas_ungkap_barang_bukti');
        Schema::dropIfExists('berantas_ungkap_tersangka');
        Schema::dropIfExists('berantas_ungkap_kasus');
    }
};