<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Utama
        Schema::create('p2m_lingkungan_bersinar', function (Blueprint $table) {
            $table->id();
            
            // Relasi Satuan Kerja
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                ->references('id')->on('satuan_kerja')
                ->onUpdate('cascade')->onDelete('cascade');
            
            $table->enum('anggaran_pelaksanaan', ['DIPA', 'NON DIPA']);
            
            // Field Khusus
            $table->enum('sasaran_kegiatan', [
                'sekolah/kampus bersinar', 
                'pondok pesantren bersinar', 
                'tempat hiburan bersinar',
                'tempat wisata bersinar',
                'industri bersinar'
            ]);
            $table->text('nama_tempat_wilayah'); 
            $table->date('tanggal_pencanangan');
            $table->integer('jumlah_penggiat_p4gn');
            $table->string('no_hp_penanggung_jawab', 20)->nullable();
            
            $table->timestamps();
        });

        // 2. Tabel Pivot (Penanggung Jawab / Pegawai)
        Schema::create('pegawai_p2m_lingkungan_bersinar', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke Kegiatan
            // Kita beri nama constraint manual agar tidak error (max length)
            $table->foreignId('p2m_lingkungan_bersinar_id')
                ->constrained('p2m_lingkungan_bersinar')
                ->onDelete('cascade')
                ->name('fk_p2m_ling_ber_id'); 

            // Relasi ke Pegawai (NIP adalah String)
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
            
            // Mencegah duplikasi pegawai yang sama di satu kegiatan
            $table->unique(['p2m_lingkungan_bersinar_id', 'pegawai_nip'], 'unique_ling_ber_pegawai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai_p2m_lingkungan_bersinar');
        Schema::dropIfExists('p2m_lingkungan_bersinar');
    }
};