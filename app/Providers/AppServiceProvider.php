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

    //adding rate limit for 30 requests per minute per ip to not exhaust
    public function boot(): void
    {
        RateLimiter::for('public-search', function (Request $request): Limit {
            return Limit::perMinute(30)->by($request->ip())->response(function () {
                return response()->json([
                    'message' => 'Too many requests at the moment. Please try again later.',
                ], 429);
            });
        });
    }
}
