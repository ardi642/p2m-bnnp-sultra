<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Utama P2M Desa Bersinar
        Schema::create('p2m_desa_bersinar', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')->references('id')->on('satuan_kerja')->onUpdate('cascade')->onDelete('cascade');
            
            $table->unsignedBigInteger('kabupaten_kota_id');
            $table->foreign('kabupaten_kota_id')->references('id')->on('kabupaten_kota')->onUpdate('cascade')->onDelete('cascade');

            $table->enum('anggaran_pembentukan', ['DIPA', 'NON DIPA']);
            $table->string('nama_desa');
            
            // PERUBAHAN: Sekarang Wajib (Tidak Nullable)
            $table->string('nama_kelurahan'); 
            
            $table->date('tanggal_pencanangan');
            $table->integer('jumlah_penggiat');
            $table->enum('keberadaan_ibm', ['Ada', 'Belum Ada']);
            $table->string('no_hp_penanggung_jawab', 20)->nullable();
            
            $table->timestamps();
        });

        // Tabel Pivot Pegawai (Penanggung Jawab)
        Schema::create('pegawai_p2m_desa_bersinar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('p2m_desa_bersinar_id')->constrained('p2m_desa_bersinar')->onDelete('cascade');
            
            $table->string('pegawai_nip');
            $table->foreign('pegawai_nip')->references('nip')->on('pegawai')->onDelete('cascade');

            $table->unsignedBigInteger('saved_satuan_kerja_id')->nullable();
            $table->foreign('saved_satuan_kerja_id')->references('id')->on('satuan_kerja')->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai_p2m_desa_bersinar');
        Schema::dropIfExists('p2m_desa_bersinar');
    }
};