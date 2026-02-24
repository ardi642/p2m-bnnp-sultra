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
        Schema::table('p2m_peran_serta_masyarakat', function (Blueprint $table) {
            $table->dropColumn('jenis_kegiatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('p2m_peran_serta_masyarakat', function (Blueprint $table) {
            $table->text('jenis_kegiatan')->after('kategori_kegiatan')->nullable();
        });
    }
};
