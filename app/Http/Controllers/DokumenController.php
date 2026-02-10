<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Services\DokumenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DokumenController extends Controller
{
    /**
     * 1. DOWNLOAD SATUAN
     * Digunakan untuk mendownload satu file spesifik berdasarkan ID Dokumen.
     * Route: GET /dokumen/{id}/download
     */
    public function download($id)
    {
        // Cari data dokumen
        $file = Dokumen::findOrFail($id);
        
        // Tentukan disk (Support S3 / Local / Public)
        // Jika kolom disk kosong, default ke 'public'
        $disk = $file->disk ?? 'public';

        // Cek keberadaan file fisik
        if (!Storage::disk($disk)->exists($file->path_file)) {
            abort(404, 'File fisik tidak ditemukan di server.');
        }

        // Return download stream
        // Headers otomatis diatur oleh Laravel (Content-Type, dll)
        return Storage::disk($disk)->download($file->path_file, $file->nama_file_asli);
    }

    /**
     * 2. DOWNLOAD ZIP DARI FILE TERPILIH (BULK ACTION)
     * Menerima array ID dokumen dari Checkbox, lalu membuat ZIP on-the-fly.
     * Route: POST /dokumen/zip/selected
     */
    public function downloadZipSelected(Request $request, DokumenService $dokumenService)
    {
        // A. Validasi Input
        $request->validate([
            'ids'   => 'required|array',           // Wajib array
            'ids.*' => 'exists:dokumen,id',        // Setiap ID harus valid di DB
        ], [
            'ids.required' => 'Pilih minimal satu file untuk didownload.',
            'ids.array'    => 'Format data tidak valid.',
        ]);

        // B. Ambil Data File dari Database
        $files = Dokumen::whereIn('id', $request->ids)->get();

        if ($files->isEmpty()) {
            return back()->with('error', 'Tidak ada file valid yang ditemukan.');
        }

        // C. Tentukan Nama File ZIP Otomatis
        // Logika: Ambil nama kegiatan dari file pertama sebagai nama ZIP
        $zipName = 'berkas-download-' . date('d-m-Y_H-i') . '.zip';

        // Cek relasi parent (polimorfik) untuk nama yang lebih deskriptif
        $firstFile = $files->first();
        if ($firstFile && $firstFile->dokumenable) {
            $parent = $firstFile->dokumenable;
            
            // Cek kolom nama yang tersedia di model parent (misal: nama_kegiatan atau nama)
            $parentName = $parent->nama_kegiatan ?? $parent->nama ?? 'berkas';
            
            // Bersihkan string agar aman jadi nama file
            $cleanName = Str::slug($parentName);
            $timestamp = date('d-m-Y_H-i');
            
            $zipName = "{$cleanName}-selected-{$timestamp}.zip";
        }

        // D. Panggil Service untuk Generate ZIP
        // Service ini menghandle logic berat (Streaming S3, Hemat RAM, Rename Duplikat)
        $zipPath = $dokumenService->generateZipFromFiles($files, $zipName);

        if (!$zipPath) {
            return back()->with('error', 'Gagal membuat file ZIP.');
        }

        // E. Download & Hapus File ZIP Sementara di Server
        // deleteFileAfterSend(true) wajib ada agar folder tmp server tidak penuh sampah
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}