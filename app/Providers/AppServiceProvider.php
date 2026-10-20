<?php

namespace App\Providers;

use App\Support\DataPath;
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
        DataPath::ensure(config('zapvault.data_path'), [
            config('database.connections.sqlite.database'),
            config('database.connections.queue.database'),
        ]);
    }
}
