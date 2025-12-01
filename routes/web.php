<?php

use App\Http\Controllers\P2m\SosialisasiController;
use App\Models\P2mSosialisasi;
use App\Http\Controllers\P2m\CfdController;
use App\Models\p2mcfd;

use App\Http\Controllers\P2m\ElektronikController;
use App\Models\p2mElektronik;

use App\Http\Controllers\P2m\OnlineController;
use App\Models\p2mOnline;

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


        Route::get('/cfd', [CfdController::class, 'index'])->name("cfd.index");
        Route::get('/cfd/create', [CfdController::class, 'create'])->name("cfd.create");
        Route::post('/cfd/store', [CfdController::class, 'store'])->name("cfd.store");


        Route::get('/elektronik', [ElektronikController::class, 'index'])->name("elektronik.index");
        Route::get('/elektronik/create', [ElektronikController::class, 'create'])->name("elektronik.create");
        Route::post('/elektronik/store', [ElektronikController::class, 'store'])->name("elektronik.store");


        Route::get('/online', [OnlineController::class, 'index'])->name("online.index");
        Route::get('/online/create', [OnlineController::class, 'create'])->name("online.create");
        Route::post('/online/store', [OnlineController::class, 'store'])->name("online.store");

    });

