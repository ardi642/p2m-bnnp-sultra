<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('safari_religi', function (Blueprint $table) {

            // Tambah kolom satker jika belum ada
            if (!Schema::hasColumn('safari_religi', 'satker')) {
                $table->unsignedBigInteger('satker')->after('id');
                $table->foreign('satker')
                    ->references('id')
                    ->on('satuan_kerja')
                    ->onDelete('cascade');
            }

            // Tambah kolom pegawai jika belum ada
            if (!Schema::hasColumn('safari_religi', 'pegawai')) {
                // Kolom NIP bertipe VARCHAR(255) → maka FK juga harus VARCHAR(255)
                $table->string('pegawai', 255)->after('satker');

                $table->foreign('pegawai')
                    ->references('nip')
                    ->on('pegawai')
                    ->onDelete('cascade');
            }

            // Hapus kolom nama_pegawai lama
            if (Schema::hasColumn('safari_religi', 'nama_pegawai')) {
                $table->dropColumn('nama_pegawai');
            }
        });
    }

    public function down(): void
    {
        Schema::table('safari_religi', function (Blueprint $table) {

            if (Schema::hasColumn('safari_religi', 'pegawai')) {
                try { $table->dropForeign(['pegawai']); } catch (\Exception $e) {}
            }
            if (Schema::hasColumn('safari_religi', 'satker')) {
                try { $table->dropForeign(['satker']); } catch (\Exception $e) {}
            }

            $table->dropColumn(['satker', 'pegawai']);

            // Kembalikan enum satker lama
            $table->enum('satker', [
                'BNNK Kabupaten Muna',
                'BNNK Kabupaten Kolaka',
                'BNNK Kota Kendari',
                'BNNK Bau Bau'
            ])->nullable();

            // Kembalikan kolom nama_pegawai
            $table->string('nama_pegawai')->nullable();
        });
    }
};