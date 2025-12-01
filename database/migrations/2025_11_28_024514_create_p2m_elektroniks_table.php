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
        Schema::create('p2m_elektronik', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                    ->references('id')
                    ->on('satuan_kerja')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->enum('anggaran_pelaksanaan', ['DIPA', 'NON DIPA']);
            $table->enum('Media', ['Televisi', 'Radio', 'Video Tron', 'Bioskop', 'TV Plasma', 'Media Lain']);
            $table->text('durasi_pelaksanaan');
            $table->date('tanggal_pelaksanaan');
            $table->text('nama_media');
            $table->text('link_kelengkapan_dokumentasi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p2m_elektronik');
    }
};
