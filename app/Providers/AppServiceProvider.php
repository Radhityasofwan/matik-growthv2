<?php

namespace App\Providers;

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
        // Kode ini akan mengirimkan data jumlah notifikasi ke komponen navbar
        // setiap kali komponen tersebut dimuat. Ini akan memperbaiki error.
        View::composer('partials.navbar', function ($view) {
            // Optimasi: Menggunakan null safe operator untuk mendapatkan jumlah notifikasi
            // jika user login, dan default ke 0 jika tidak.
            $unreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
            $view->with('unreadNotificationsCount', $unreadCount);
        });
    }
}

