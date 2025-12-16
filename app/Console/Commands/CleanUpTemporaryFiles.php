<?php

namespace App\Console\Commands;

use App\Models\TemporaryFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanUpTemporaryFiles extends Command
{
    /**
     * Nama command yang akan dipanggil nanti.
     */
    protected $signature = 'app:clean-temp';

    /**
     * Deskripsi command.
     */
    protected $description = 'Membersihkan file sementara yang sudah kadaluarsa (lebih dari 24 jam)';

    /**
     * Eksekusi command.
     */
    public function handle()
    {
        $this->info('Memulai pembersihan file sementara...');

        // Gunakan chunkById untuk memproses data dalam batch (misal 100 per batch)
        // Ini mencegah memori server meledak jika ada ribuan file.
        $count = 0;
        
        TemporaryFile::where('created_at', '<', now()->subDay())
            ->chunkById(100, function ($expiredFiles) use (&$count) {
                
                foreach ($expiredFiles as $file) {
                    $path = 'public/tmp/' . $file->folder;
                    
                    try {
                        // Cek dan Hapus Fisik
                        if (Storage::exists($path)) {
                            Storage::deleteDirectory($path);
                        }

                        // Hapus Database
                        $file->delete();
                        $count++;
                        
                    } catch (\Exception $e) {
                        // Jika error pada 1 file, catat di log tapi JANGAN hentikan proses loop
                        // agar file lain tetap terproses.
                        Log::error("Gagal menghapus temp file ID {$file->id}: " . $e->getMessage());
                    }
                }
            });

        $this->info("Pembersihan selesai. Total file dihapus: $count");
    }

}