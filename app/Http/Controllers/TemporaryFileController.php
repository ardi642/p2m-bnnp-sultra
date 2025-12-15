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

            // 1. SOLUSI ARRAY: Jika input berupa array (karena name="dokumentasi[]")
            // Kita ambil file pertama (karena FilePond kirim 1 file per request)
            if (is_array($file)) {
                $file = $file[0];
            }

            // 2. VALIDASI KEAMANAN: Cek apakah file rusak/error sebelum diproses
            if (!$file->isValid()) {
                // Return error 500 halus agar FilePond merah tapi tidak crash
                return response()->json(['error' => 'File rusak atau melebihi batas upload PHP.'], 500);
            }

            // 3. PROSES SIMPAN
            $filename = $file->getClientOriginalName();
            $folder = uniqid() . '-' . now()->timestamp;
            
            // Simpan ke storage/app/public/tmp/FOLDER_UNIK/NAMA_FILE
            $file->storeAs('public/tmp/' . $folder, $filename);

            // Catat di database sementara
            TemporaryFile::create([
                'folder' => $folder,
                'filename' => $filename
            ]);

            // Kembalikan ID Folder ke FilePond (untuk disubmit nanti)
            return $folder;
        }
        
        return response()->json(['error' => 'Gagal upload. File mungkin terlalu besar.'], 500);
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
    }
}