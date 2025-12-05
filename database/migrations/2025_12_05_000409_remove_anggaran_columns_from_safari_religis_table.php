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
        Schema::table('safari_religi', function (Blueprint $table) {
            $table->dropColumn(['anggaran_dipa', 'anggaran_non_dipa']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('safari_religi', function (Blueprint $table) {
            $table->decimal('anggaran_dipa', 15, 2)->nullable();
            $table->decimal('anggaran_non_dipa', 15, 2)->nullable();
        });
    }
};
