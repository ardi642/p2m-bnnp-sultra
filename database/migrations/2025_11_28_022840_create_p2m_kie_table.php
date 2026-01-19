<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Utama
        Schema::create('p2m_kie', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                  ->references('id')->on('satuan_kerja')
                  ->onUpdate('cascade')->onDelete('cascade');
            
            $table->enum('anggaran_pelaksanaan', ['DIPA', 'NON DIPA']);
            
            // Field Khusus KIE
            $table->date('tanggal_pelaksanaan');
            $table->text('tempat_kegiatan'); // Menggunakan Text agar bisa menampung banyak karakter (Rows 3)
            
            $table->timestamps();
        });

        // 2. Tabel Pivot Pegawai
        Schema::create('pegawai_p2m_kie', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('p2m_kie_id')
                  ->constrained('p2m_kie')
                  ->onDelete('cascade');

            $table->string('pegawai_nip');
            $table->foreign('pegawai_nip')
                  ->references('nip')->on('pegawai')
                  ->onDelete('cascade');

            // History Satker
            $table->unsignedBigInteger('saved_satuan_kerja_id')->nullable();
            $table->foreign('saved_satuan_kerja_id')
                  ->references('id')->on('satuan_kerja')
                  ->onDelete('set null');

            $table->timestamps();
            
            // Mencegah duplikasi
            $table->unique(['p2m_kie_id', 'pegawai_nip'], 'unique_kie_pegawai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai_p2m_kie');
        Schema::dropIfExists('p2m_kie');
    }
};