<?php

namespace App\Http\Controllers;

use App\Models\TemporaryFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TemporaryFileController extends Controller
{
    public function upload(Request $request)
    {
        if ($request->hasFile('dokumentasi')) {
            $file = $request->file('dokumentasi');

            // 1. SOLUSI ARRAY: Ambil file pertama jika input array
            if (is_array($file)) {
                $file = $file[0];
            }

            // 2. VALIDASI KEAMANAN: Cek validitas file
            if (!$file->isValid()) {
                return response()->json(['error' => 'File rusak atau tidak valid.'], 500);
            }

            try {
                // 3. PROSES SIMPAN
                $filename = $file->getClientOriginalName();
                $folder = uniqid() . '-' . now()->timestamp;
                
                // Simpan ke storage sementara
                $file->storeAs('public/tmp/' . $folder, $filename);
                
                // Catat di database sementara
                TemporaryFile::create([
                    'folder' => $folder,
                    'filename' => $filename
                ]);

                // PENTING: Return plain text folder ID agar FilePond bisa menangkapnya
                return $folder; 

            } catch (\Exception $e) {
                // Tangkap error server (misal permission folder, disk penuh)
                return response()->json(['error' => 'Gagal menyimpan file: ' . $e->getMessage()], 500);
            }
        }
        
        // --- PERBAIKAN DI SINI ---
        // Jangan return '', tapi return JSON error dengan status code 400
        return response()->json(['error' => 'Tidak ada file yang ditemukan dalam request.'], 400);
    }

    public function revert(Request $request)
    {
        // User klik tombol 'X' (Batal)
        $folder = $request->getContent();
        
        if ($folder) {
            // Hapus file fisik dan record database
            Storage::deleteDirectory('public/tmp/' . $folder);
            TemporaryFile::where('folder', $folder)->delete();
        }
        
        // Return kosong status 200 agar FilePond tahu penghapusan berhasil
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
        
        // Return 404 jika file preview tidak ditemukan (agar FilePond tidak loading terus)
        return response()->json(['error' => 'File tidak ditemukan'], 404);
    }
}