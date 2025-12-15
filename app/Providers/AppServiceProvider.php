<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;

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
        RateLimiter::for('public-search', function (Request $request): Limit {
            return Limit::perMinute(30)->by($request->ip())->response(function () {
                return response()->json([
                    'message' => 'Too many requests. Please slow down.',
                ], 429);
            });
        });
    }
}
