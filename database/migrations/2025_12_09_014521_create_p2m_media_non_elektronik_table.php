<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('p2m_media_non_elektronik', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                ->references('id')
                ->on('satuan_kerja')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->enum('anggaran_pelaksanaan', ['DIPA', 'NON DIPA']);
            
            // Enum sesuai kategori utama
            $table->enum('jenis_media', [
                'Media Cetak', 
                'Media Luar Ruang', 
                'Branding Sarana Publik'
            ]);

            $table->integer('durasi_pelaksanaan'); // Dalam hari
            $table->date('tanggal_pelaksanaan'); // Tanggal mulai
            $table->text('tempat_kegiatan'); // Tempat pemasangan
            $table->text('link_kelengkapan_dokumentasi');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('p2m_media_non_elektronik');
    }
};