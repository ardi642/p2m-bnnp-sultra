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
        Schema::create('p2m_tes_urine', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                ->references('id')->on('satuan_kerja')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->enum('anggaran_pelaksanaan', ['DIPA', 'NON DIPA']);
            
            // Sasaran Kegiatan (Spesifik Tes Urine)
            $table->enum('sasaran_kegiatan', [
                'Instansi Pemerintah', 
                'Lingkungan Pendidikan', 
                'Pekerja Swasta', 
                'Lingkungan Masyarakat'
            ]);

            $table->string('nama_instansi_pelaksana'); // Nama tempat/instansi tes
            $table->date('tanggal_pelaksanaan');
            $table->text('tempat_kegiatan'); // Alamat/Lokasi detil

            $table->integer('jumlah_peserta')->default(0);
            $table->integer('jumlah_positif')->default(0); // Hasil tes positif
            $table->text('keterangan_positif')->nullable(); // Rincian parameter/nama inisial yg positif
            
            $table->text('link_kelengkapan_dokumentasi')->nullable();

            $table->timestamps();
        });

        // 2. Tabel Pivot (Pegawai/Katim)
        Schema::create('pegawai_p2m_tes_urine', function (Blueprint $table) {
            $table->id();

            $table->foreignId('p2m_tes_urine_id')
                ->constrained('p2m_tes_urine')
                ->onDelete('cascade');

            $table->string('pegawai_nip');
            $table->foreign('pegawai_nip')
                ->references('nip')->on('pegawai')
                ->onDelete('cascade');

            $table->timestamps();

            $table->unique(['p2m_tes_urine_id', 'pegawai_nip'], 'unique_tes_urine_pegawai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai_p2m_tes_urine');
        Schema::dropIfExists('p2m_tes_urine');
    }
};