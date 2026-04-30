<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        // S1 - Secure: Restrict Pulse dashboard to Super Admin and Admin only
        Gate::define('viewPulse', function (User $user) {
            return $user->hasRole('super_admin') || $user->hasRole('admin');
        });

        // S2 - Sustain: Protect author credit for OSS Internara
        $author = \App\Support\AppInfo::author();
        $authorName = $author['name'] ?? '';

        if ($authorName !== 'Reas Vyn') {
            throw new \RuntimeException('Invalid author signature. Unauthorized modification detected.');
        }
    }
}
