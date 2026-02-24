<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('rehab_pasien', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->string('id_pasien'); 
            $table->string('nama_pasien');
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->timestamps();

            $table->foreign('satuan_kerja_id')
                  ->references('id')
                  ->on('satuan_kerja')
                  ->onDelete('cascade');
        });

        Schema::create('rehab_riwayat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rehab_pasien_id')
                  ->constrained('rehab_pasien')
                  ->onDelete('cascade');
            $table->date('tanggal_rehab');
            $table->string('pendidikan');
            $table->string('pekerjaan');
            $table->string('sumber_pasien');
            $table->timestamps();
        });

        Schema::create('rehab_riwayat_narkotika', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rehab_riwayat_id')
                  ->constrained('rehab_riwayat')
                  ->cascadeOnDelete();
            $table->foreignId('narkotika_id')
                  ->constrained('berantas_narkotika')
                  ->onDelete('restrict');
        });
    }

    public function down(): void {
        Schema::dropIfExists('rehab_riwayat_narkotika');
        Schema::dropIfExists('rehab_riwayat');
        Schema::dropIfExists('rehab_pasien');
    }
};