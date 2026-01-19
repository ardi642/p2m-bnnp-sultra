<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Utama
        Schema::create('p2m_upacara', function (Blueprint $table) {
            $table->id();
            
            // Relasi Satker
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                ->references('id')->on('satuan_kerja')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->enum('anggaran_pelaksanaan', ['DIPA', 'NON DIPA']);
            
            // Field Khusus Upacara
            $table->string('nama_sekolah');
            $table->date('tanggal_pelaksanaan');
            $table->integer('jumlah_peserta_upacara');
            
            $table->timestamps();
        });

        // 2. Tabel Pivot Pegawai (Many-to-Many dengan History Satker)
        Schema::create('pegawai_p2m_upacara', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('p2m_upacara_id')
                ->constrained('p2m_upacara')
                ->onDelete('cascade');

            $table->string('pegawai_nip');
            $table->foreign('pegawai_nip')
                ->references('nip')->on('pegawai')
                ->onDelete('cascade');

            // History Satker saat input (Snapshot)
            $table->unsignedBigInteger('saved_satuan_kerja_id')->nullable();
            $table->foreign('saved_satuan_kerja_id')
                ->references('id')->on('satuan_kerja')
                ->onDelete('set null');

            $table->timestamps();
            
            // Mencegah duplikasi pegawai di kegiatan yang sama
            $table->unique(['p2m_upacara_id', 'pegawai_nip'], 'unique_upacara_pegawai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai_p2m_upacara');
        Schema::dropIfExists('p2m_upacara');
    }
};