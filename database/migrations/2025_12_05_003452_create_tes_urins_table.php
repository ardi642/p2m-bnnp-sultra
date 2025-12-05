<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tes_urin', function (Blueprint $table) {
            $table->id();

            // Relasi ke satuan kerja
            $table->foreignId('satker_id')->constrained('satuan_kerja')->onDelete('cascade');

            // Anggaran Pelaksanaan
            $table->enum('anggaran_pelaksanaan', ['DIPA', 'NON DIPA']);

            // Sasaran Kegiatan
            $table->enum('sasaran_kegiatan', [
                'Instansi Pemerintah',
                'Lingkungan Pendidikan',
                'Pekerja Swasta',
                'Lingkungan Masyarakat'
            ]);

            // Informasi kegiatan
            $table->string('nama_instansi_pelaksana');
            $table->date('tanggal_pelaksanaan');

            // Penanggung jawab
            $table->string('nama_katim');

            // Dokumentasi
            $table->string('link_kelengkapan_dokumentasi');

            // Data peserta
            $table->integer('jumlah_peserta_test_urin');
            $table->integer('jumlah_terindikasi_positif')->default(0);

            // Keterangan parameter positif
            $table->text('keterangan_parameter_positif')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tes_urin');
    }
};