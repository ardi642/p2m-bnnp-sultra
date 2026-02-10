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
        Schema::rename('p2m_ikan_tables', 'p2m_ikan');
        Schema::rename('pegawai_p2m_ikan_tables', 'pegawai_p2m_ikan');
    }

    public function down(): void
    {
        Schema::rename('p2m_ikan', 'p2m_ikan_tables');
        Schema::rename('pegawai_p2m_ikan', 'pegawai_p2m_ikan_tables');
    }
};
