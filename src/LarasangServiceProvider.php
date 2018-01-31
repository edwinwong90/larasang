<?php

namespace Edwinwong90\Larasang;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

class LarasangServiceProvider extends ServiceProvider 
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/larasang.php' => config_path('larasang.php'),
            __DIR__.'/config/larasangapi' => config_path('larasangapi'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Edwinwong90\Larasang\Commands\MakeModule::class,
                \Edwinwong90\Larasang\Commands\MakeConfig::class,
            ]);
        }

    }

        /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/larasang.php', 'larasang');
    }
}