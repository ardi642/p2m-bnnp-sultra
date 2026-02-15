<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rehab_pasien', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satuan_kerja_id')
                ->constrained('satuan_kerja')
                ->cascadeOnDelete();
            $table->string('nama_pasien');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->string('pekerjaan')->nullable();
            $table->string('pendidikan')->nullable();
            $table->integer('usia');

            // Relasi ke tabel berantas_narkotika
            $table->foreignId('narkotika_id')->nullable()
                ->constrained('berantas_narkotika')
                ->nullOnDelete();

            $table->enum('sumber_pasien', ['Voluntary', 'Compulsory']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rehab_pasien');
    }
};
