<?php

use App\Http\Controllers\Admin\PegawaiController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Berantas\MapController;
use App\Http\Controllers\Berantas\NarkotikaController;
use App\Http\Controllers\Berantas\PetaUngkapKasusController;
use App\Http\Controllers\Berantas\TatController;
use App\Http\Controllers\Berantas\UngkapKasusController;
use App\Http\Controllers\Berantas\RegisterBarangBuktiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\P2m\SosialisasiController;
use App\Http\Controllers\P2m\UpacaraController;
use App\Http\Controllers\P2m\KieController;
use App\Http\Controllers\P2m\LingkunganController;
use App\Models\P2mSosialisasi;
use App\Http\Controllers\P2m\CfdController;
use App\Http\Controllers\P2m\PelatihanController;
use App\Http\Controllers\P2m\DesaKelurahanBersinarController;
use App\Models\p2mcfd;

use App\Http\Controllers\P2m\ElektronikController;
use App\Http\Controllers\P2m\KeluargaController;
use App\Http\Controllers\P2m\LingkunganBersinarController;
use App\Http\Controllers\P2m\MediaNonElektronikController;
use App\Http\Controllers\P2m\NonElektronikController;
use App\Models\p2mElektronik;

use App\Http\Controllers\P2m\OnlineController;
use App\Http\Controllers\P2m\SafariReligiController;
use App\Http\Controllers\P2m\TesUrineController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Rehab\RehabLaporanController;
use App\Http\Controllers\TemporaryFileController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\P2m\AsistensiRelawanController;
use App\Http\Controllers\P2m\IkanController;
use App\Http\Controllers\P2m\InformasiEdukasiController;
use App\Http\Controllers\P2m\MonevController;
use App\Http\Controllers\P2m\PemetaanSdmSdaController;
use App\Livewire\Dashboard\Index;
use App\Models\Dokumen;
use App\Models\DokumentasiKegiatan;
use App\Models\p2mOnline;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// --- GUEST ROUTES (Login & Forgot Password) ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');
    
    // Forgot Password Routes
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update');
});

