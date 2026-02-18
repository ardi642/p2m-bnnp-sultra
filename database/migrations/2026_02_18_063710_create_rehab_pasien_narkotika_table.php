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
        Schema::create('rehab_pasien_narkotika', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rehab_pasien_id')
                ->constrained('rehab_pasien')
                ->cascadeOnDelete();
            $table->foreignId('narkotika_id')
                ->constrained('berantas_narkotika')
                ->cascadeOnDelete();
            $table->timestamps();

            // mencegah duplikasi
            $table->unique(['rehab_pasien_id', 'narkotika_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rehab_pasien_narkotika');
    }
};
