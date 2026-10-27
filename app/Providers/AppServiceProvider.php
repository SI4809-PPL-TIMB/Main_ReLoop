<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend('migrator', function ($migrator, $app) {
            return new \App\Support\CustomMigrator(
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
        //
    }
}
