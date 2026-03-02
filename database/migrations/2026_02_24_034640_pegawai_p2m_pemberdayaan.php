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
        Schema::create('pegawai_p2m_pemberdayaan', function (Blueprint $table) {
            $table->id();

            // Foreign Key ke Kegiatan
            $table->unsignedBigInteger('p2m_pemberdayaan_id');
            $table->foreign('p2m_pemberdayaan_id', 'fk_pegawai_pemberdayaan_kegiatan') // Nama constraint dipendekkan agar tidak error
                ->references('id')
                ->on('p2m_pemberdayaan')
                ->onDelete('cascade');

            // Foreign Key ke Pegawai (NIP string)
            $table->string('pegawai_nip');
            $table->foreign('pegawai_nip', 'fk_pegawai_pemberdayaan_nip')
                ->references('nip')
                ->on('pegawai')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            // History Satker saat input
            $table->unsignedBigInteger('saved_satuan_kerja_id')->nullable();
            $table->foreign('saved_satuan_kerja_id')
                ->references('id')
                ->on('satuan_kerja')
                ->onDelete('set null');

            $table->timestamps();
            $table->unique(['p2m_pemberdayaan_id', 'pegawai_nip'], 'unique_kegiatan_pegawai');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai_p2m_pemberdayaan');
    }
};
