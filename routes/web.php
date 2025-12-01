<?php

use App\Http\Controllers\P2m\SosialisasiController;
use App\Http\Controllers\P2m\SafariReligiController;
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

        Route::get('/safarireligi', [SafariReligiController::class, 'index'])->name("safarireligi.index");

        Route::get('/safarireligi/create', [SafariReligiController::class, 'create'])->name("safarireligi.create");

        Route::post('/safarireligi/store', [SafariReligiController::class, 'store'])->name("safarireligi.store");
    });