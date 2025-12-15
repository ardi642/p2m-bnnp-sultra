<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use App\Models\TemporaryFile;

// Jalankan setiap hari pukul 00:00
Schedule::call(function () {
    // Cari file temp yang dibuat sebelum 24 jam yang lalu
    $oldFiles = TemporaryFile::where('created_at', '<', now()->subDay())->get();

    foreach ($oldFiles as $file) {
        // Hapus folder fisik di storage
        Storage::deleteDirectory('public/tmp/' . $file->folder);
        // Hapus record di database
        $file->delete();
    }
})->daily();