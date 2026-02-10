<?php

namespace App\Services;

use App\Models\TemporaryFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DokumenService
{
    /**
     * Memindahkan File Fisik dari Temp ke Permanent
     */
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
                $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;

                if (Storage::disk($sourceDisk)->exists($sourcePath)) {
                    
                    $ext      = pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                    $nameOnly = pathinfo($tempFile->filename, PATHINFO_FILENAME);
                    
                    $mimeType = Storage::disk($sourceDisk)->mimeType($sourcePath); 
                    $size     = Storage::disk($sourceDisk)->size($sourcePath);      

                    $cleanFileName   = $kategori . '-' . (string) Str::uuid() . '.' . $ext;
                    $destinationPath = $basePath . '/' . $cleanFileName;

                    // Pindahkan File
                    Storage::disk($targetDisk)->put(
                        $destinationPath, 
                        Storage::disk($sourceDisk)->readStream($sourcePath)
                    );
                    
                    $movedFilesLog[] = $destinationPath; 

                    // Simpan Database (File Fisik)
                    $model->dokumen()->create([
                        'kategori'       => $kategori,
                        'nama_file_asli' => $tempFile->filename,
                        'path_file'      => $destinationPath,
                        'tipe_file'      => $mimeType,
                        'ukuran_file'    => $size,
                        'disk'           => $targetDisk,
                        'is_link'        => false, // Ini File
                        'path_url'       => null
                    ]);

                    // Cleanup
                    $tempFile->delete();
                    Storage::disk($sourceDisk)->deleteDirectory('tmp/' . $folder);
                }
            }
        }
    }

    /**
     * Menyimpan Link Eksternal ke Database
     */
    public function saveLinks(array $linksData, $model, string $kategori): void
    {
        // Struktur input array: [ ['nama' => 'Label', 'url' => 'https://...'], ... ]
        foreach ($linksData as $link) {
            if (!empty($link['nama']) && !empty($link['url'])) {
                $model->dokumen()->create([
                    'kategori'       => $kategori,
                    'nama_file_asli' => $link['nama'], // Label Link
                    'path_url'       => $link['url'],  // URL Link
                    'is_link'        => true,          // Penanda Link
                    'path_file'      => null,
                    'tipe_file'      => null,
                    'ukuran_file'    => null,
                    'disk'           => null
                ]);
            }
        }
    }

    /**
     * Generate ZIP (Hanya untuk File Fisik, Link diabaikan)
     */
    public function generateZipFromFiles($files, ?string $customZipName = null): ?string
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        // Filter: Hanya ambil yang BUKAN Link
        $physicalFiles = $files->where('is_link', false);

        if ($physicalFiles->isEmpty()) return null;

        if (!$customZipName) {
            $timestamp = date('d-m-Y_H-i');
            $customZipName = "download-selected-{$timestamp}.zip";
        }
        
        if (!file_exists(public_path('tmp'))) {
            mkdir(public_path('tmp'), 0755, true);
        }
        $zipPath = public_path("tmp/{$customZipName}");
        $tempFilesToDelete = []; 

        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            
            $usedNames = [];

            foreach ($physicalFiles as $file) {
                $disk = $file->disk ?? 'public';
                
                if (\Illuminate\Support\Facades\Storage::disk($disk)->exists($file->path_file)) {
                    
                    $filename = $file->nama_file_asli;
                    $base = pathinfo($filename, PATHINFO_FILENAME);
                    $ext = pathinfo($filename, PATHINFO_EXTENSION);
                    $c = 1;
                    while (in_array($filename, $usedNames)) {
                        $filename = $base . '_' . $c++ . '.' . $ext;
                    }
                    $usedNames[] = $filename;

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