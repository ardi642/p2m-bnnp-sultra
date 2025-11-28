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
        Schema::create('p2m_sosialisasi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                    ->references('id')
                    ->on('satuan_kerja')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->enum('anggaran_pelaksanaan', ['DIPA', 'NON DIPA']);
            $table->text('nama_kegiatan');
            $table->enum('sasaran_kegiatan', ['lingkungan pendidikan', 'lingkungan kerja', 'lingkungan masyarakat']);
            $table->date('tanggal_pelaksanaan');
            $table->text('tempat_kegiatan');
            $table->text('nama_pegawai');
            $table->integer('jumlah_peserta');
            $table->text('link_kelengkapan_dokumentasi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p2m_sosialisasi');
    }
};
