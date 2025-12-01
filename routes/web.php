<?php

use App\Http\Controllers\P2m\SosialisasiController;
use App\Http\Controllers\P2m\UpacaraController;
use App\Http\Controllers\P2m\KieController;
use App\Http\Controllers\P2m\LingkunganController;
use App\Models\P2mSosialisasi;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('p2m')
    ->name('p2m.')
    ->group(function() {
        Route::get('/', function() {
            return view('p2m.index');
        })->name("index");
        
        Route::get('/sosialisasi', [SosialisasiController::class, 'index'])->name("sosialisasi.index");
        Route::get('/sosialisasi/create', [SosialisasiController::class, 'create'])->name("sosialisasi.create");
        Route::post('/sosialisasi/store', [SosialisasiController::class, 'store'])->name("sosialisasi.store");

        Route::delete('/sosialisasi/destroy/{id}', [SosialisasiController::class, 'destroy'])->name("sosialisasi.destroy");
        Route::get('/upacara', [UpacaraController::class, 'index'])->name("upacara.index");
        Route::get('/upacara/create', [UpacaraController::class, 'create'])->name("upacara.create");
        Route::post('/upacara/store', [UpacaraController::class, 'store'])->name("upacara.store");

        Route::get('/kie', [KieController::class, 'index'])->name("kie.index");
        Route::get('/kie/create', [KieController::class, 'create'])->name("kie.create");
        Route::post('/kie/store', [KieController::class, 'store'])->name("kie.store");

        Route::get('/lingkungan', [LingkunganController::class, 'index'])->name("lingkungan.index");
        Route::get('/lingkungan/create', [LingkunganController::class, 'create'])->name("lingkungan.create");
        Route::post('/lingkungan/store', [LingkunganController::class, 'store'])->name("lingkungan.store");
    });

