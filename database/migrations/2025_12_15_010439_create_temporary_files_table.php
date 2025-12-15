<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temporary_files', function (Blueprint $table) {
            $table->id();
            $table->string('folder');   // Nama folder unik session upload
            $table->string('filename'); // Nama file
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_files');
    }
};