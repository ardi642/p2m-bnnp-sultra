<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Utama Kegiatan Tes Urine
        Schema::create('p2m_tes_urine', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')->references('id')->on('satuan_kerja')->onUpdate('cascade')->onDelete('cascade');
            
            $table->enum('anggaran_pelaksanaan', ['DIPA', 'NON DIPA']);
            $table->string('nama_instansi'); // Pengganti nama_kegiatan
            $table->enum('sasaran_kegiatan', ['lingkungan pemerintah', 'lingkungan pendidikan', 'lingkungan swasta', 'lingkungan masyarakat']);
            $table->date('tanggal_pelaksanaan');
            $table->text('tempat_kegiatan'); // Alamat
            
            // Data Peserta & Hasil
            $table->integer('jumlah_peserta');
            $table->integer('jumlah_positif')->default(0);
            $table->text('keterangan_positif')->nullable(); // Keterangan parameter indikasi
            
            $table->timestamps();
        });

        // Tabel Pivot Pegawai (Panitia Pelaksana)
        Schema::create('pegawai_p2m_tes_urine', function (Blueprint $table) {
            $table->id();
            $table->foreignId('p2m_tes_urine_id')->constrained('p2m_tes_urine')->onDelete('cascade');
            
            $table->string('pegawai_nip');
            $table->foreign('pegawai_nip')->references('nip')->on('pegawai')->onDelete('cascade');

            // History Satker Pegawai saat kegiatan (Snapshot)
            $table->unsignedBigInteger('saved_satuan_kerja_id')->nullable();
            $table->foreign('saved_satuan_kerja_id')->references('id')->on('satuan_kerja')->onDelete('set null');

            $table->timestamps();
            $table->unique(['p2m_tes_urine_id', 'pegawai_nip'], 'unique_tes_urine_pegawai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai_p2m_tes_urine');
        Schema::dropIfExists('p2m_tes_urine');
    }
};