<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safari_religi', function (Blueprint $table) {
            $table->id();

            // Satker (gunakan enum sesuai daftar)
            $table->enum('satker', [
                'BNNP JATENG',
                'BNNK KENDAL',
                'BNNK BATANG',
                'BNNK TEGAL',
                'BNNK CILACAP',
                'BNNK BANYUMAS',
                'BNNK PURBALINGGA',
                'BNNK MAGELANG',
                'BNNK TEMANGGUNG',
                'BNNK SURAKARTA'
            ]);

            // Tempat kegiatan
            $table->string('tempat_kegiatan');

            // Tanggal pelaksanaan acara
            $table->date('tanggal_pelaksanaan');

            // Bulan pelaksanaan (enum)
            $table->enum('bulan_pelaksanaan', [
                'JANUARI',
                'FEBRUARI',
                'MARET',
                'APRIL',
                'MEI',
                'JUNI',
                'JULI',
                'AGUSTUS',
                'SEPTEMBER',
                'OKTOBER',
                'NOVEMBER',
                'DESEMBER'
            ]);

            // Nama pegawai yang ditugaskan
            $table->string('nama_pegawai');

            // Jumlah masyarakat tersosialisasi
            $table->integer('jumlah_masyarakat');

            // Link kelengkapan dan dokumentasi
            $table->text('link_dokumentasi');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safari_religi');
    }
};