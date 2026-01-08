<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berantas_tat', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('satuan_kerja_id');
            $table->foreign('satuan_kerja_id')
                  ->references('id')->on('satuan_kerja')
                  ->onUpdate('cascade')->onDelete('cascade');

            // --- DATA UMUM ---
            $table->string('no_register')->unique();
            $table->date('tanggal_pelaksanaan');
            
            // --- DATA TERSANGKA ---
            $table->text('nama_tersangka'); // Textarea
            $table->string('nik')->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            
            $table->integer('usia')->nullable(); 
            $table->string('pendidikan')->nullable(); 
            
            $table->string('pekerjaan')->nullable();
            $table->string('no_telepon')->nullable();
            
            // --- DATA KASUS ---
            $table->text('pasal_disangkakan')->nullable(); // Textarea
            $table->date('tanggal_penangkapan')->nullable();
            $table->string('jenis_narkoba')->nullable();
            $table->string('jumlah_satuan')->nullable(); 
            $table->string('instansi_pengirim')->nullable(); 
            $table->date('tanggal_permohonan')->nullable();

            // --- TIM ASESMEN ---
            $table->text('tim_hukum')->nullable(); // Textarea
            $table->text('tim_medis')->nullable(); // Textarea

            // --- HASIL & REKOMENDASI ---
            $table->string('lembaga_rehab')->nullable(); 
            
            // PERUBAHAN: Ubah jadi TEXT agar muat banyak di textarea
            $table->text('proses_hukum_lanjut')->nullable(); 
            
            $table->enum('tindak_lanjut_rekomendasi', ['dilaksanakan', 'tidak dilaksanakan'])->nullable(); 
            
            $table->decimal('biaya', 15, 2)->nullable()->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berantas_tat');
    }
};