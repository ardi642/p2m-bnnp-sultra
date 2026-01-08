<?php

use App\Http\Controllers\Admin\PegawaiController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Berantas\TatController;
use App\Http\Controllers\Berantas\UngkapKasusController;
use App\Http\Controllers\P2m\SosialisasiController;
use App\Http\Controllers\P2m\UpacaraController;
use App\Http\Controllers\P2m\KieController;
use App\Http\Controllers\P2m\LingkunganController;
use App\Models\P2mSosialisasi;
use App\Http\Controllers\P2m\CfdController;
use App\Http\Controllers\P2m\DesaBersinarController;
use App\Models\p2mcfd;

use App\Http\Controllers\P2m\ElektronikController;
use App\Http\Controllers\P2m\LingkunganBersinarController;
use App\Http\Controllers\P2m\MediaNonElektronikController;
use App\Http\Controllers\P2m\NonElektronikController;
use App\Models\p2mElektronik;

use App\Http\Controllers\P2m\OnlineController;
use App\Http\Controllers\P2m\SafariReligiController;
use App\Http\Controllers\P2m\TesUrineController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TemporaryFileController;
use App\Models\DokumentasiKegiatan;
use App\Models\p2mOnline;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');

    // Tampilkan Form Lupa Password
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])
        ->name('password.request');

    // Proses Kirim Link ke Email
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->name('password.email');

    // Tampilkan Form Reset Password (Link dari Email)
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])
        ->name('password.reset');

    // Proses Update Password Baru
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])
        ->name('password.update');
});

