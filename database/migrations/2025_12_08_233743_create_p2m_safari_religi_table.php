<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Utama Safari Religi
        Schema::create('p2m_safari_religi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                  ->references('id')->on('satuan_kerja')
                  ->onUpdate('cascade')->onDelete('cascade');
            
            $table->date('tanggal_pelaksanaan');
            $table->text('tempat_kegiatan');
            $table->integer('jumlah_masyarakat'); // Sesuai request: Jumlah Masyarakat Tersosialisasi
            $table->timestamps();
        });

        // 2. Tabel Pivot Pegawai <-> Safari Religi
        Schema::create('pegawai_p2m_safari_religi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('p2m_safari_religi_id')
                  ->constrained('p2m_safari_religi')
                  ->onDelete('cascade');

            $table->string('pegawai_nip');
            $table->foreign('pegawai_nip')
                  ->references('nip')->on('pegawai')
                  ->onDelete('cascade');

            // History Satker (Snapshot saat input)
            $table->unsignedBigInteger('saved_satuan_kerja_id')->nullable();
            $table->foreign('saved_satuan_kerja_id')
                  ->references('id')->on('satuan_kerja')
                  ->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai_p2m_safari_religi');
        Schema::dropIfExists('p2m_safari_religi');
    }
};