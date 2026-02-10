<?php

namespace App\Services;

use App\Models\TemporaryFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DokumenService
{
    public function moveToPermanent(array $tempFolders, $model, string $kategori, array &$movedFilesLog): void
    {
        $sourceDisk = 'public'; 
        $targetDisk = config('filesystems.default'); 

        $modelFolder = $model->getStorageFolder(); 
        $datePath    = date('Y-m');
        $basePath    = "uploads/{$modelFolder}/{$datePath}";

        foreach ($tempFolders as $folder) {
            $tempFile = TemporaryFile::where('folder', $folder)->first();

            if ($tempFile) {
                // KEMBALIKAN KE 'public/tmp' AGAR SESUAI DENGAN CONTROLLER
                $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;

                if (Storage::disk($sourceDisk)->exists($sourcePath)) {
                    
                    $ext      = pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                    $nameOnly = pathinfo($tempFile->filename, PATHINFO_FILENAME);
                    
                    // Ambil Metadata
                    $mimeType = Storage::disk($sourceDisk)->mimeType($sourcePath); 
                    $size     = Storage::disk($sourceDisk)->size($sourcePath);     

                    // Generate UUID
                    $cleanFileName   = $kategori . '-' . (string) Str::uuid() . '.' . $ext;
                    $destinationPath = $basePath . '/' . $cleanFileName;

                    // Pindahkan File
                    Storage::disk($targetDisk)->put(
                        $destinationPath, 
                        Storage::disk($sourceDisk)->readStream($sourcePath)
                    );
                    
                    $movedFilesLog[] = $destinationPath; 

                    // Simpan Database
                    $model->dokumen()->create([
                        'kategori'       => $kategori,
                        'nama_file_asli' => $tempFile->filename,
                        'path_file'      => $destinationPath,
                        'tipe_file'      => $mimeType,
                        'ukuran_file'    => $size,
                        'disk'           => $targetDisk
                    ]);

                    // Cleanup
                    $tempFile->delete();
                    Storage::disk($sourceDisk)->deleteDirectory('tmp/' . $folder);
                }
            }
        }
    }


    /**
     * Membuat ZIP dari sekumpulan object Dokumen (Collection).
     * Bisa dari checkbox, bisa dari kategori, bebas.
     */
    public function generateZipFromFiles($files, ?string $customZipName = null): ?string
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        if ($files->isEmpty()) return null;

        // 1. Tentukan Nama ZIP
        if (!$customZipName) {
            // Default: download-selected- tanggal_jam.zip
            $timestamp = date('d-m-Y_H-i');
            $customZipName = "download-selected-{$timestamp}.zip";
        }
        
        // Buat folder tmp
        if (!file_exists(public_path('tmp'))) {
            mkdir(public_path('tmp'), 0755, true);
        }
        $zipPath = public_path("tmp/{$customZipName}");
        $tempFilesToDelete = []; 

        // 2. Proses ZIP (Logic sama persis: Stream & Hemat RAM)
        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            
            $usedNames = [];

            foreach ($files as $file) {
                $disk = $file->disk ?? 'public';
                
                if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($file->path_file)) {
                    
                    // Anti Bentrok Nama
                    $filename = $file->nama_file_asli;
                    $base = pathinfo($filename, PATHINFO_FILENAME);
                    $ext = pathinfo($filename, PATHINFO_EXTENSION);
                    $c = 1;
                    while (in_array($filename, $usedNames)) {
                        $filename = $base . '_' . $c++ . '.' . $ext;
                    }
                    $usedNames[] = $filename;

                    // Masukkan ke ZIP (Stream / Local)
                    if ($disk == 'public' || $disk == 'local') {
                        $absPath = \Illuminate\Support\Facades\Storage::disk($disk)->path($file->path_file);
                        $zip->addFile($absPath, $filename);
                    } else {
                        $readStream = \Illuminate\Support\Facades\Storage::disk($disk)->readStream($file->path_file);
                        $tempUuid = \Illuminate\Support\Str::uuid();
                        $localTemp = public_path("tmp/{$tempUuid}");
                        
                        $write = fopen($localTemp, 'w');
                        stream_copy_to_stream($readStream, $write);
                        fclose($write);
                        
                        $zip->addFile($localTemp, $filename);
                        $tempFilesToDelete[] = $localTemp;
                    }
                }
            }
            $zip->close();
        }

        foreach ($tempFilesToDelete as $temp) {
            if (file_exists($temp)) unlink($temp);
        }

        return $zipPath;
    }

}