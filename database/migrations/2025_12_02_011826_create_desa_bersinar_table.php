<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDesaBersinarTable extends Migration
{
    public function up()
    {
        Schema::create('desa_bersinar', function (Blueprint $table) {
            $table->id();

            // Foreign key ke tabel satuan_kerja
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')->references('id')->on('satuan_kerja')->onDelete('cascade');

            // Anggaran pembentukan
            $table->enum('anggaran_pembentukan', ['DIPA', 'NON DIPA'])->nullable();

            // Lokasi
            $table->string('nama_desa')->nullable();
            $table->string('nama_kecamatan')->nullable();

            // Kabupaten / Kota — Provinsi Sulawesi Tenggara
            $table->enum('kabupaten_kota', [
                'Kabupaten Bombana',
                'Kabupaten Buton',
                'Kabupaten Buton Selatan',
                'Kabupaten Buton Tengah',
                'Kabupaten Buton Utara',
                'Kabupaten Kolaka',
                'Kabupaten Kolaka Timur',
                'Kabupaten Kolaka Utara',
                'Kabupaten Konawe',
                'Kabupaten Konawe Kepulauan',
                'Kabupaten Konawe Selatan',
                'Kabupaten Konawe Utara',
                'Kabupaten Muna',
                'Kabupaten Muna Barat',
                'Kabupaten Wakatobi',
                'Kota Baubau',
                'Kota Kendari'
            ])->nullable();

            // Tanggal & bulan pelaksanaan
            $table->date('tanggal_pencanangan')->nullable();

            $table->enum('bulan_pelaksanaan', [
                'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
                'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'
            ])->nullable();

            // Jumlah penggiat
            $table->unsignedInteger('jumlah_penggiat_p4gn')->nullable();

            // Keberadaan IBM
            $table->enum('keberadaan_ibm', ['Ada', 'Belum Ada'])->nullable();

            // Penanggung jawab
            $table->string('nama_penanggung_jawab')->nullable();
            $table->string('nomor_hp_penanggung_jawab')->nullable();

            // Link dokumentasi
            $table->text('link_kelengkapan_dokumentasi')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index untuk performa
            $table->index('satuan_kerja_id');
            $table->index('kabupaten_kota');
        });
    }

    public function down()
    {
        Schema::dropIfExists('desa_bersinar');
    }
}
