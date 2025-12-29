<?php

namespace App\Services\Berantas;

use App\Models\BerantasUngkapKasus;
use App\Models\TemporaryFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UngkapKasusService
{
    // --- HANDLE STORE (CREATE) ---
    public function handleStore($dataKasus, $request)
    {
        return DB::transaction(function () use ($dataKasus, $request) {
            // 1. Buat Parent
            $kasus = BerantasUngkapKasus::create($dataKasus);

            // 2. Proses Tersangka (Dapatkan Mapping ID Temp -> ID Asli)
            $mapTempIdToDbId = $this->syncTersangka($kasus, $request->tersangka);

            // 3. Proses Barang Bukti (Gunakan mapping tadi)
            $this->syncBarangBukti($kasus, $request->barang_bukti, $mapTempIdToDbId);

            // 4. Proses Dokumentasi
            $this->handleDokumentasi($kasus, $request->dokumentasi);

            return $kasus;
        });
    }

    // --- HANDLE UPDATE (EDIT) ---
    public function handleUpdate($kasus, $dataKasus, $request)
    {
        return DB::transaction(function () use ($kasus, $dataKasus, $request) {
            // 1. Update Parent
            $kasus->update($dataKasus);

            // 2. Proses Tersangka (Validasi Hapus & Update)
            $mapTempIdToDbId = $this->syncTersangka($kasus, $request->tersangka, $request->barang_bukti);

            // 3. Proses Barang Bukti
            $this->syncBarangBukti($kasus, $request->barang_bukti, $mapTempIdToDbId);

            // 4. Proses Dokumentasi
            if ($request->filled('delete_files')) {
                $this->deleteDokumentasi($kasus, $request->delete_files);
            }
            $this->handleDokumentasi($kasus, $request->dokumentasi);

            return $kasus;
        });
    }

    // ================= LOGIKA INTI (PRIVATE) =================

    private function syncTersangka($kasus, $tersangkaData, $barangBuktiData = [])
    {
        $tersangkaData = $tersangkaData ?? [];
        
        // A. HAPUS DATA YANG HILANG DARI FORM
        $existingIdsInForm = collect($tersangkaData)->pluck('id')->filter()->toArray();
        $idsToDelete = $kasus->tersangka()->whereNotIn('id', $existingIdsInForm)->pluck('id')->toArray();

        if (!empty($idsToDelete)) {
            // Validasi Backend: Cek apakah ID yang mau dihapus dipakai di Barang Bukti
            if (!empty($barangBuktiData)) {
                foreach ($barangBuktiData as $bb) {
                    // Cek field pemilik, apakah match dengan ID yang mau dihapus
                    if (in_array($bb['pemilik'], $idsToDelete)) {
                        throw ValidationException::withMessages([
                            'tersangka' => 'Gagal menyimpan! Ada Tersangka yang dihapus namun masih dipilih sebagai pemilik Barang Bukti.'
                        ]);
                    }
                }
            }

            // Hapus Fisik Foto & Record DB
            $tersangkaHapus = $kasus->tersangka()->whereIn('id', $idsToDelete)->get();
            foreach ($tersangkaHapus as $t) {
                if ($t->foto_tersangka && Storage::disk('public')->exists($t->foto_tersangka)) {
                    Storage::disk('public')->delete($t->foto_tersangka);
                }
                $t->delete();
            }
        }

        // B. UPDATE ATAU CREATE
        $mapTempIdToDbId = []; 

        foreach ($tersangkaData as $data) {
            $pathFoto = null;
            $model = null;

            // Upload Foto Baru
            if (isset($data['foto']) && $data['foto'] instanceof \Illuminate\Http\UploadedFile) {
                $file = $data['foto'];
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $pathFoto = $file->storeAs('berantas/ungkap/tersangka', $filename, 'public');
            }

            $payload = [
                'nama_tersangka' => $data['nama'],
                'jenis_kelamin'  => $data['jk'],
                'pekerjaan'      => $data['pekerjaan'] ?? null,
                'status_tahap'   => $data['tahap'],
            ];
            if ($pathFoto) $payload['foto_tersangka'] = $pathFoto;

            if (isset($data['id']) && $data['id']) {
                // UPDATE
                $model = $kasus->tersangka()->find($data['id']);
                if ($pathFoto && $model->foto_tersangka && Storage::disk('public')->exists($model->foto_tersangka)) {
                    Storage::disk('public')->delete($model->foto_tersangka);
                }
                $model->update($payload);
            } else {
                // CREATE
                $model = $kasus->tersangka()->create($payload);
            }

            // Simpan Mapping: Temp ID (dari Alpine JS) => Real DB ID
            // Jika data lama, key-nya adalah ID itu sendiri. Jika baru, key-nya adalah string 'new_xxx'
            $key = $data['temp_id'] ?? $model->id; 
            $mapTempIdToDbId[$key] = $model->id;
        }

        return $mapTempIdToDbId;
    }

    private function syncBarangBukti($kasus, $bbData, $mapTempIdToDbId)
    {
        $bbData = $bbData ?? [];
        
        // Hapus BB yang hilang
        $existingIds = collect($bbData)->pluck('id')->filter()->toArray();
        $kasus->barangBukti()->whereNotIn('id', $existingIds)->delete();

        foreach ($bbData as $data) {
            $pemilikRef = $data['pemilik']; 
            $finalTersangkaId = null;

            // Resolusi Pemilik (Apakah milik kasus atau milik tersangka)
            if ($pemilikRef !== 'kasus') {
                if (isset($mapTempIdToDbId[$pemilikRef])) {
                    $finalTersangkaId = $mapTempIdToDbId[$pemilikRef];
                } elseif (is_numeric($pemilikRef)) {
                    $finalTersangkaId = $pemilikRef; // Fallback untuk ID lama
                }
            }

            $payload = [
                'berantas_ungkap_tersangka_id' => $finalTersangkaId,
                'jenis_barang_bukti'  => $data['jenis'],
                'jumlah_barang_bukti' => $data['jumlah'],
                'satuan_barang_bukti' => $data['satuan'],
            ];

            if (isset($data['id']) && $data['id']) {
                $kasus->barangBukti()->where('id', $data['id'])->update($payload);
            } else {
                $kasus->barangBukti()->create($payload);
            }
        }
    }

    private function handleDokumentasi($kasus, $folders)
    {
        if (empty($folders)) return;
        foreach ($folders as $folder) {
            $tempFile = TemporaryFile::where('folder', $folder)->first();
            if ($tempFile) {
                $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;
                $destPath = 'lampiran/berantas_ungkap/' . date('Y') . '/' . $tempFile->filename;
                if (Storage::exists($sourcePath)) {
                    Storage::disk('public')->put($destPath, Storage::readStream($sourcePath));
                    $kasus->dokumentasi()->create([
                        'nama_file_asli' => $tempFile->filename,
                        'path_file' => $destPath,
                        'tipe_file' => Storage::mimeType($sourcePath),
                        'ukuran_file' => Storage::size($sourcePath)
                    ]);
                    Storage::deleteDirectory('public/tmp/' . $folder);
                    $tempFile->delete();
                }
            }
        }
    }

    private function deleteDokumentasi($kasus, $fileIds)
    {
        if(empty($fileIds)) return;
        foreach ($fileIds as $fileId) {
            $doc = $kasus->dokumentasi()->find($fileId);
            if ($doc) {
                if (Storage::disk('public')->exists($doc->path_file)) Storage::disk('public')->delete($doc->path_file);
                $doc->delete();
            }
        }
    }
}