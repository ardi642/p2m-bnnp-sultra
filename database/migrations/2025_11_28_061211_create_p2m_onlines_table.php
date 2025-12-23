<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('p2m_online', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                ->references('id')->on('satuan_kerja')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->enum('anggaran_pelaksanaan', ['DIPA', 'NON DIPA']);
            
            // Enum Ringkas di DB
            $table->enum('jenis_media', [
                'Media Online', 
                'Medsos Stakeholder', 
                'Media lain'
            ]);
            
            $table->string('nama_media');
            $table->date('tanggal_mulai_pelaksanaan');
            $table->integer('durasi_pelaksanaan'); // Dalam Hari
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('p2m_online');
    }
};