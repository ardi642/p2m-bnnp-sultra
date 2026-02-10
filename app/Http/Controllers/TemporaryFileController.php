<?php

namespace App\Http\Controllers;

use App\Models\TemporaryFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemporaryFileController extends Controller
{
    public function upload(Request $request)
    {
        $file = null;

        // 1. CEK INPUT: Cari file di input 'dokumentasi' ATAU 'lampiran'
        if ($request->hasFile('dokumentasi')) {
            $file = $request->file('dokumentasi');
        } elseif ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');
        }

        // Jika tidak ada file di kedua input tersebut
        if (!$file) {
            return response()->json(['error' => 'Tidak ada file yang ditemukan dalam request.'], 400);
        }

        // 2. SOLUSI ARRAY: FilePond sering mengirim array meskipun single file
        if (is_array($file)) {
            $file = $file[0];
        }

        // 3. VALIDASI KEAMANAN: Cek validitas file fisik
        if (!$file->isValid()) {
            return response()->json(['error' => 'File rusak atau tidak valid.'], 500);
        }

        try {
            // 4. PROSES SIMPAN
            $filename = $file->getClientOriginalName();
            $folder = (string) Str::uuid(); // Generate Folder ID Unik
            
            // Simpan file fisik ke folder temporary
            $file->storeAs('public/tmp/' . $folder, $filename);
            
            // Simpan record ke database (Cukup folder & filename)
            TemporaryFile::create([
                'folder' => $folder,
                'filename' => $filename
            ]);

            // PENTING: Return Folder ID (Plain Text) agar FilePond bisa menangkapnya
            return $folder;

        } catch (\Exception $e) {
            // Tangkap error server (misal permission folder, disk penuh)
            return response()->json(['error' => 'Gagal menyimpan file: ' . $e->getMessage()], 500);
        }
    }

    public function revert(Request $request)
    {
        // User klik tombol 'X' (Batal) - FilePond mengirim ID folder sebagai body text
        $folder = $request->getContent();
        
        if ($folder) {
            // Hapus file fisik dan record database
            Storage::deleteDirectory('public/tmp/' . $folder);
            TemporaryFile::where('folder', $folder)->delete();
        }
        
        return response('');
    }

    public function load(Request $request)
    {
        $folder = $request->query('file');
        
        if ($folder) {
            $tempFile = TemporaryFile::where('folder', $folder)->first();
            
            if($tempFile) {
                $path = 'public/tmp/' . $folder . '/' . $tempFile->filename;
                
                if(Storage::exists($path)){
                    $file = Storage::get($path);
                    $type = Storage::mimeType($path);
                    
                    return response($file)
                        ->header('Content-Type', $type)
                        ->header('Content-Disposition', 'inline; filename="' . $tempFile->filename . '"');
                }
            }
        }
        
        // Return 404 jika file preview tidak ditemukan
        return response()->json(['error' => 'File tidak ditemukan'], 404);
    }
}