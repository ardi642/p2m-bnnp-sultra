<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pegawai_p2m_desa_bersinar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('p2m_desa_bersinar_id')
                ->constrained('p2m_desa_bersinar')
                ->onDelete('cascade');
            $table->string('pegawai_nip');
            $table->foreign('pegawai_nip')
                ->references('nip')
                ->on('pegawai')
                ->onDelete('cascade');
            $table->timestamps();
            $table->unique(['p2m_desa_bersinar_id', 'pegawai_nip'], 'p2m_desa_bersinar_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pegawai_p2m_desa_bersinar');
    }
};