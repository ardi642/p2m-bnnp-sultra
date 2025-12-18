<?php

namespace App\Http\Controllers\P2m;

use App\Http\Controllers\Controller;
use App\Models\P2mSosialisasi;
use App\Models\SatuanKerja;
use App\Models\Pegawai;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Exports\SosialisasiExport; // Import Export Class
use App\Helpers\SearchHelper;
use App\Models\DokumentasiKegiatan;
use App\Models\TemporaryFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel; // Import Facade Excel
use Illuminate\Support\Str;

class SosialisasiController extends Controller
{
    // 1. FUNGSI KHUSUS UNTUK BUILD QUERY (Re-usable)
    private function getFilteredQuery(Request $request)
    {

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $activeYears = $request->filled('tahun') ? $request->tahun : [date('Y')];
        
        $query = P2mSosialisasi::with('pegawai.satuanKerja', 'satuanKerja');

        // --- FILTER SAMA PERSIS SEPERTI SEBELUMNYA ---

        if ($user->hasRole('admin')) {
            if ($request->filled('satuan_kerja_id')) {
                $query->whereIn('satuan_kerja_id', $request->satuan_kerja_id);
            }
        }
        else {
            $satkerId = $user->getSatkerId();
            $query->where('satuan_kerja_id', $satkerId);
        }

        if ($request->filled('bulan')) {
            $query->where(function($q) use ($request) {
                foreach ($request->bulan as $b) {
                    $q->orWhereMonth('tanggal_pelaksanaan', $b);
                }
            });
        }
        $query->where(function($q) use ($activeYears) {
            foreach ($activeYears as $y) {
                $q->orWhereYear('tanggal_pelaksanaan', $y);
            }
        });
        if ($request->filled('anggaran_pelaksanaan')) {
            $query->whereIn('anggaran_pelaksanaan', $request->anggaran_pelaksanaan);
        }
        if ($request->filled('sasaran_kegiatan')) {
            $query->whereIn('sasaran_kegiatan', $request->sasaran_kegiatan);
        }
        
        // Filter Pegawai
        if ($request->filled('pegawai_nips')) {
            $nips = $request->pegawai_nips;
            $logic = $request->input('pegawai_logic', 'OR');
            if ($logic === 'AND') {
                foreach ($nips as $nip) {
                    $query->whereHas('pegawai', function($q) use ($nip) {
                        $q->where('pegawai.nip', $nip);
                    });
                }
            } else {
                $query->whereHas('pegawai', function($q) use ($nips) {
                    $q->whereIn('pegawai.nip', $nips);
                });
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $searchDate = SearchHelper::translateDateInput($search);
            $query->where(function($q) use ($search, $searchDate) {
                // 1. Pencarian Kolom Teks Utama
                $q->where('nama_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('tempat_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('sasaran_kegiatan', 'LIKE', "%{$search}%")
                    ->orWhere('anggaran_pelaksanaan', 'LIKE', "%{$search}%") // Cari DIPA/NON DIPA

                    // 2. Pencarian Angka (Jumlah Peserta)
                    ->orWhere('jumlah_peserta', 'LIKE', "%{$search}%")

                    // 3. Pencarian Relasi Satker
                    ->orWhereHas('satuanKerja', function($subQ) use ($search) {
                        $subQ->where('satuan_kerja', 'LIKE', "%{$search}%");
                    })

                    // 4. Pencarian Relasi Pegawai (Cari nama pegawai yang terlibat)
                    ->orWhereHas('pegawai', function($subQ) use ($search) {
                        $subQ->where('nama', 'LIKE', "%{$search}%");
                    });

                    // 3. Tanggal Pelaksanaan (Format: Kamis, 04 September 2025)
                    // %W=Hari, %d=Tgl, %M=Bulan Panjang, %Y=Tahun
                    $q->orWhereRaw("LOWER(DATE_FORMAT(tanggal_pelaksanaan, '%W, %d %M %Y')) LIKE ?", ["%{$searchDate}%"]);
                    
                    // Variasi tanpa hari (04 September 2025)
                    // $q->orWhereRaw("LOWER(DATE_FORMAT(tanggal_pelaksanaan, '%d %M %Y')) LIKE ?", ["%{$searchDate}%"]);

                    // 4. Dibuat Pada (Format: 09 Dec 2025 02:00)
                    // %b=Bulan Pendek (Dec), %H:%i=Jam:Menit
                    $q->orWhereRaw("LOWER(DATE_FORMAT(created_at, '%d %b %Y %H:%i')) LIKE ?", ["%{$searchDate}%"]);

                    // Variasi tanpa jam (09 Dec 2025)
                    // $q->orWhereRaw("LOWER(DATE_FORMAT(created_at, '%d %b %Y')) LIKE ?", ["%{$searchDate}%"]);
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowSort = ['anggaran_pelaksanaan', 'nama_kegiatan', 'sasaran_kegiatan', 'tanggal_pelaksanaan', 'tempat_kegiatan', 'jumlah_peserta', 'created_at', 'satuan_kerja'];

        if (in_array($sortBy, $allowSort)) {
            if ($sortBy === 'satuan_kerja') {
                $query->join('satuan_kerja', 'p2m_sosialisasi.satuan_kerja_id', '=', 'satuan_kerja.id')
                        ->orderBy('satuan_kerja.satuan_kerja', $sortOrder)
                        ->select('p2m_sosialisasi.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
        } else {
            $query->latest();
        }

        return $query;
    }

    public function index(Request $request): View {
        // Data Master

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $pegawais = Pegawai::orderBy('nama', 'asc')->get(['nip', 'nama']);
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
        }
        else {
            $satkerId = $user->getSatkerId();
            $pegawais = Pegawai::where('satuan_kerja_id', $satkerId)
                                ->orderBy('nama', 'asc')
                                ->get(['nip', 'nama']);
            $satuanKerjas = [];
        }

        $yearQuery = P2mSosialisasi::selectRaw('YEAR(tanggal_pelaksanaan) as year');

        if ($user->isOperator()) {
            $yearQuery->where('satuan_kerja_id', $user->getSatkerId());
        }

        $years = $yearQuery->distinct()->orderBy('year', 'desc')->pluck('year');

        $query = $this->getFilteredQuery($request);

        // Kita tambahkan 'dokumentasi' manual disini.
        // Jadi saat Export Excel (yang tidak lewat fungsi index ini), dokumentasi TIDAK dimuat.
        // Tapi saat buka halaman web (lewat fungsi index ini), dokumentasi DIMUAT.
        $query->with('dokumentasi');

        $perPage = $request->input('per_page', 10);
        
        // Validasi keamanan (agar user tidak iseng input angka 1000000 bikin server down)
        // Hanya izinkan angka: 10, 25, 50, 100
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }
        $sosialisasis = $query->paginate($perPage)->withQueryString();

        // --- OPTIMASI LOOKUP (Agar View Cepat) ---
        // Ambil semua nama satker menjadi array [id => nama]
        // Contoh: [1 => 'BNN Jakarta', 2 => 'BNN Bali']
        $satkerLookup = SatuanKerja::pluck('satuan_kerja', 'id')->toArray();
                        
        return view('p2m.sosialisasi.index', compact('sosialisasis', 'satuanKerjas', 'years', 'pegawais', 'user', 'satkerLookup'));
    }

    // 3. METHOD EXPORT (DOWNLOAD EXCEL)
    public function export(Request $request) 
    {
        // Panggil fungsi query yang SAMA PERSIS dengan index
        // Bedanya: Kita tidak pakai paginate(), tapi langsung lempar ke Class Export
        $query = $this->getFilteredQuery($request);

        return Excel::download(new SosialisasiExport($query), 'Laporan_P2M_Sosialisasi.xlsx');
    }

    public function create(): View {

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
            $pegawais = Pegawai::with('satuanKerja')->orderBy('nama', 'asc')->get();
        }
        else if ($user->isOperator()){
            $satuanKerjas = [];
            $satkerId = $user->getSatkerId();
            $pegawais = Pegawai::with('satuanKerja')
                ->where('satuan_kerja_id', $satkerId)
                ->orderBy('nama', 'asc')
                ->get();
        }

        return view('p2m.sosialisasi.create', compact('satuanKerjas', 'pegawais'));
    }

    public function store(Request $request) {
        
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // SIAPKAN RULES VALIDASI
        $rules = [
            'anggaran_pelaksanaan' => 'required',
            'nama_kegiatan'        => 'required',
            'sasaran_kegiatan'     => 'required',
            'tanggal_pelaksanaan'  => 'required|date',
            'tempat_kegiatan'      => 'required',
            'jumlah_peserta'       => 'required|numeric',
            
            // Validasi Array Pegawai
            'pegawai_nips'   => 'required|array',
            'pegawai_nips.*' => 'exists:pegawai,nip',

            // Validasi Dokumentasi (Minimal 1 file wajib)
            'dokumentasi'   => 'nullable|array',
            'dokumentasi.*' => 'required',
        ];

        // Jika Admin, wajib pilih Satuan Kerja
        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required';
        }

        // EKSEKUSI VALIDASI
        $validasi = $request->validate($rules);

        $filesMoved = []; // Array untuk melacak file yang berhasil dipindah (untuk rollback)

        DB::beginTransaction(); // MULAI TRANSAKSI

        try {
            // Pisahkan data input
            $dataKegiatan = collect($validasi)->except('dokumentasi', 'pegawai_nips')->toArray();
            $pegawaiNips  = $validasi['pegawai_nips'];

            // Jika Operator, set Satker ID otomatis sesuai user login
            if ($user->isOperator()) {
                $dataKegiatan['satuan_kerja_id'] = $user->getSatkerId();
            }

            // SIMPAN DATA UTAMA
            $kegiatan = P2mSosialisasi::create($dataKegiatan);

            // -----------------------------------------------------------
            // SIMPAN RELASI PEGAWAI DENGAN ATTACH (MODIFIKASI DISINI)
            // -----------------------------------------------------------
            
            // Ambil detail pegawai dari Database berdasarkan NIP yang dipilih
            // Tujuannya: Untuk mengetahui 'satuan_kerja_id' mereka SAAT INI
            $listPegawai = Pegawai::whereIn('nip', $pegawaiNips)->get();

            // Siapkan Array untuk Attach
            // Format yang dibutuhkan attach agar bisa simpan kolom tambahan:
            // [ 
            //    'NIP_A' => ['saved_satuan_kerja_id' => 1], 
            //    'NIP_B' => ['saved_satuan_kerja_id' => 2] 
            // ]
            $attachData = [];
            foreach ($listPegawai as $pgw) {
                $attachData[$pgw->nip] = [
                    'saved_satuan_kerja_id' => $pgw->satuan_kerja_id
                ];
            }

            // SIMPAN RELASI PEGAWAI
            $kegiatan->pegawai()->attach($pegawaiNips);

            // PROSES PINDAH FILE (Dari Temp ke Storage Public)
            if ($request->filled('dokumentasi')) {
                $tempFolders = $request->input('dokumentasi');

                foreach ($tempFolders as $folder) {
                    $tempFile = TemporaryFile::where('folder', $folder)->first();

                    if ($tempFile) {
                        // A. Path SUMBER (Di folder tmp)
                        $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;

                        // B. SOLUSI ERROR MIMETYPE: 
                        // Ambil MimeType & Size dari file SUMBER (sebelum dipindah/copy)
                        // Ini lebih stabil karena file pasti ada di default disk
                        $mimeType = Storage::mimeType($sourcePath); 
                        $size = Storage::size($sourcePath);

                        // C. SOLUSI NAMA FILE (Anti Bentrok)
                        // Format: WAKTU_IDUNIK_NAMASLUG.EKSTENSI
                        // Contoh: 17028392_65a4b2c_laporan-kegiatan.pdf
                        $ext = pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                        $nameOnly = pathinfo($tempFile->filename, PATHINFO_FILENAME);
                        
                        // uniqid() menambahkan string unik berbasis mikrodetik
                        // Str::slug() membersihkan nama file dari spasi/karakter aneh
                        $cleanFileName = time() . '_' . uniqid() . '_' . Str::slug($nameOnly) . '.' . $ext;

                        // D. Path TUJUAN (Di Disk Public)
                        $destinationPath = 'dokumentasi/' . date('Y') . '/' . $cleanFileName;

                        if (Storage::exists($sourcePath)) {
                            
                            // Copy File antar Disk
                            Storage::disk('public')->put($destinationPath, Storage::readStream($sourcePath));
                            
                            $filesMoved[] = $destinationPath;

                            // Simpan ke DB
                            $kegiatan->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename, // Nama asli tetap disimpan untuk display user
                                'path_file'      => $destinationPath,    
                                'tipe_file'      => $mimeType,           // Pakai variabel yang sudah diambil di atas
                                'ukuran_file'    => $size,               // Pakai variabel size di atas
                            ]);

                            // Hapus Temp
                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            // KOMIT TRANSAKSI (Simpan Permanen)
            DB::commit(); 

        } catch (\Exception $e) {
            // JIKA GAGAL: BATALKAN SEMUA
            DB::rollBack();

            // Hapus file fisik yang terlanjur tercopy ke folder tujuan
            foreach ($filesMoved as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            return back()
                ->with('error', 'store') // Trigger error store
                ->with('message', 'Gagal menyimpan data: ' . $e->getMessage())
                ->withInput();
        }

        // REDIRECT SUKSES
        return redirect()->route('p2m.sosialisasi.index')
            ->with('success', 'store') // Trigger SweetAlert
            ->with('message', 'Berhasil menambahkan data kegiatan');
    }

    public function edit($id): View 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Ambil Data Kegiatan beserta relasi Pegawai (untuk pre-fill input)
        $kegiatan = P2mSosialisasi::with('pegawai')->findOrFail($id);

        // Proteksi Hak Akses
        // Jika Operator mencoba edit data milik Satker lain -> 403 Forbidden
        if ($user->isOperator() && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403, 'Anda tidak berhak mengubah data Satuan Kerja lain.');
        }

        // Siapkan Data Master (Logic sama seperti Create)
        if ($user->isAdmin()) {
            $satuanKerjas = SatuanKerja::orderBy('satuan_kerja', 'asc')->get();
            $pegawais = Pegawai::orderBy('nama', 'asc')->get();
        } 
        else {
            $satuanKerjas = []; 
            $satkerId = $user->getSatkerId();

            // Ambil pegawai yang AKTIF di Satker saat ini
            $pegawaiAktif = Pegawai::where('satuan_kerja_id', $satkerId)->get();

            // Ambil pegawai yang SUDAH MENEMPEL di kegiatan ini (Termasuk yg sudah mutasi)
            $pegawaiExisting = $kegiatan->pegawai;

            // Gabungkan keduanya, lalu hilangkan duplikat NIP
            // Ini memastikan Budi (yg sudah pindah satker) tetap muncul di list
            $pegawais = $pegawaiAktif->merge($pegawaiExisting)->unique('nip')->sortBy('nama');
        }

        // Ambil Array NIP Pegawai yang sudah terpilih sebelumnya
        // Ini penting untuk mengisi Tom Select nanti
        $selectedPegawaiNips = $kegiatan->pegawai->pluck('nip')->toArray();

        return view('p2m.sosialisasi.edit', compact('kegiatan', 'satuanKerjas', 'pegawais', 'selectedPegawaiNips'));
    }

    public function update(Request $request, $id) 
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $kegiatan = P2mSosialisasi::findOrFail($id);

        // Proteksi Update
        if ($user->isOperator() && $kegiatan->satuan_kerja_id !== $user->getSatkerId()) {
            abort(403);
        }

        // 1. Validasi
        $rules = [
            'anggaran_pelaksanaan' => 'required',
            'nama_kegiatan'        => 'required',
            'sasaran_kegiatan'     => 'required',
            'tanggal_pelaksanaan'  => 'required|date',
            'tempat_kegiatan'      => 'required',
            'jumlah_peserta'       => 'required|numeric',
            'pegawai_nips'         => 'required|array',
            'pegawai_nips.*'       => 'exists:pegawai,nip',

            // Array ID file lama yang mau dihapus
            'delete_files'   => 'nullable|array', 
            'delete_files.*' => 'exists:dokumentasi_kegiatan,id',
            
            // Array FilePond untuk file baru (Nullable karena edit tidak wajib upload baru)
            'dokumentasi'   => 'nullable|array',
        ];

        if ($user->isAdmin()) {
            $rules['satuan_kerja_id'] = 'required';
        }

        $validasi = $request->validate($rules);

        // Variabel pelacak
        $newFilesMoved = []; // File baru yang sukses dipindah (untuk rollback)
        $filesToDelete = []; // File lama yang mau dihapus fisiknya (setelah commit)

        DB::beginTransaction();

        try {
            $pegawaiNips = $validasi['pegawai_nips'];
            $dataUpdate = collect($validasi)->except(['dokumentasi', 'pegawai_nips', 'delete_files'])->toArray();

            if ($user->isOperator()) {
                unset($dataUpdate['satuan_kerja_id']); 
            }

            // Update Data Utama
            $kegiatan->update($dataUpdate);

            // -----------------------------------------------------------
            // LOGIKA UPDATE PIVOT (HISTORY PRESERVATION)
            // -----------------------------------------------------------

            // Ambil Data Pivot LAMA (Untuk melihat history satker sebelumnya)
            // Kita ambil kolom 'pegawai_nip' dan 'saved_satuan_kerja_id'
            $oldPivotData = DB::table('pegawai_p2m_sosialisasi')
                                ->where('p2m_sosialisasi_id', $id)
                                ->get()
                                ->keyBy('pegawai_nip'); // Index array berdasarkan NIP

            // Ambil Data Master Pegawai (Untuk pegawai BARU yg ditambahkan)
            $masterPegawais = Pegawai::whereIn('nip', $pegawaiNips)->get()->keyBy('nip');

            $syncData = [];

            // Loop NIP yang disubmit dari Form
            foreach ($pegawaiNips as $nip) {
                
                // CEK: Apakah pegawai ini adalah "Orang Lama" di kegiatan ini?
                if (isset($oldPivotData[$nip]) && $oldPivotData[$nip]->saved_satuan_kerja_id) {
                    // KASUS 1: PEGAWAI LAMA
                    // Pertahankan ID Satker dari history lama.
                    // Jangan ambil dari master pegawai (karena mungkin dia sudah mutasi).
                    $satkerToSave = $oldPivotData[$nip]->saved_satuan_kerja_id; 
                } else {
                    // KASUS 2: PEGAWAI BARU (atau data lama yg belum punya history)
                    // Ambil ID Satker dia saat ini dari master pegawai.
                    $satkerToSave = $masterPegawais[$nip]->satuan_kerja_id ?? null;
                }

                // Masukkan ke array sync
                $syncData[$nip] = [
                    'saved_satuan_kerja_id' => $satkerToSave
                ];
            }

            // Eksekusi Sync
            // Otomatis menghapus pegawai yang tidak dicentang, 
            // dan mengupdate/insert pegawai yang dicentang dengan Satker ID yang tepat.
            $kegiatan->pegawai()->sync($syncData);

            // -----------------------------------------------------------

            // A. PROSES HAPUS FILE LAMA (Hapus DB dulu, Fisik nanti)
            if ($request->has('delete_files')) {
                $filesToRemove = DokumentasiKegiatan::whereIn('id', $request->delete_files)->get();
                
                foreach ($filesToRemove as $file) {
                    // Simpan path fisik untuk dihapus nanti
                    $filesToDelete[] = $file->path_file; 
                    // Hapus record database
                    $file->delete();
                }
            }

            // B. PROSES UPLOAD FILE BARU
            if ($request->filled('dokumentasi')) {
                $tempFolders = $request->input('dokumentasi');

                foreach ($tempFolders as $folder) {
                    $tempFile = TemporaryFile::where('folder', $folder)->first();

                    if ($tempFile) {
                        // Path Sumber (Temp)
                        $sourcePath = 'public/tmp/' . $folder . '/' . $tempFile->filename;
                        
                        // Metadata File
                        $mimeType = Storage::mimeType($sourcePath); 
                        $size = Storage::size($sourcePath);
                        
                        // Generate Nama Unik
                        $ext = pathinfo($tempFile->filename, PATHINFO_EXTENSION);
                        $nameOnly = pathinfo($tempFile->filename, PATHINFO_FILENAME);
                        $cleanFileName = time() . '_' . uniqid() . '_' . Str::slug($nameOnly) . '.' . $ext;

                        // Path Tujuan (Disk Public)
                        // Folder: dokumentasi/TAHUN/file.pdf
                        $destinationPath = 'dokumentasi/' . date('Y') . '/' . $cleanFileName;

                        if (Storage::exists($sourcePath)) {
                            // Copy ke Disk Public
                            Storage::disk('public')->put($destinationPath, Storage::readStream($sourcePath));
                            $newFilesMoved[] = $destinationPath;

                            // Simpan ke DB
                            $kegiatan->dokumentasi()->create([
                                'nama_file_asli' => $tempFile->filename,
                                'path_file'      => $destinationPath, // Path bersih tanpa 'public/'
                                'tipe_file'      => $mimeType,
                                'ukuran_file'    => $size,
                            ]);

                            // Hapus Temp
                            Storage::deleteDirectory('public/tmp/' . $folder);
                            $tempFile->delete();
                        }
                    }
                }
            }

            // COMMIT TRANSAKSI
            DB::commit();

            // C. CLEANUP SETELAH SUKSES (Hapus fisik file lama)
            foreach ($filesToDelete as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            return redirect()->route('p2m.sosialisasi.index')
            ->with('success', 'update')
            ->with('message', 'Data berhasil diperbarui');

        } catch (\Exception $e) {
            // ROLLBACK JIKA GAGAL
            DB::rollBack();

            // Hapus file BARU yang terlanjur tercopy
            foreach ($newFilesMoved as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
            
            // Note: File lama TIDAK dihapus karena loop hapus fisik ada setelah commit.

            return back()
                ->with('error', 'update') // Trigger error update
                ->with('message', 'Gagal memperbarui data: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy($id) 
    {
        // 1. CARI DATA
        // Jangan pakai with('dokumentasi') disini agar hemat memori di awal
        $kegiatan = P2mSosialisasi::findOrFail($id);
        
        // 2. KUMPULKAN PATH FILE (EFISIENSI MEMORI TINGGI)
        // Menggunakan cursor() agar data diambil satu per satu (streaming), 
        // bukan dimuat sekaligus ke RAM. Sangat aman jika ada ribuan file.
        $filesToDelete = [];
        
        foreach ($kegiatan->dokumentasi()->cursor() as $doc) {
            $filesToDelete[] = $doc->path_file;
        }

        // 3. HAPUS DATABASE (TRANSAKSI ATOMIK)
        DB::beginTransaction();
        try {
            // Hapus Kegiatan
            // Berkat kode boot() di Model, ini otomatis menghapus data di DB:
            // - p2m_sosialisasi (HILANG)
            // - dokumentasi_kegiatan (HILANG)
            $kegiatan->delete(); 

            // KUNCI: Commit dulu! Pastikan DB bersih 100% baru sentuh file fisik.
            DB::commit(); 

        } catch (\Exception $e) {
            // JIKA DB GAGAL: Batalkan semua. File fisik jangan disentuh.
            DB::rollBack();
            return back()
                ->with('error', 'destroy') // Trigger error destroy
                ->with('message', 'Gagal menghapus data dari database: ' . $e->getMessage());
        }

        // 4. HAPUS FILE FISIK (POST-COMMIT ACTION)
        // Database sudah bersih. Sekarang kita bersihkan harddisk.
        // Jika tahap ini gagal, tidak masalah (hanya jadi file sampah), 
        // yang penting data di aplikasi sudah konsisten hilang.
        
        foreach ($filesToDelete as $path) {
            try {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            } catch (\Exception $e) {
                // Silent Fail: Biarkan saja jika file gagal dihapus (misal permission error).
            }
        }

        return redirect()->back()
            ->with('success', 'destroy')
            ->with('message', 'Data dan file berhasil dihapus.');
    }

}
