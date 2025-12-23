<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('p2m_non_elektronik', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                ->references('id')->on('satuan_kerja')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->enum('anggaran_pelaksanaan', ['DIPA', 'NON DIPA']);
            
            // Enum Ringkas di Database
            $table->enum('jenis_media', [
                'Media Cetak', 
                'Media Luar Ruang', 
                'Branding Sarana Publik'
            ]);
            
            $table->text('tempat_pemasangan');
            $table->date('tanggal_mulai_pelaksanaan');
            $table->integer('durasi_pelaksanaan'); // Dalam Hari
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('p2m_non_elektronik');
    }
};