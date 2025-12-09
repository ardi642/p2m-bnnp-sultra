<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\P2m\SosialisasiController;
use App\Http\Controllers\P2m\UpacaraController;
use App\Http\Controllers\P2m\KieController;
use App\Http\Controllers\P2m\LingkunganController;
use App\Models\P2mSosialisasi;
use App\Http\Controllers\P2m\CfdController;
use App\Http\Controllers\P2m\DesaBersinarController;
use App\Models\p2mcfd;

use App\Http\Controllers\P2m\ElektronikController;
use App\Models\p2mElektronik;

use App\Http\Controllers\P2m\OnlineController;
use App\Http\Controllers\P2m\SafariReligiController;
use App\Http\Controllers\P2m\TesUrineController;
use App\Models\p2mOnline;

use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');
});

Route::middleware('auth')->group(function() {
    Route::get('/', function () {
        return view('welcome');
    });

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::prefix('p2m')
    ->name('p2m.')
    ->group(function() {
        Route::get('/', function() {
            return view('p2m.index');
        })->name("index");
        
        Route::middleware(['role:admin,operator'])->group(function() {

            // P2M sosialisasi
            Route::get('/sosialisasi/export', [SosialisasiController::class, 'export'])->name('sosialisasi.export');
            Route::get('/sosialisasi', [SosialisasiController::class, 'index'])->name("sosialisasi.index");

            // P2M cfd
            Route::get('/cfd', [CfdController::class, 'index'])->name("cfd.index");
            Route::get('/cfd/export', [CfdController::class, 'export'])->name('cfd.export');

            // P2M elektronik
            Route::get('/elektronik', [ElektronikController::class, 'index'])->name("elektronik.index");
            Route::get('/elektronik/export', [ElektronikController::class, 'export'])->name('elektronik.export');

            // P2M online
            Route::get('/online', [OnlineController::class, 'index'])->name("online.index");
            Route::get('/online/export', [OnlineController::class, 'export'])->name('online.export');

            // P2M upacara
            Route::get('/upacara', [UpacaraController::class, 'index'])->name("upacara.index");
            Route::get('/upacara/export', [UpacaraController::class, 'export'])->name('upacara.export');

            // P2M kie
            Route::get('/kie', [KieController::class, 'index'])->name("kie.index");
            Route::get('/kie/export', [KieController::class, 'export'])->name('kie.export');

            // P2M lingkungan
            Route::get('/lingkungan', [LingkunganController::class, 'index'])->name("lingkungan.index");
            Route::get('/lingkungan/export', [LingkunganController::class, 'export'])->name('lingkungan.export');

            // P2M tes urine / deteksi dini
            Route::get('/tes-urine', [TesUrineController::class, 'index'])->name("tes_urine.index");
            Route::get('/tes-urine/export', [TesUrineController::class, 'export'])->name('tes_urine.export');

            // P2M desa bersinar
            Route::get('/desa-bersinar', [DesaBersinarController::class, 'index'])->name('desa_bersinar.index');
            Route::get('/desa-bersinar/export', [DesaBersinarController::class, 'export'])->name('desa_bersinar.export');
            
            // P2M safari religi
            Route::get('/safari-religi', [SafariReligiController::class, 'index'])->name("safari_religi.index");
            Route::get('/safari-religi/export', [SafariReligiController::class, 'export'])->name('safari_religi.export');
        });

        Route::middleware(['role:operator'])->group(function() {

            // P2M sosialisasi
            Route::get('/sosialisasi/create', [SosialisasiController::class, 'create'])->name("sosialisasi.create");
            Route::post('/sosialisasi', [SosialisasiController::class, 'store'])->name("sosialisasi.store");
            Route::get('/sosialisasi/{id}/edit', [SosialisasiController::class, 'edit'])->name('sosialisasi.edit');
            Route::put('/sosialisasi/{id}', [SosialisasiController::class, 'update'])->name('sosialisasi.update');
            Route::delete('/sosialisasi/{id}', [SosialisasiController::class, 'destroy'])->name("sosialisasi.destroy");
            
            // P2M cfd
            Route::get('/cfd/create', [CfdController::class, 'create'])->name("cfd.create");
            Route::post('/cfd', [CfdController::class, 'store'])->name("cfd.store");
            Route::delete('/cfd/{id}', [cfdController::class, 'destroy'])->name("cfd.destroy");

            // P2M elektronik
            Route::get('/elektronik/create', [ElektronikController::class, 'create'])->name("elektronik.create");
            Route::post('/elektronik', [ElektronikController::class, 'store'])->name("elektronik.store");
            Route::delete('/elektronik/{id}', [ElektronikController::class, 'destroy'])->name("elektronik.destroy");

            // P2M online
            Route::get('/online/create', [OnlineController::class, 'create'])->name("online.create");
            Route::post('/online', [OnlineController::class, 'store'])->name("online.store");
            Route::delete('/online/{id}', [OnlineController::class, 'destroy'])->name("online.destroy");

            // P2M upacara
            Route::get('/upacara/create', [UpacaraController::class, 'create'])->name("upacara.create");
            Route::post('/upacara', [UpacaraController::class, 'store'])->name("upacara.store");
            Route::delete('/upacara/{id}', [UpacaraController::class, 'destroy'])->name("upacara.destroy");

            // P2M kie
            Route::get('/kie/create', [KieController::class, 'create'])->name("kie.create");
            Route::post('/kie', [KieController::class, 'store'])->name("kie.store");
            Route::delete('/kie/{id}', [KieController::class, 'destroy'])->name("kie.destroy");

            // P2M lingkungan
            Route::get('/lingkungan/create', [LingkunganController::class, 'create'])->name("lingkungan.create");
            Route::post('/lingkungan', [LingkunganController::class, 'store'])->name("lingkungan.store");
            Route::delete('/lingkungan/{id}', [LingkunganController::class, 'destroy'])->name("lingkungan.destroy");

            // P2M tes urine
            Route::get('/tes-urine/create', [TesUrineController::class, 'create'])->name("tes_urine.create");
            Route::post('/tes-urine', [TesUrineController::class, 'store'])->name("tes_urine.store");
            Route::get('/tes-urine/{id}/edit', [TesUrineController::class, 'edit'])->name('tes_urine.edit');
            Route::put('/tes-urine/{id}', [TesUrineController::class, 'update'])->name('tes_urine.update');
            Route::delete('/tes-urine/{id}', [TesUrineController::class, 'destroy'])->name("tes_urine.destroy");

            // P2M desa bersinar
            Route::get('/desa-bersinar/create', [DesaBersinarController::class, 'create'])->name('desa_bersinar.create');
            Route::post('/desa-bersinar', [DesaBersinarController::class, 'store'])->name('desa_bersinar.store');
            Route::get('/desa-bersinar/{id}/edit', [DesaBersinarController::class, 'edit'])->name('desa_bersinar.edit');
            Route::put('/desa-bersinar/{id}', [DesaBersinarController::class, 'update'])->name('desa_bersinar.update');
            Route::delete('/desa-bersinar/{id}', [DesaBersinarController::class, 'destroy'])->name('desa_bersinar.destroy');

            // P2M safari religi
            Route::get('/safari-religi/create', [SafariReligiController::class, 'create'])->name("safari_religi.create");
            Route::post('/safari-religi', [SafariReligiController::class, 'store'])->name("safari_religi.store");
            Route::get('/safari-religi/{id}/edit', [SafariReligiController::class, 'edit'])->name('safari_religi.edit');
            Route::put('/safari-religi/{id}', [SafariReligiController::class, 'update'])->name('safari_religi.update');
            Route::delete('/safari-religi/{id}', [SafariReligiController::class, 'destroy'])->name("safari_religi.destroy");
        });
    });

});
