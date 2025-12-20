<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

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
        //
        // Customize the password reset URL for your React SPA
        ResetPassword::createUrlUsing(function ($notifiable, $token) {
            // Change this to your actual React route
            // Example: http://localhost:5173/reset-password?token=abc123&email=user@example.com
            return 'http://localhost:5173/reset-password?token=' . $token . '&email=' . $notifiable->email;
        });
    }
}