// --- AUTHENTICATED ROUTES ---
Route::middleware('auth')->group(function() {
    
    // Dashboard & Umum
    // Route::get('/', function () { return view('welcome'); })->name('dashboard');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

    // API Data (Satu endpoint fleksibel untuk semua bidang)
    Route::get('/api/dashboard/global', [DashboardController::class, 'getGlobalData'])->name('api.dashboard.global');
    Route::get('/api/dashboard/chart', [DashboardController::class, 'getChartData'])->name('api.dashboard.chart');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Group Route Dokumen
    Route::prefix('dokumen')->name('dokumen.')->group(function () {
        
        // 1. Download Satuan (GET)
        Route::get('/{id}/download', [DokumenController::class, 'download'])
            ->name('download');

        // 2. Download ZIP Selected (POST)
        // Menggunakan POST karena array ID bisa jadi sangat banyak
        Route::post('/zip/selected', [DokumenController::class, 'downloadZipSelected'])
            ->name('zip.selected');
    });

    Route::post('/upload-temp', [App\Http\Controllers\TemporaryFileController::class, 'upload'])->name('upload.temp');
    Route::delete('/revert-temp', [App\Http\Controllers\TemporaryFileController::class, 'revert'])->name('revert.temp');
    Route::get('/load-temp', [App\Http\Controllers\TemporaryFileController::class, 'load'])->name('load.temp');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update'); 
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::get('/profile/verify-email/{token}', [ProfileController::class, 'verifyNewEmail'])->name('profile.email.verify');
    Route::delete('/profile/cancel-email', [ProfileController::class, 'cancelEmailChange'])->name('profile.email.cancel');
    Route::post('/profile/resend-email', [ProfileController::class, 'resendEmailVerification'])->name('profile.email.resend');

    // =========================================================================
    // 1. MANAJEMEN USER & PEGAWAI (Khusus Admin Pusat, Admin Satker, & Admin Bidang)
    // =========================================================================
    // Catatan: Admin Bidang (P2M/Berantas) juga butuh akses 'users' untuk manage operator mereka sendiri.
    // Jadi kita tambahkan role mereka di sini.
    Route::middleware(['role:admin,admin_satker,admin_p2m,admin_berantas,admin_rehab'])->group(function() {
        Route::prefix('admin')->name('admin.')->group(function() {
            
            // User Controller (Create Operator, Reset Password, List User)
            Route::resource('users', UserController::class);
            Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset_password');

            // Semua admin (termasuk bidang) boleh melihat daftar pegawai di satkernya
            Route::get('pegawai', [PegawaiController::class, 'index'])->name('pegawai.index');
        });
    });

    // Hanya Admin Pusat dan Admin Satker yang boleh menambah/mengedit fisik pegawai
    Route::middleware(['role:admin,admin_satker'])->group(function() {
        Route::prefix('admin')->name('admin.')->group(function() {
            
            // Resource Pegawai (Kecuali Index & Show yang sudah ada di atas)
            // Ini menangani: create, store, edit, update, destroy
            Route::resource('pegawai', PegawaiController::class)->except(['index', 'show']);

        });
    });

    // =========================================================================
    // 2. MODUL P2M (Pencegahan)
    // =========================================================================
    Route::prefix('p2m')->name('p2m.')->group(function() {
        
        // Halaman Utama P2M
        Route::get('/', function() { return view('p2m.index'); })->name("index");
        
        // A. READ/VIEW ACCESS (Admin Pusat, Admin Satker, Admin P2M, Operator P2M, Operator Satker)
        // Tambahkan 'admin_p2m' disini
        Route::middleware(['role:admin,admin_satker,admin_p2m,operator_satker,operator_p2m'])->group(function() {
            // Informasi & Edukasi
            Route::get('/informasi-edukasi', [InformasiEdukasiController::class, 'index'])->name("informasi-edukasi.index");
            Route::get('/informasi-edukasi/export', [InformasiEdukasiController::class, 'export'])->name('informasi-edukasi.export');

            // Sosialisasi
            Route::get('/sosialisasi', [SosialisasiController::class, 'index'])->name("sosialisasi.index");
            Route::get('/sosialisasi/export', [SosialisasiController::class, 'export'])->name('sosialisasi.export');
            // Upacara
            Route::get('/upacara', [UpacaraController::class, 'index'])->name("upacara.index");
            Route::get('/upacara/export', [UpacaraController::class, 'export'])->name('upacara.export');
            // KIE
            Route::get('/kie', [KieController::class, 'index'])->name("kie.index");
            Route::get('/kie/export', [KieController::class, 'export'])->name('kie.export');
            // Lingkungan Bersinar
            Route::get('/lingkungan-bersinar', [LingkunganBersinarController::class, 'index'])->name("lingkungan-bersinar.index");
            Route::get('/lingkungan-bersinar/export', [LingkunganBersinarController::class, 'export'])->name('lingkungan-bersinar.export');
            // CFD
            Route::get('/cfd', [CfdController::class, 'index'])->name("cfd.index");
            Route::get('/cfd/export', [CfdController::class, 'export'])->name('cfd.export');
            // Elektronik
            Route::get('/elektronik', [ElektronikController::class, 'index'])->name("elektronik.index");
            Route::get('/elektronik/export', [ElektronikController::class, 'export'])->name('elektronik.export');
            // Non Elektronik
            Route::get('/non-elektronik', [NonElektronikController::class, 'index'])->name("non-elektronik.index");
            Route::get('/non-elektronik/export', [NonElektronikController::class, 'export'])->name('non-elektronik.export');
            // Online
            Route::get('/online', [OnlineController::class, 'index'])->name("online.index");
            Route::get('/online/export', [OnlineController::class, 'export'])->name('online.export');
            // Tes Urine
            Route::get('/tes-urine', [TesUrineController::class, 'index'])->name("tes-urine.index");
            Route::get('/tes-urine/export', [TesUrineController::class, 'export'])->name('tes-urine.export');
            // Desa Bersinar
            Route::get('/desa-kelurahan-bersinar', [DesaKelurahanBersinarController::class, 'index'])->name('desa-kelurahan-bersinar.index');
            Route::get('/desa-kelurahan-bersinar/export', [DesaKelurahanBersinarController::class, 'export'])->name('desa-kelurahan-bersinar.export');
            // Safari Religi
            Route::get('/safari-religi', [SafariReligiController::class, 'index'])->name("safari-religi.index");
            Route::get('/safari-religi/export', [SafariReligiController::class, 'export'])->name('safari-religi.export');
            // IKAN
            Route::get('/ikan', [IkanController::class, 'index'])->name("ikan.index");
            Route::get('/ikan/export', [IkanController::class, 'export'])->name('ikan.export');
            // Asistensi Relawan
            Route::get('/asistensi-relawan', [AsistensiRelawanController::class, 'index'])->name("asistensi-relawan.index");
            Route::get('/asistensi-relawan/export', [AsistensiRelawanController::class, 'export'])->name('asistensi-relawan.export');
            // Safari Religi
            Route::get('/pelatihan', [PelatihanController::class, 'index'])->name("pelatihan.index");
            Route::get('/pelatihan/export', [PelatihanController::class, 'export'])->name('pelatihan.export');
             // Safari Religi
            Route::get('/keluarga', [KeluargaController::class, 'index'])->name("keluarga.index");
            Route::get('/keluarga/export', [KeluargaController::class, 'export'])->name('keluarga.export');
            // Monev
            Route::get('/monev', [MonevController::class, 'index'])->name("monev.index");
            Route::get('/monev/export', [MonevController::class, 'export'])->name('monev.export');
            // Pemetaan SDM & SDA
            Route::get('/pemetaan-sdm-sda', [PemetaanSdmSdaController::class, 'index'])->name("pemetaan-sdm-sda.index");
            Route::get('/pemetaan-sdm-sda/export', [PemetaanSdmSdaController::class, 'export'])->name('pemetaan-sdm-sda.export');
        });

        // B. WRITE/CREATE/EDIT ACCESS (Hanya Operator P2M & Operator Satker)
        // Admin P2M biasanya hanya memantau, tapi jika boleh input, tambahkan 'admin_p2m' disini
        Route::middleware(['role:operator_satker,operator_p2m'])->group(function() {
            
            // Informasi & Edukasi CRUD
            Route::resource('informasi-edukasi', InformasiEdukasiController::class)->except(['index', 'show']);
            // Sosialisasi CRUD
            Route::resource('sosialisasi', SosialisasiController::class)->except(['index', 'show']);
            // Upacara CRUD
            Route::resource('upacara', UpacaraController::class)->except(['index', 'show']);
            // KIE CRUD
            Route::resource('kie', KieController::class)->except(['index', 'show']);
            // Lingkungan Bersinar CRUD
            Route::resource('lingkungan-bersinar', LingkunganBersinarController::class)->except(['index', 'show']);
            // CFD CRUD
            Route::resource('cfd', CfdController::class)->except(['index', 'show']);
            // Elektronik CRUD
            Route::resource('elektronik', ElektronikController::class)->except(['index', 'show']);
            // Non Elektronik CRUD
            Route::resource('non-elektronik', NonElektronikController::class)->except(['index', 'show']);
            // Online CRUD
            Route::resource('online', OnlineController::class)->except(['index', 'show']);
            // Tes Urine CRUD
            Route::resource('tes-urine', TesUrineController::class)->except(['index', 'show']);
            // Desa Bersinar CRUD
            Route::resource('desa-kelurahan-bersinar', DesaKelurahanBersinarController::class)->except(['index', 'show']);
            // Safari Religi CRUD
            Route::resource('safari-religi', SafariReligiController::class)->except(['index', 'show']);
            // IKAN
            Route::resource('ikan', IkanController::class)->except(['index', 'show']);
            // Asistensi Relawan
            Route::resource('asistensi-relawan', AsistensiRelawanController::class)->except(['index', 'show']);
            // Pelatihan CRUD
            Route::resource('pelatihan', PelatihanController::class)->except(['index', 'show']);
             // Ketahanan Keluarga CRUD
            Route::resource('keluarga', KeluargaController::class)->except(['index', 'show']);
            // Monev CRUD
            Route::resource('monev', MonevController::class)->except(['index', 'show']);
            // Pemetaan SDM & SDA CRUD
            Route::resource('pemetaan-sdm-sda', PemetaanSdmSdaController::class)->except(['index', 'show']);
        });
    });

    // =========================================================================
    // 3. MODUL BERANTAS (Pemberantasan)
    // =========================================================================
    Route::prefix('berantas')->name('berantas.')->group(function() {
        
        // A. READ/VIEW ACCESS (Tambahkan 'admin_berantas')
        Route::middleware(['role:admin,admin_satker,admin_berantas,operator_satker,operator_berantas'])->group(function() {
            // Ungkap Kasus
            Route::get('/ungkap-kasus', [UngkapKasusController::class, 'index'])->name("ungkap-kasus.index");
            Route::get('/ungkap-kasus/export', [UngkapKasusController::class, 'export'])->name('ungkap-kasus.export');
            // TAT
            Route::get('/tat', [TatController::class, 'index'])->name("tat.index");
            Route::get('/tat/export', [TatController::class, 'export'])->name('tat.export');
            // Narkotika
            Route::get('/narkotika', [NarkotikaController::class, 'index'])->name('narkotika.index');
            // Barang Bukti
            Route::get('/register-barang-bukti', [RegisterBarangBuktiController::class, 'index'])->name('register-barang-bukti.index');
            Route::get('/register-barang-bukti/export', [RegisterBarangBuktiController::class, 'export'])->name('register-barang-bukti.export');

            // Route Peta Ungkap Kasus
            Route::get('peta-ungkap-kasus', [PetaUngkapKasusController::class, 'index'])->name('peta-ungkap-kasus.index');
            Route::get('peta-ungkap-kasus/data', [PetaUngkapKasusController::class, 'data'])->name('peta-ungkap-kasus.data');
            Route::get('peta-ungkap-kasus/detail/{id}', [PetaUngkapKasusController::class, 'show'])->name('peta-ungkap-kasus.show');

            // // --- PETA SEBARAN (GIS) ---
        
            // // 1. Peta Ungkap Kasus (Crime Map)
            // Route::get('/peta/ungkap-kasus', [MapController::class, 'ungkapKasusIndex'])
            //     ->name('peta.ungkap-kasus.index');
            // Route::get('/peta/ungkap-kasus/data', [MapController::class, 'getUngkapKasusData'])
            //     ->name('peta.ungkap-kasus.data');

            // // 2. Peta Register Barang Bukti (Evidence Map)
            // Route::get('/peta/register-bb', [MapController::class, 'registerBbIndex'])
            //     ->name('peta.register-bb.index');
            // Route::get('/peta/register-bb/data', [MapController::class, 'getRegisterBbData'])
            //     ->name('peta.register-bb.data');
        });

        // B. WRITE ACCESS (Hanya Operator Berantas & Operator Satker)
        Route::middleware(['role:operator_satker,operator_berantas'])->group(function() {
            Route::resource('ungkap-kasus', UngkapKasusController::class)->except(['index', 'show']);
            Route::resource('tat', TatController::class)->except(['index', 'show']);
            // Narkotika & BB (Biasanya pakai resource juga atau manual seperti kode lama)
            Route::post('/narkotika', [NarkotikaController::class, 'store'])->name('narkotika.store');
            Route::put('/narkotika/{id}', [NarkotikaController::class, 'update'])->name('narkotika.update');
            Route::delete('/narkotika/{id}', [NarkotikaController::class, 'destroy'])->name('narkotika.destroy');
            
            Route::resource('register-barang-bukti', RegisterBarangBuktiController::class)->except(['index', 'show']);
        });

    });


    // =========================================================================
    // 4. MODUL REHABILITASI
    // =========================================================================
    Route::prefix('rehab')->name('rehab.')->group(function() {
        
        // A. READ/VIEW ACCESS
        Route::middleware(['role:admin,admin_satker,admin_rehab,operator_satker,operator_rehab'])->group(function() {
            Route::get('/laporan', [RehabLaporanController::class, 'index'])->name('laporan.index');
            Route::get('/laporan/export', [RehabLaporanController::class, 'export'])->name('laporan.export');
        });

        // B. WRITE ACCESS (Target & Laporan Harian)
        Route::middleware(['role:operator_satker,operator_rehab,admin,admin_satker'])->group(function() {
            
            // Route Simpan/Update Target
            Route::post('/laporan/target', [RehabLaporanController::class, 'storeTarget'])->name('laporan.store_target');
            
            // Route Hapus Target (BARU)
            Route::delete('/laporan/target/{id}', [RehabLaporanController::class, 'destroyTarget'])->name('laporan.destroy_target');

            // Resource Laporan Harian
            Route::resource('laporan', RehabLaporanController::class)->except(['index', 'show']);
        });
    });

});