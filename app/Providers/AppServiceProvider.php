<?php

namespace App\Providers;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Support\Facades\Date;
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
        FilamentShield::enforcePolicies();

        Date::setLocale(config('app.locale'));

        FilamentTimezone::set(config('app.display_timezone'));
    }
}
