<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rehab_laporan_bulanan', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke Satuan Kerja
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                  ->references('id')
                  ->on('satuan_kerja')
                  ->onDelete('cascade');
            
            // PERIODE (YYYY-MM-01)
            $table->date('periode'); 
            
            // INDIKATOR 1: RAWAT JALAN
            $table->integer('target_rawat_jalan')->default(0);
            $table->integer('realisasi_rawat_jalan')->default(0);
            
            // INDIKATOR 2: PASCA REHABILITASI
            $table->integer('target_pasca_rehab')->default(0);
            $table->integer('realisasi_pasca_rehab')->default(0);
            
            // INDIKATOR 3: SKHPN
            $table->integer('target_skhpn')->default(0);
            $table->integer('realisasi_skhpn')->default(0);

            // Mencegah duplikasi: 1 Satker = 1 Laporan per Bulan
            $table->unique(['satuan_kerja_id', 'periode'], 'unique_rehab_periode');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rehab_laporan_bulanan');
    }
};