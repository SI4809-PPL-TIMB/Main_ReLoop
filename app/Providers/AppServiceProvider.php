<?php

namespace App\Providers;

use App\Support\CustomMigrator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\ViewErrorBag;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend('migrator', function ($migrator, $app) {
            return new CustomMigrator(
                $app['migration.repository'],
                $app['db'],
                $app['files'],
                $app['events']
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by('login:'.hash('sha256', $request->ip()))
            ->response(function (Request $request, array $headers) {
                $message = 'Terlalu banyak percobaan masuk. Coba lagi dalam '.$headers['Retry-After'].' detik.';

                if ($request->expectsJson()) {
                    return response()->json(['message' => $message], 429, $headers);
                }

                return response()->view('auth.login', [
                    'errors' => (new ViewErrorBag)->put('default', new MessageBag(['identifier' => $message])),
                ], 429, $headers);
            }));
    }
}
