<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. TABEL TARGET TAHUNAN (Revisi: Hapus Bulan)
        Schema::create('rehab_target', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->integer('tahun'); // 2026
            
            // Angka Target (Berlaku untuk 1 Tahun Penuh)
            $table->integer('target_rawat_jalan')->default(0);
            $table->integer('target_pasca_rehab')->default(0);
            $table->integer('target_skhpn')->default(0);

            // Constraint: 1 Satker = 1 Target per Tahun
            $table->unique(['satuan_kerja_id', 'tahun'], 'unique_target_rehab_tahunan');
            
            $table->foreign('satuan_kerja_id')->references('id')->on('satuan_kerja')->onDelete('cascade');
            $table->timestamps();
        });

        // 2. TABEL LAPORAN HARIAN (Tetap sama)
        Schema::create('rehab_laporan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            
            // TANGGAL (YYYY-MM-DD)
            $table->date('tanggal'); 
            
            // REALISASI HARIAN
            $table->integer('realisasi_rawat_jalan')->default(0);
            $table->integer('realisasi_pasca_rehab')->default(0);
            $table->integer('realisasi_skhpn')->default(0);

            // Constraint: 1 Satker = 1 Laporan per Tanggal
            $table->unique(['satuan_kerja_id', 'tanggal'], 'unique_laporan_harian');

            $table->foreign('satuan_kerja_id')->references('id')->on('satuan_kerja')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rehab_laporan');
        Schema::dropIfExists('rehab_target');
    }
};