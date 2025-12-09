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
        // 1. Tabel Utama: p2m_safari_religi
        Schema::create('p2m_safari_religi', function (Blueprint $table) {
            $table->id();

            // Relasi Satuan Kerja
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                ->references('id')->on('satuan_kerja')
                ->onUpdate('cascade')->onDelete('cascade');

            // Data Kegiatan
            $table->text('tempat_kegiatan');
            $table->date('tanggal_pelaksanaan');
            
            // Jumlah Masyarakat yang tersosialisasi
            $table->integer('jumlah_masyarakat')->default(0); 
            
            // Dokumentasi
            $table->text('link_kelengkapan_dokumentasi')->nullable();

            $table->timestamps();
        });

        // 2. Tabel Pivot: pegawai_p2m_safari_religi
        Schema::create('pegawai_p2m_safari_religi', function (Blueprint $table) {
            $table->id();

            // Relasi ke Safari Religi
            $table->foreignId('p2m_safari_religi_id')
                ->constrained('p2m_safari_religi')
                ->onDelete('cascade');

            // Relasi ke Pegawai (NIP String)
            $table->string('pegawai_nip');
            $table->foreign('pegawai_nip')
                ->references('nip')->on('pegawai')
                ->onDelete('cascade');

            $table->timestamps();
            
            // Mencegah duplikasi pegawai di kegiatan yang sama
            $table->unique(['p2m_safari_religi_id', 'pegawai_nip'], 'unique_safari_religi_pegawai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai_p2m_safari_religi');
        Schema::dropIfExists('p2m_safari_religi');
    }
};