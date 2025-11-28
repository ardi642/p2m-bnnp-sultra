<?php

use App\Http\Controllers\P2m\SosialisasiController;
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
    });

