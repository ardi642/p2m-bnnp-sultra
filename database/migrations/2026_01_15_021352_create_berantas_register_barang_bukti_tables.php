<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Register (Parent)
        Schema::create('berantas_register_barang_bukti', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->date('tanggal_perolehan');
            $table->enum('sumber_perolehan', ['Hasil Tangkap', 'Temuan'])->default('Hasil Tangkap');
            $table->text('lokasi_perolehan')->nullable(); // Opsional sesuai request sebelumnya
            $table->timestamps();

            $table->foreign('satuan_kerja_id')->references('id')->on('satuan_kerja')->onDelete('cascade');
        });

        // 2. Tabel Item Barang Bukti (Child)
        Schema::create('berantas_register_barang_bukti_items', function (Blueprint $table) {
            $table->id();

            // Foreign Key dengan nama pendek custom
            $table->unsignedBigInteger('register_barang_bukti_id');
            $table->foreign('register_barang_bukti_id', 'reg_bb_items_parent_fk')
                  ->references('id')->on('berantas_register_barang_bukti')
                  ->onDelete('cascade');
            
            $table->enum('kategori', ['Narkotika', 'Non-Narkotika']);
            
            $table->unsignedBigInteger('narkotika_id')->nullable();
            $table->foreign('narkotika_id', 'reg_bb_items_narko_fk')
                  ->references('id')->on('berantas_narkotika')
                  ->onDelete('restrict');

            $table->string('nama_barang_non_narkotika')->nullable();

            $table->decimal('kuantitas', 16, 4);
            
            // --- SECURITY: STRICT ENUM DB LEVEL ---
            // Hanya menerima 'Gram', 'Kg', 'Ton'. Jika input 'Liter' masuk sini, MySQL akan menolak (Fatal Error).
            $table->enum('satuan_narkotika', ['Gram', 'Kg', 'Ton'])->nullable();
            
            // Kolom terpisah untuk satuan bebas
            $table->string('satuan_non_narkotika')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berantas_register_barang_bukti_items');
        Schema::dropIfExists('berantas_register_barang_bukti');
    }
};