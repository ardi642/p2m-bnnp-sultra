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
        Schema::create('p2m_pelatihan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                    ->references('id')
                    ->on('satuan_kerja')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            $table->enum('anggaran_pelaksanaan', ['DIPA', 'NON DIPA']);
            $table->text('nama_kegiatan');
            $table->enum('sasaran_kegiatan', ['lingkungan pendidikan', 'lingkungan pemerintah', 'lingkungan masyarakat', 'lingkungan swasta']);
            $table->date('tanggal_pelaksanaan');
            $table->text('tempat_kegiatan');
            $table->integer('jumlah_peserta');
            $table->timestamps();
        });

        Schema::create('pegawai_p2m_pelatihan', function (Blueprint $table) {
            $table->id();

            // 1. Relasi ke Kegiatan (Masih pakai ID/Integer standar)
            $table->foreignId('p2m_pelatihan_id')
                ->constrained('p2m_pelatihan')
                ->onDelete('cascade');

            // 2. Relasi ke Pegawai (HARUS STRING karena NIP adalah String)
            $table->string('pegawai_nip'); 
            
            // Definisikan Foreign Key secara manual
            $table->foreign('pegawai_nip')
                ->references('nip') // Mengacu ke kolom 'nip'
                ->on('pegawai')     // Di tabel 'pegawai'
                ->onDelete('cascade');


            //  HISTORY SATKER (SNAPSHOT) - INI YANG BARU
            // Disimpan nullable jaga-jaga, onDelete set null agar history aman meski satker master dihapus
            $table->unsignedBigInteger('saved_satuan_kerja_id')->nullable();
            $table->foreign('saved_satuan_kerja_id')
                ->references('id')
                ->on('satuan_kerja')
                ->onDelete('set null');

            $table->timestamps();
            // Opsional: Mencegah duplikasi (satu pegawai tidak bisa input 2x di kegiatan yang sama)
            $table->unique(['p2m_pelatihan_id', 'pegawai_nip'], 'unique_kegiatan_pegawai');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai_p2m_pelatihan');
        Schema::dropIfExists('p2m_pelatihan');
    }
};
