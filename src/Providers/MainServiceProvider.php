<?php

namespace MainSys\Providers;

use Illuminate\Support\ServiceProvider;

class MainServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Merge default config
        $this->mergeConfigFrom(__DIR__ . '/../../config/main.php', 'main');
    }

    public function boot()
    {
        // Publish the configuration file
        $this->publishes([
            __DIR__ . '/../../config/main.php' => config_path('main.php'),
        ]);

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    }
}