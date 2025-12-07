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
        Schema::table('users', function (Blueprint $table) {
            // Tambah Kolom Role
            // Kita taruh 'after' password biar rapi di database
            $table->enum('role', ['admin', 'operator'])
                ->default('operator')
                ->after('password'); 

            // Tambah Kolom Pegawai NIP
            $table->string('pegawai_nip')
                ->nullable()
                ->after('role');

            // Tambah Foreign Key
            $table->foreign('pegawai_nip')
                ->references('nip')->on('pegawai')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['pegawai_nip']);
            $table->dropColumn(['role', 'pegawai_nip']);
        });
    }
};
