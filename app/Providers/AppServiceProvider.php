<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
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
        Gate::define('viewPulse', fn ($user) => true);

        if (DB::getDriverName() === 'sqlite') {
            $database = config('database.connections.sqlite.database');
            if ($database !== ':memory:') {
                DB::statement('PRAGMA journal_mode=WAL;');
            }
            DB::statement('PRAGMA foreign_keys=ON;');
        }
    }
}
