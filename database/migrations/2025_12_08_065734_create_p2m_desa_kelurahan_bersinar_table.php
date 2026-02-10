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
        // 1. Tabel Utama: P2M Desa/Kelurahan Bersinar
        Schema::create('p2m_desa_kelurahan_bersinar', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke Satuan Kerja
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                ->references('id')
                ->on('satuan_kerja')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            
            // Relasi ke Kabupaten/Kota
            $table->unsignedBigInteger('kabupaten_kota_id');
            $table->foreign('kabupaten_kota_id')
                ->references('id')
                ->on('kabupaten_kota')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->enum('anggaran_pembentukan', ['DIPA', 'NON DIPA']);
            $table->string('nama_desa_kelurahan');
                        
            $table->date('tanggal_pencanangan');
            $table->integer('jumlah_penggiat');
            $table->enum('keberadaan_ibm', ['Ada', 'Belum Ada']);
            $table->string('no_hp_penanggung_jawab', 20)->nullable();
            
            $table->timestamps();
        });

        // 2. Tabel Pivot: Pegawai (Penanggung Jawab)
        Schema::create('pegawai_p2m_desa_kelurahan_bersinar', function (Blueprint $table) {
            $table->id();

            // --- PERBAIKAN: Menggunakan nama constraint kustom agar tidak kepanjangan ---
            
            // Kolom ID Kegiatan
            $table->unsignedBigInteger('p2m_desa_kelurahan_bersinar_id');
            
            // Syntax: foreign('nama_kolom', 'NAMA_CONSTRAINT_KUSTOM')
            $table->foreign('p2m_desa_kelurahan_bersinar_id', 'fk_desa_kelurahan_bersinar') 
                ->references('id')
                ->on('p2m_desa_kelurahan_bersinar') // Pastikan nama tabel referensi benar
                ->onDelete('cascade');
            
            // Kolom NIP Pegawai
            $table->string('pegawai_nip');
            
            // Syntax: foreign('nama_kolom', 'NAMA_CONSTRAINT_KUSTOM')
            $table->foreign('pegawai_nip', 'fk_desa_kelurahan_bersinar_nip')
                ->references('nip')
                ->on('pegawai')
                ->onDelete('cascade');

            // Kolom History Satker
            $table->unsignedBigInteger('saved_satuan_kerja_id')->nullable();
            $table->foreign('saved_satuan_kerja_id', 'fk_desa_kelurahan_bersinar_saved_satker')
                ->references('id')
                ->on('satuan_kerja')
                ->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai_p2m_desa_kelurahan_bersinar');
        Schema::dropIfExists('p2m_desa_kelurahan_bersinar');
    }
};