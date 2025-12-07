<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\P2m\SosialisasiController;
use App\Http\Controllers\P2m\UpacaraController;
use App\Http\Controllers\P2m\KieController;
use App\Http\Controllers\P2m\LingkunganController;
use App\Models\P2mSosialisasi;
use App\Http\Controllers\P2m\CfdController;
use App\Models\p2mcfd;

use App\Http\Controllers\P2m\ElektronikController;
use App\Models\p2mElektronik;

use App\Http\Controllers\P2m\OnlineController;
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
            Route::get('/sosialisasi/export', [SosialisasiController::class, 'export'])->name('sosialisasi.export');
            Route::get('/sosialisasi', [SosialisasiController::class, 'index'])->name("sosialisasi.index");
        });

        Route::middleware(['role:operator'])->group(function() {
            Route::get('/sosialisasi/create', [SosialisasiController::class, 'create'])->name("sosialisasi.create");
            Route::post('/sosialisasi', [SosialisasiController::class, 'store'])->name("sosialisasi.store");

            Route::get('/sosialisasi/{id}/edit', [SosialisasiController::class, 'edit'])->name('sosialisasi.edit');
            Route::put('/sosialisasi/{id}', [SosialisasiController::class, 'update'])->name('sosialisasi.update');

            Route::delete('/sosialisasi/{id}', [SosialisasiController::class, 'destroy'])->name("sosialisasi.destroy");
        });

        Route::get('/upacara', [UpacaraController::class, 'index'])->name("upacara.index");
        Route::get('/upacara/create', [UpacaraController::class, 'create'])->name("upacara.create");
        Route::post('/upacara/store', [UpacaraController::class, 'store'])->name("upacara.store");
        Route::delete('/upacara/destroy/{id}', [UpacaraController::class, 'destroy'])->name("upacara.destroy");

        Route::get('/kie', [KieController::class, 'index'])->name("kie.index");
        Route::get('/kie/create', [KieController::class, 'create'])->name("kie.create");
        Route::post('/kie/store', [KieController::class, 'store'])->name("kie.store");
        Route::delete('/kie/destroy/{id}', [KieController::class, 'destroy'])->name("kie.destroy");

        Route::get('/lingkungan', [LingkunganController::class, 'index'])->name("lingkungan.index");
        Route::get('/lingkungan/create', [LingkunganController::class, 'create'])->name("lingkungan.create");
        Route::post('/lingkungan/store', [LingkunganController::class, 'store'])->name("lingkungan.store");
        Route::delete('/lingkungan/destroy/{id}', [LingkunganController::class, 'destroy'])->name("lingkungan.destroy");

        Route::get('/cfd', [CfdController::class, 'index'])->name("cfd.index");
        Route::get('/cfd/create', [CfdController::class, 'create'])->name("cfd.create");
        Route::post('/cfd/store', [CfdController::class, 'store'])->name("cfd.store");
        Route::delete('/cfd/destroy/{id}', [cfdController::class, 'destroy'])->name("cfd.destroy");


        Route::get('/elektronik', [ElektronikController::class, 'index'])->name("elektronik.index");
        Route::get('/elektronik/create', [ElektronikController::class, 'create'])->name("elektronik.create");
        Route::post('/elektronik/store', [ElektronikController::class, 'store'])->name("elektronik.store");
        Route::delete('/elektronik/destroy/{id}', [ElektronikController::class, 'destroy'])->name("elektronik.destroy");


        Route::get('/online', [OnlineController::class, 'index'])->name("online.index");
        Route::get('/online/create', [OnlineController::class, 'create'])->name("online.create");
        Route::post('/online/store', [OnlineController::class, 'store'])->name("online.store");
        Route::delete('/online/destroy/{id}', [OnlineController::class, 'destroy'])->name("online.destroy");

    });

});
