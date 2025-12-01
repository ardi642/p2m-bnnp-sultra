<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('safari_religi', function (Blueprint $table) {

            // Hapus kolom lama jika masih enum/string
            if (Schema::hasColumn('safari_religi', 'satker')) {
                $table->dropColumn('satker');
            }

            if (Schema::hasColumn('safari_religi', 'nama_pegawai')) {
                $table->dropColumn('nama_pegawai');
            }

            // Tambahkan kolom relasi satker
            $table->unsignedBigInteger('satker')->after('id');
            $table->foreign('satker')
                  ->references('id')
                  ->on('satuan_kerja')
                  ->onDelete('cascade');

            // Tambahkan kolom relasi pegawai
            $table->unsignedBigInteger('pegawai')->after('satker');
            $table->foreign('pegawai')
                  ->references('nip')
                  ->on('pegawai')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('safari_religi', function (Blueprint $table) {
            $table->dropForeign(['satker']);
            $table->dropForeign(['pegawai']);
            $table->dropColumn(['satker', 'pegawai']);

            // Optional: tambahkan kembali kolom lama jika rollback
            $table->enum('satker', [
                'BNNK Kabupaten Muna',
                'BNNK Kabupaten Kolaka',
                'BNNK Kota Kendari',
                'BNNK Bau Bau'
            ])->nullable();

            $table->string('nama_pegawai')->nullable();
        });
    }
};
