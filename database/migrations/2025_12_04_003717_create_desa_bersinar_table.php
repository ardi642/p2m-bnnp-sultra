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
        Schema::create('desa_bersinar', function (Blueprint $table) {
            $table->id();

            // Relasi ke satuan_kerja
            $table->unsignedBigInteger('satker');
            $table->foreign('satker')->references('id')->on('satuan_kerja')->onDelete('cascade');

            // Anggaran pembentukan
            $table->enum('anggaran_pembentukan', ['DIPA', 'NON DIPA']);

            // Data desa
            $table->string('nama_desa');
            $table->string('nama_kecamatan');
            $table->string('nama_kota_kabupaten');

            // Tanggal & bulan
            $table->date('tanggal_pencanangan');
            $table->string('bulan_pelaksanaan');

            // Penggiat & IBM
            $table->integer('jumlah_penggiat_p4gn');
            $table->enum('keberadaan_ibm', ['Ada', 'Belum Ada']);

            // Penanggung jawab
            $table->string('nama_penanggung_jawab');
            $table->string('nomor_hp_penanggung_jawab');

            // Link dokumentasi
            $table->text('link_dokumentasi');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('desa_bersinar');
    }
};
