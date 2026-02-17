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
        // 1. Tabel Utama
        Schema::create('p2m_monev', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                    ->references('id')
                    ->on('satuan_kerja')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->enum('anggaran_pelaksanaan', ['DIPA', 'NON DIPA']);
            $table->text('nama_kegiatan');
            $table->enum('sasaran_kegiatan', ['lingkungan pendidikan', 'lingkungan pemerintah', 'lingkungan masyarakat', 'lingkungan swasta']);
            $table->date('tanggal_pelaksanaan');
            $table->text('tempat_kegiatan');
            $table->integer('jumlah_peserta');
            $table->timestamps();
        });

        // 2. Tabel Pivot untuk Pegawai (Many-to-Many)
        Schema::create('pegawai_p2m_monev', function (Blueprint $table) {
            $table->id();
            
            // Foreign Key ke Kegiatan
            $table->unsignedBigInteger('p2m_monev_id');
            $table->foreign('p2m_monev_id', 'fk_pegawai_monev_kegiatan') // Nama constraint dipendekkan agar tidak error
                  ->references('id')
                  ->on('p2m_monev')
                  ->onDelete('cascade');

            // Foreign Key ke Pegawai (NIP string)
            $table->string('pegawai_nip');
            $table->foreign('pegawai_nip', 'fk_pegawai_monev_nip')
                  ->references('nip')
                  ->on('pegawai')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            
            // History Satker saat input
            $table->unsignedBigInteger('saved_satuan_kerja_id')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai_p2m_monev');
        Schema::dropIfExists('p2m_monev');
    }
};