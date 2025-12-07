<?php

namespace App\Providers;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Menggunakan closure composer ('*' artinya berlaku untuk SEMUA file view)
        View::composer('*', function ($view) {
            
             // Default kosong (untuk Guest/Belum Login)
            $satuanKerja = '';
            $pegawai = null;

            // Cek dulu apakah user sedang login?
            if (Auth::check()) {
                /** @var \App\Models\User $user */
                $user = Auth::user();

                // LOGIKA UTAMA DISINI:
                // Ambil nama satker. Jika null (karena dia Admin/tidak punya satker),
                // maka isi dengan teks 'Administrator'.
                
                $pegawai = $user->pegawai;
                $satuanKerja = $pegawai?->satuanKerja?->satuan_kerja ?? 'Super Admin BNNP Sultra';
            }
            $view->with('satuanKerja', $satuanKerja);
            $view->with('pegawai', $pegawai);

        });
    }
}
