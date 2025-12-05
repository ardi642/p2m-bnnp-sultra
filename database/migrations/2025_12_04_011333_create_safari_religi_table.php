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
        Schema::create('safari_religi', function (Blueprint $table) {
            $table->id();

            // Relasi ke tabel satuan_kerja
            $table->unsignedBigInteger('satker');
            $table->foreign('satker')
                  ->references('id')
                  ->on('satuan_kerja')
                  ->onDelete('cascade');

            $table->string('anggaran_pembentukan');

            // DIPA / NON DIPA
            $table->boolean('anggaran_dipa')->default(0);
            $table->boolean('anggaran_non_dipa')->default(0);

            $table->string('nama_desa');
            $table->string('nama_kecamatan');
            $table->string('nama_kota_kabupaten');

            $table->date('tanggal_pencanangan');
            $table->string('bulan_pelaksanaan');

            $table->integer('jumlah_penggiat_p4gn');
            $table->enum('keberadaan_ibm', ['Ada', 'Belum Ada']);

            $table->string('nama_penanggung_jawab');
            $table->string('nomor_hp_penanggung_jawab');

            $table->text('link_kelengkapan_dokumentasi');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('safari_religi');
    }
};