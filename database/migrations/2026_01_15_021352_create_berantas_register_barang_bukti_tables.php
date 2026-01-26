<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Register (Parent) - Hapus sumber_perolehan dari sini
        Schema::create('berantas_register_barang_bukti', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->date('tanggal_perolehan');
            $table->text('lokasi_perolehan')->nullable();
            $table->timestamps();

            $table->foreign('satuan_kerja_id')->references('id')->on('satuan_kerja')->onDelete('cascade');
        });

        // 2. Tabel Item Barang Bukti (Child) - Tambah sumber_perolehan disini
        Schema::create('berantas_register_barang_bukti_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('register_barang_bukti_id');
            $table->foreign('register_barang_bukti_id', 'reg_bb_items_parent_fk')
                  ->references('id')->on('berantas_register_barang_bukti')
                  ->onDelete('cascade');
            
            // PINDAHAN DARI PARENT
            $table->enum('sumber_perolehan', ['Hasil Tangkap', 'Temuan'])->default('Hasil Tangkap');

            $table->enum('kategori', ['Narkotika', 'Non-Narkotika']);
            
            $table->unsignedBigInteger('narkotika_id')->nullable();
            $table->foreign('narkotika_id', 'reg_bb_items_narko_fk')
                  ->references('id')->on('berantas_narkotika')
                  ->onDelete('restrict');

            $table->string('nama_barang_non_narkotika')->nullable();

            $table->decimal('kuantitas', 16, 4);
            $table->enum('satuan_narkotika', ['Gram', 'Kg', 'Ton'])->nullable();
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