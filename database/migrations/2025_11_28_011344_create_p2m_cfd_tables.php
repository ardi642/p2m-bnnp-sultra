<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Utama Kegiatan CFD
        Schema::create('p2m_cfd', function (Blueprint $table) {
            $table->id();
            
            // Relasi Satker
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')->references('id')->on('satuan_kerja')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->enum('anggaran_pelaksanaan', ['DIPA', 'NON DIPA']);

            // Field Khusus CFD
            $table->string('nama_kegiatan'); // Tetap diadakan untuk judul laporan/pencarian
            $table->date('tanggal_pelaksanaan');
            $table->text('tempat_kegiatan');
            $table->integer('jumlah_peserta');
            
            $table->timestamps();
        });

        // 2. Tabel Pivot Pegawai - CFD
        Schema::create('pegawai_p2m_cfd', function (Blueprint $table) {
            $table->id();

            $table->foreignId('p2m_cfd_id')
                ->constrained('p2m_cfd')
                ->onDelete('cascade');

            $table->string('pegawai_nip');
            $table->foreign('pegawai_nip')->references('nip')->on('pegawai')
                ->onDelete('cascade');

            // History Satker (Snapshot saat input)
            $table->unsignedBigInteger('saved_satuan_kerja_id')->nullable();
            $table->foreign('saved_satuan_kerja_id')->references('id')->on('satuan_kerja')
                ->onDelete('set null');

            $table->timestamps();
            
            // Mencegah duplikasi input pegawai di kegiatan sama
            $table->unique(['p2m_cfd_id', 'pegawai_nip'], 'unique_cfd_pegawai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai_p2m_cfd');
        Schema::dropIfExists('p2m_cfd');
    }
};