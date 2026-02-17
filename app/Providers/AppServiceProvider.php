<?php

namespace App\Providers;

use App\Models\RfidTag;
use App\Observers\RfidTagObserver;
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
        RfidTag::observe(RfidTagObserver::class);
    }
}
