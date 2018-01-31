<?php

namespace Edwinwong90\Larasang;

use Illuminate\Support\Facades\Route;

class Larasang 
{

    public static function routes()
    {
        $callback = function ($router) {
            $router->all();
        };

        $options = [];

        if (config('larasang.prefix')) {
            $options['prefix'] = config('larasang.prefix');
        }

        if (config('larasang.middleware')) {
            $options['middleware'] = config('larasang.middleware');
        }

        if (config('larasang.namespace')) {
            $options['namespace'] = config('larasang.namespace');
        }

        Route::group($options, function ($router) use ($callback) {
            $callback(new RouteRegistrar($router));
        });
    }

}