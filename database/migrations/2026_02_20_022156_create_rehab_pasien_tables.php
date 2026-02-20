<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // 1. Tabel Data Pasien (Statis / Identitas Tetap)
        Schema::create('rehab_pasien', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->string('no_rekam_medis')->unique(); // Format: RM-2026-01-0001
            $table->string('nama_pasien'); // Bisa nama asli atau inisial
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->timestamps();

            $table->foreign('satuan_kerja_id')->references('id')->on('satuan_kerja')->onDelete('cascade');
        });

        // 2. Tabel Riwayat Kedatangan (Dinamis / Berubah sesuai waktu rehab)
        Schema::create('rehab_riwayat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rehab_pasien_id')->constrained('rehab_pasien')->onDelete('cascade');
            $table->date('tanggal_rehab');
            $table->integer('usia');
            $table->string('pendidikan');
            $table->string('pekerjaan');
            $table->enum('sumber_pasien', ['Voluntary', 'Compulsory']);
            $table->timestamps();
        });

        // 3. Tabel Pivot Riwayat & Narkotika (Many-to-Many)
        Schema::create('rehab_riwayat_narkotika', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rehab_riwayat_id')->constrained('rehab_riwayat')->cascadeOnDelete();
            $table->foreignId('narkotika_id')->constrained('berantas_narkotika')->onDelete('restrict');
        });
    }

    public function down(): void {
        Schema::dropIfExists('rehab_riwayat_narkotika');
        Schema::dropIfExists('rehab_riwayat');
        Schema::dropIfExists('rehab_pasien');
    }
};