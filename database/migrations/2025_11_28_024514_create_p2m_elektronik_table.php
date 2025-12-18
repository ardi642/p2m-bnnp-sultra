<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hanya satu tabel, tidak ada pivot pegawai
        Schema::create('p2m_elektronik', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')->references('id')->on('satuan_kerja')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->enum('anggaran_pelaksanaan', ['DIPA', 'NON DIPA']);
            
            // Enum Jenis Media
            $table->enum('jenis_media', ['televisi', 'radio', 'video tron', 'bioskop', 'tv plasma', 'media lain']);
            
            $table->string('nama_media'); // Nama Stasiun TV/Radio/Lokasi Videotron
            $table->date('tanggal_pelaksanaan');
            $table->integer('durasi_pelaksanaan'); // Satuan Hari
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('p2m_elektronik');
    }
};