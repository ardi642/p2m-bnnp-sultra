<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('p2m_desa_bersinar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                ->references('id')
                ->on('satuan_kerja')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->enum('anggaran_pembentukan', ['DIPA', 'NON DIPA']);
            $table->string('nama_desa');
            $table->string('nama_kelurahan');

            $table->unsignedBigInteger('kabupaten_kota_id'); // relasi ke master
            $table->foreign('kabupaten_kota_id')
                ->references('id')
                ->on('kabupaten_kota')
                ->onDelete('restrict');

            $table->date('tanggal_pencanangan');
            $table->integer('jumlah_penggiat');
            $table->enum('keberadaan_ibm', ['ada', 'belum ada']);

            $table->string('nomor_hp_penanggung_jawab');
            $table->text('link_kelengkapan_dokumentasi');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('p2m_desa_bersinar');
    }
};