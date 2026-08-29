<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // The api middleware group applies throttle:api; define the limiter here
        // rather than pulling in install:api, which would also install Sanctum.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
    }
}
