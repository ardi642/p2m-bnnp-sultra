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
        Schema::create('p2m_peran_serta_masyarakat', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                    ->references('id')
                    ->on('satuan_kerja')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->enum('anggaran_pelaksanaan', ['DIPA', 'NON DIPA']);
            $table->text('kategori_kegiatan');
            $table->text('nama_kegiatan');
            $table->date('tanggal_pelaksanaan');
            $table->text('tempat_kegiatan');
            $table->integer('jumlah_peserta');
            $table->timestamps();
        });

        // 2. Tabel Pivot untuk Pegawai (Many-to-Many)
        Schema::create('pegawai_p2m_peran_serta_masyarakat', function (Blueprint $table) {
            $table->id();

            // Foreign Key ke Kegiatan
            $table->unsignedBigInteger('p2m_peran_serta_masyarakat_id');
            $table->foreign('p2m_peran_serta_masyarakat_id', 'fk_pegawai_psm_kegiatan')
                  ->references('id')
                  ->on('p2m_peran_serta_masyarakat')
                  ->onDelete('cascade');

            // Foreign Key ke Pegawai (NIP string)
            $table->string('pegawai_nip');
            $table->foreign('pegawai_nip', 'fk_pegawai_psm_nip')
                  ->references('nip')
                  ->on('pegawai')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');

            // History Satker saat input
            $table->unsignedBigInteger('saved_satuan_kerja_id')->nullable();
            $table->foreign('saved_satuan_kerja_id')
                ->references('id')
                ->on('satuan_kerja')
                ->onDelete('set null');

            $table->timestamps();
            $table->unique(['p2m_peran_serta_masyarakat_id', 'pegawai_nip'], 'unique_psm_pegawai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai_p2m_peran_serta_masyarakat');
        Schema::dropIfExists('p2m_peran_serta_masyarakat');
    }
};