Route::middleware('auth')->group(function() {
    Route::get('/', function () {
        return view('welcome');
    })->name('dashboard');

    Route::get('/dokumentasi/{id}/download', function ($id) {
        
        $file = DokumentasiKegiatan::findOrFail($id);

        if (!Storage::disk('public')->exists($file->path_file)) {
            abort(404, 'File fisik tidak ditemukan.');
        }

        $pathLengkap = Storage::disk('public')->path($file->path_file);

        // 2. Gunakan response()->download()
        // Ini lebih dikenali editor daripada Storage::download()
        return response()->download($pathLengkap, $file->nama_file_asli);

    })->name('dokumentasi.download'); // <--- NAMA YANG DISARANKAN

    // --- ROUTE UTILITY FILEPOND (UPLOAD SEMENTARA) ---
    // Ditaruh disini agar bisa diakses semua user yang login (Admin & Operator)
    Route::post('/upload-temp', [TemporaryFileController::class, 'upload'])->name('upload.temp');
    Route::delete('/revert-temp', [TemporaryFileController::class, 'revert'])->name('revert.temp');
    Route::get('/load-temp', [TemporaryFileController::class, 'load'])->name('load.temp');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route untuk update Biodata (Email, dll)
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update'); 
    // Route untuk ganti Password (yang sudah ada sebelumnya)
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Route klik dari email (Public/Guest boleh akses, atau Auth juga boleh)
    Route::get('/profile/verify-email/{token}', [ProfileController::class, 'verifyNewEmail'])->name('profile.email.verify');
    
    // Route tombol aksi di profil
    Route::delete('/profile/cancel-email', [ProfileController::class, 'cancelEmailChange'])->name('profile.email.cancel');
    Route::post('/profile/resend-email', [ProfileController::class, 'resendEmailVerification'])->name('profile.email.resend');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::middleware(['role:admin,admin_satker'])->group(function() {
        Route::prefix('admin')->name('admin.')->group(function() {
            Route::resource('users', UserController::class);
            
            // Route Khusus Reset Password
            Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])
                ->name('users.reset_password');

            Route::resource('pegawai', PegawaiController::class);
        });
    });

    Route::prefix('p2m')
    ->name('p2m.')
    ->group(function() {
        Route::get('/', function() {
            return view('p2m.index');
        })->name("index");
        
        Route::middleware(['role:admin,admin_satker,operator'])->group(function() {

            // P2M sosialisasi
            Route::get('/sosialisasi', [SosialisasiController::class, 'index'])->name("sosialisasi.index");
            Route::get('/sosialisasi/export', [SosialisasiController::class, 'export'])->name('sosialisasi.export');

            // P2M Upacara
            Route::get('/upacara', [UpacaraController::class, 'index'])->name("upacara.index");
            Route::get('/upacara/export', [UpacaraController::class, 'export'])->name('upacara.export');

            // P2M KIE Keliling
            Route::get('/kie', [KieController::class, 'index'])->name("kie.index");
            Route::get('/kie/export', [KieController::class, 'export'])->name('kie.export');

            // P2M lingkungan bersinar
            Route::get('/lingkungan-bersinar', [LingkunganBersinarController::class, 'index'])->name("lingkungan-bersinar.index");
            Route::get('/lingkungan-bersinar/export', [LingkunganBersinarController::class, 'export'])->name('lingkungan-bersinar.export');

            // P2M CFD (Car Free Day)
            Route::get('/cfd', [CfdController::class, 'index'])->name("cfd.index");
            Route::get('/cfd/export', [CfdController::class, 'export'])->name('cfd.export');

            // P2M Media Elektronik
            Route::get('/elektronik', [ElektronikController::class, 'index'])->name("elektronik.index");
            Route::get('/elektronik/export', [ElektronikController::class, 'export'])->name('elektronik.export');

            // P2M Non Elektronik
            Route::get('/non-elektronik/export', [NonElektronikController::class, 'export'])
            ->name('non-elektronik.export');
            Route::get('/non-elektronik', [NonElektronikController::class, 'index'])
            ->name("non-elektronik.index");

            // P2M online
            Route::get('/online', [OnlineController::class, 'index'])->name("online.index");
            Route::get('/online/export', [OnlineController::class, 'export'])->name('online.export');

            // P2M tes urine / deteksi dini
            Route::get('/tes-urine', [TesUrineController::class, 'index'])->name("tes_urine.index");
            Route::get('/tes-urine/export', [TesUrineController::class, 'export'])->name('tes_urine.export');

            // P2M desa bersinar
            Route::get('/desa-bersinar', [DesaBersinarController::class, 'index'])->name('desa-bersinar.index');
            Route::get('/desa-bersinar/export', [DesaBersinarController::class, 'export'])->name('desa-bersinar.export');
            
            // P2M safari religi
            Route::get('/safari-religi', [SafariReligiController::class, 'index'])->name("safari_religi.index");
            Route::get('/safari-religi/export', [SafariReligiController::class, 'export'])->name('safari_religi.export');

            // P2M Tes Urine
            Route::get('/tes-urine', [TesUrineController::class, 'index'])->name("tes-urine.index");
            Route::get('/tes-urine/export', [TesUrineController::class, 'export'])->name('tes-urine.export');

            // P2M Safari Religi
            Route::get('/safari-religi', [SafariReligiController::class, 'index'])->name("safari-religi.index");
            Route::get('/safari-religi/export', [SafariReligiController::class, 'export'])->name('safari-religi.export');
        });

        Route::middleware(['role:operator'])->group(function() {

            // P2M sosialisasi
            Route::get('/sosialisasi/create', [SosialisasiController::class, 'create'])->name("sosialisasi.create");
            Route::post('/sosialisasi', [SosialisasiController::class, 'store'])->name("sosialisasi.store");
            Route::get('/sosialisasi/{id}/edit', [SosialisasiController::class, 'edit'])->name('sosialisasi.edit');
            Route::put('/sosialisasi/{id}', [SosialisasiController::class, 'update'])->name('sosialisasi.update');
            Route::delete('/sosialisasi/{id}', [SosialisasiController::class, 'destroy'])->name("sosialisasi.destroy");

            Route::get('/upacara/create', [UpacaraController::class, 'create'])->name("upacara.create");
            Route::post('/upacara', [UpacaraController::class, 'store'])->name("upacara.store");
            Route::get('/upacara/{id}/edit', [UpacaraController::class, 'edit'])->name('upacara.edit');
            Route::put('/upacara/{id}', [UpacaraController::class, 'update'])->name('upacara.update');
            Route::delete('/upacara/{id}', [UpacaraController::class, 'destroy'])->name("upacara.destroy");
            
            // P2M KIE Keliling
            Route::get('/kie/create', [KieController::class, 'create'])->name("kie.create");
            Route::post('/kie', [KieController::class, 'store'])->name("kie.store");
            Route::get('/kie/{id}/edit', [KieController::class, 'edit'])->name('kie.edit');
            Route::put('/kie/{id}', [KieController::class, 'update'])->name('kie.update');
            Route::delete('/kie/{id}', [KieController::class, 'destroy'])->name("kie.destroy");

            // P2M Lingkungan Bersinar
            Route::get('/lingkungan-bersinar/create', [LingkunganBersinarController::class, 'create'])->name("lingkungan-bersinar.create");
            Route::post('/lingkungan-bersinar', [LingkunganBersinarController::class, 'store'])->name("lingkungan-bersinar.store");
            Route::get('/lingkungan-bersinar/{id}/edit', [LingkunganBersinarController::class, 'edit'])->name('lingkungan-bersinar.edit');
            Route::put('/lingkungan-bersinar/{id}', [LingkunganBersinarController::class, 'update'])->name('lingkungan-bersinar.update');
            Route::delete('/lingkungan-bersinar/{id}', [LingkunganBersinarController::class, 'destroy'])->name("lingkungan-bersinar.destroy");

            // P2M CFD (Car Free Day)
            Route::get('/cfd/create', [CfdController::class, 'create'])->name("cfd.create");
            Route::post('/cfd', [CfdController::class, 'store'])->name("cfd.store");
            Route::get('/cfd/{id}/edit', [CfdController::class, 'edit'])->name('cfd.edit');
            Route::put('/cfd/{id}', [CfdController::class, 'update'])->name('cfd.update');
            Route::delete('/cfd/{id}', [CfdController::class, 'destroy'])->name("cfd.destroy");

            // P2M Media Elektronik
            Route::get('/elektronik/create', [ElektronikController::class, 'create'])->name("elektronik.create");
            Route::post('/elektronik', [ElektronikController::class, 'store'])->name("elektronik.store");
            Route::get('/elektronik/{id}/edit', [ElektronikController::class, 'edit'])->name('elektronik.edit');
            Route::put('/elektronik/{id}', [ElektronikController::class, 'update'])->name('elektronik.update');
            Route::delete('/elektronik/{id}', [ElektronikController::class, 'destroy'])->name("elektronik.destroy");

            // P2M Media Non Elektronik
            Route::get('/non-elektronik/create', [NonElektronikController::class, 'create'])
            ->name("non-elektronik.create");
            Route::post('/non-elektronik', [NonElektronikController::class, 'store'])
                ->name("non-elektronik.store");
            Route::get('/non-elektronik/{id}/edit', [NonElektronikController::class, 'edit'])
                ->name('non-elektronik.edit');
            Route::put('/non-elektronik/{id}', [NonElektronikController::class, 'update'])
                ->name('non-elektronik.update');
            Route::delete('/non-elektronik/{id}', [NonElektronikController::class, 'destroy'])
                ->name("non-elektronik.destroy");

            // P2M online
            Route::get('/online/create', [OnlineController::class, 'create'])->name("online.create");
            Route::post('/online', [OnlineController::class, 'store'])->name("online.store");
            Route::get('/online/{id}/edit', [OnlineController::class, 'edit'])->name('online.edit');
            Route::put('/online/{id}', [OnlineController::class, 'update'])->name('online.update');
            Route::delete('/online/{id}', [OnlineController::class, 'destroy'])->name("online.destroy");

            // P2M tes urine
            Route::get('/tes-urine/create', [TesUrineController::class, 'create'])->name("tes-urine.create");
            Route::post('/tes-urine', [TesUrineController::class, 'store'])->name("tes-urine.store");
            Route::get('/tes-urine/{id}/edit', [TesUrineController::class, 'edit'])->name('tes-urine.edit');
            Route::put('/tes-urine/{id}', [TesUrineController::class, 'update'])->name('tes-urine.update');
            Route::delete('/tes-urine/{id}', [TesUrineController::class, 'destroy'])->name("tes-urine.destroy");

            // P2M desa bersinar
            Route::get('/desa-bersinar/create', [DesaBersinarController::class, 'create'])->name('desa-bersinar.create');
            Route::post('/desa-bersinar', [DesaBersinarController::class, 'store'])->name('desa-bersinar.store');
            Route::get('/desa-bersinar/{id}/edit', [DesaBersinarController::class, 'edit'])->name('desa-bersinar.edit');
            Route::put('/desa-bersinar/{id}', [DesaBersinarController::class, 'update'])->name('desa-bersinar.update');
            Route::delete('/desa-bersinar/{id}', [DesaBersinarController::class, 'destroy'])->name('desa-bersinar.destroy');

            // P2M safari religi
            Route::get('/safari-religi/create', [SafariReligiController::class, 'create'])->name("safari_religi.create");
            Route::post('/safari-religi', [SafariReligiController::class, 'store'])->name("safari_religi.store");
            Route::get('/safari-religi/{id}/edit', [SafariReligiController::class, 'edit'])->name('safari_religi.edit');
            Route::put('/safari-religi/{id}', [SafariReligiController::class, 'update'])->name('safari_religi.update');
            Route::delete('/safari-religi/{id}', [SafariReligiController::class, 'destroy'])->name("safari_religi.destroy");

            // 2. P2M Safari Religi
            Route::get('/safari-religi/create', [SafariReligiController::class, 'create'])->name("safari-religi.create");
            Route::post('/safari-religi', [SafariReligiController::class, 'store'])->name("safari-religi.store");
            Route::get('/safari-religi/{id}/edit', [SafariReligiController::class, 'edit'])->name('safari-religi.edit');
            Route::put('/safari-religi/{id}', [SafariReligiController::class, 'update'])->name('safari-religi.update');
            Route::delete('/safari-religi/{id}', [SafariReligiController::class, 'destroy'])->name("safari-religi.destroy");
        });
    });


    Route::prefix('berantas')
        ->name('berantas.')
        ->group(function() {
            Route::middleware(['role:admin,admin_satker,operator'])->group(function() {

                // Ungkap Kasus
                Route::get('/ungkap-kasus/export', [UngkapKasusController::class, 'export'])->name('ungkap-kasus.export');
                Route::get('/ungkap-kasus', [UngkapKasusController::class, 'index'])->name("ungkap-kasus.index");

                // TAT (Tim Asesmen Terpadu)
                Route::get('/tat/export', [TatController::class, 'export'])->name('tat.export');
                Route::get('/tat', [TatController::class, 'index'])->name("tat.index");
            });

            Route::middleware(['role:operator'])->group(function() {

                // Ungkap Kasus
                Route::get('/ungkap-kasus/create', [UngkapKasusController::class, 'create'])->name("ungkap-kasus.create");
                Route::post('/ungkap-kasus', [UngkapKasusController::class, 'store'])->name("ungkap-kasus.store");
                Route::get('/ungkap-kasus/{id}/edit', [UngkapKasusController::class, 'edit'])->name('ungkap-kasus.edit');
                Route::put('/ungkap-kasus/{id}', [UngkapKasusController::class, 'update'])->name('ungkap-kasus.update');
                Route::delete('/ungkap-kasus/{id}', [UngkapKasusController::class, 'destroy'])->name("ungkap-kasus.destroy");

                // TAT (Tim Asesmen Terpadu)
                Route::get('/tat/create', [TatController::class, 'create'])->name("tat.create");
                Route::post('/tat', [TatController::class, 'store'])->name("tat.store");
                Route::get('/tat/{id}/edit', [TatController::class, 'edit'])->name('tat.edit');
                Route::put('/tat/{id}', [TatController::class, 'update'])->name('tat.update');
                Route::delete('/tat/{id}', [TatController::class, 'destroy'])->name("tat.destroy");
            });
            
        });

});
