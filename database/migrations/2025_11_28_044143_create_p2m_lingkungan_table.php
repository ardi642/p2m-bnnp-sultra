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
        Schema::create('p2m_lingkungan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                    ->references('id')
                    ->on('satuan_kerja')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->enum('sasaran', ['Sekolah/Kampus Bersinar', 'Pondok Pesantren Bersinar', 'Tempat Hiburan Bersinar', 'Tempat Wisata Bersinar', 'Industri Bersinar']);
            $table->text('nama_tempat');
            $table->date('tanggal_pelaksanaan');
            $table->integer('jumlah_penggiat');
            $table->text('nama_penanggungjawab');
            $table->text('nomor_hp');
            $table->text('link_kelengkapan_dokumentasi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p2m_lingkungan');
    }
};
